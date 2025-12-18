<?php

namespace App\Services;

use App\Models\Module;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ModuleDestroyerService
{
    protected $module;
    protected $moduleName;
    protected $studlyName;
    protected $camelName;
    protected $tableName;
    protected $inTransaction = false;

    public function __construct(Module $module)
    {
        $this->module = $module;
        $this->moduleName = Str::kebab($module->name);
        $this->studlyName = Str::studly($module->name);
        $this->camelName = Str::camel($module->name);

        // Get the table name from the model class
        $modelClass = "App\\Models\\{$this->studlyName}";
        if (class_exists($modelClass)) {
            $modelInstance = new $modelClass;
            $this->tableName = $modelInstance->getTable();
        } else {
            // Fallback to default table name
            $this->tableName = Str::snake($module->name . 's');
        }
    }

    /**
     * Execute the module destruction process
     *
     * @param bool $deleteChildren Whether to delete child modules
     * @return array
     */
    public function destroy(bool $deleteChildren = false): array
    {
        // 1. First check for child modules - before any transaction starts
        $children = $this->module->children;
        if ($children->count() > 0 && !$deleteChildren) {
            return [
                'success' => false,
                'error' => 'Module has child modules',
                'has_children' => true,
                'children' => $children->pluck('name')->toArray()
            ];
        }

        // We never start a transaction in the service when called from the controller
        // The controller is responsible for transaction management
        try {
            $deletedFiles = [];

            // 2. Delete children first if authorized
            if ($deleteChildren && $children->count() > 0) {
                foreach ($children as $child) {
                    // Create a new destroyer for the child but don't start a new transaction
                    $childDestroyer = new self($child);
                    $childDestroyer->setInTransaction(true); // Mark as already in transaction

                    // Delete child module components
                    $this->deleteModuleComponents($child, $deletedFiles);
                }
            }

            // 3. Delete the module itself
            $this->deleteModuleComponents($this->module, $deletedFiles);

            return [
                'success' => true,
                'deleted_items' => $deletedFiles
            ];

        } catch (\Exception $e) {
            Log::error('Module destruction failed', [
                'module' => $this->moduleName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete all components of a module
     *
     * @param Module $module
     * @param array $deletedFiles
     * @return void
     */
    protected function deleteModuleComponents(Module $module, array &$deletedFiles): void
    {
        // Create a destroyer for this module
        $destroyer = new self($module);
        $destroyer->setInTransaction(true); // Mark as already in transaction

        // 1. Delete role permissions
        $destroyer->deleteRolePermissions();
        $deletedFiles[] = "Role permissions for module: {$module->name}";

        // 2. Delete database table
        if ($destroyer->deleteTable()) {
            $deletedFiles[] = "Database table: {$destroyer->tableName}";
        }

        // 3. Delete generated files
        $deletedFiles = array_merge($deletedFiles, $destroyer->deleteGeneratedFiles());

        // 4. Remove routes
        if ($destroyer->removeRoutes()) {
            $deletedFiles[] = "Routes for module: {$module->name}";
        }

        // 5. Delete translations
        if ($destroyer->deleteTranslations()) {
            $deletedFiles[] = "Translation files for module: {$module->name}";
        }

        // 6. Finally delete the module record
        $module->forceDelete();
        $deletedFiles[] = "Module record: {$module->name}";
    }

    /**
     * Delete the database table for this module
     *
     * @return bool
     */
    protected function deleteTable(): bool
    {
        try {
            if (Schema::hasTable($this->tableName)) {
                Schema::dropIfExists($this->tableName);

                Log::info("Dropped database table", [
                    'table' => $this->tableName,
                    'module' => $this->module->name
                ]);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Failed to drop database table", [
                'table' => $this->tableName,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get all tables that have foreign key constraints referencing the given table
     */
    protected function getReferencingTables(string $tableName): array
    {
        $constraints = DB::select("
            SELECT
                tc.CONSTRAINT_NAME as constraint_name,
                tc.TABLE_NAME as table_name,
                kcu.COLUMN_NAME as column_name,
                kcu.REFERENCED_TABLE_NAME as referenced_table_name,
                kcu.REFERENCED_COLUMN_NAME as referenced_column_name
            FROM information_schema.TABLE_CONSTRAINTS tc
            JOIN information_schema.KEY_COLUMN_USAGE kcu
                ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
            WHERE tc.CONSTRAINT_TYPE = 'FOREIGN KEY'
                AND kcu.REFERENCED_TABLE_NAME = ?
                AND tc.TABLE_SCHEMA = DATABASE()",
            [$tableName]
        );

        return array_map(function($constraint) {
            return [
                'constraint_name' => $constraint->constraint_name,
                'table_name' => $constraint->table_name,
                'column_name' => $constraint->column_name,
                'referenced_table_name' => $constraint->referenced_table_name,
                'referenced_column_name' => $constraint->referenced_column_name
            ];
        }, $constraints);
    }

    /**
     * Check if module can be safely deleted
     */
    protected function canSafelyDelete(): bool
    {
        // Get referencing tables
        $referencingTables = $this->getReferencingTables($this->tableName);

        if (!empty($referencingTables)) {
            $tableNames = array_unique(array_map(function($constraint) {
                return $constraint['table_name'];
            }, $referencingTables));

            $message = "Cannot delete module. Table {$this->tableName} is referenced by: " . implode(', ', $tableNames);
            throw new \Exception($message);
        }

        return true;
    }

    /**
     * Delete all generated files for the module
     */
    protected function deleteGeneratedFiles(): array
    {
        $deletedFiles = [];

        // Define paths to delete
        $paths = [
            // Models
            app_path("Models/{$this->studlyName}.php"),

            // Controllers
            app_path("Http/Controllers/{$this->studlyName}Controller.php"),

            // Requests
            app_path("Http/Requests/{$this->studlyName}Request.php"),

            // Resources
            app_path("Http/Resources/{$this->studlyName}Resource.php"),

            // OR Resources with DTR
            app_path("Http/Resources/{$this->studlyName}DTR.php"),

            // Services
            app_path("Services/{$this->studlyName}Service.php"),

            // Views
            resource_path("views/backend/{$this->moduleName}"),

            // Components
            app_path("View/Components/{$this->studlyName}Form.php"),

            // Migrations (using table name instead of plural name)
            database_path("migrations/*_create_{$this->tableName}_table.php"),
        ];

        foreach ($paths as $path) {
            if (Str::contains($path, '*')) {
                // Handle wildcard paths (like migrations)
                $files = glob($path);
                if ($files) {
                    foreach ($files as $file) {
                        if (File::exists($file)) {
                            File::delete($file);
                            $deletedFiles[] = "File: " . basename($file);
                        }
                    }
                }
            } else {
                if (File::exists($path)) {
                    if (is_dir($path)) {
                        File::deleteDirectory($path);
                        $deletedFiles[] = "Directory: " . basename($path);
                    } else {
                        File::delete($path);
                        $deletedFiles[] = "File: " . basename($path);
                    }
                }
            }
        }

        return $deletedFiles;
    }

    /**
     * Remove module routes from web.php
     */
    protected function removeRoutes(): bool
    {
        $routesPath = base_path('routes/module-generated.php');

        if (File::exists($routesPath)) {
            $contents = File::get($routesPath);

            // Find the start and end markers for this module
            $startMarker = "// {$this->camelName} Start";
            $endMarker = "// {$this->camelName} End";

            // Get the positions of the markers
            $startPos = strpos($contents, $startMarker);
            $endPos = strpos($contents, $endMarker);

            if ($startPos !== false && $endPos !== false) {
                // Get the end of the line containing the end marker
                $endPos = strpos($contents, "\n", $endPos);
                if ($endPos === false) {
                    $endPos = strlen($contents);
                } else {
                    $endPos += 1; // Include the newline
                }

                // Remove the routes block
                $newContents = substr($contents, 0, $startPos) .
                              substr($contents, $endPos);

                File::put($routesPath, $newContents);
                return true;
            }
        }
        return false;
    }

    /**
     * Delete translation files
     */
    protected function deleteTranslations(): bool
    {
        $deleted = false;
        $locales = config('languages.available');

        foreach ($locales as $locale => $language) {
            $path = lang_path("{$locale}/{$this->studlyName}");
            if (File::exists($path)) {
                File::deleteDirectory($path);
                $deleted = true;
            }
        }

        return $deleted;
    }

    /**
     * Delete role permissions for this module
     */
    protected function deleteRolePermissions(): void
    {
        try {
            // Simply delete records from role_permissions table
            DB::table('role_permissions')
                ->where('module_id', $this->module->id)
                ->delete();

            Log::info('Deleted role permissions for module', [
                'module_id' => $this->module->id,
                'module_name' => $this->module->name
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete role permissions', [
                'module_id' => $this->module->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Set the transaction flag
     *
     * @param bool $inTransaction
     * @return void
     */
    public function setInTransaction(bool $inTransaction): void
    {
        $this->inTransaction = $inTransaction;
    }
}
