<?php

namespace App\Services;

use App\Models\Module;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ViewGeneratorService;
use Illuminate\Support\Facades\Validator;

class ModuleGeneratorService extends BaseService
{
    protected array $modelTraits = [];

    public function __construct(Module $model)
    {
        parent::__construct($model);
    }

    protected $steps = [
        1 => 'validateBasicInfo',
        2 => 'validateDatabaseStructure',
        3 => 'validateFormFields',
        4 => 'validateTranslations',
        5 => 'validatePermissions'
    ];

    /**
     * Validate each step of the module generation process
     */
    public function validateStep($step, array $data)
    {
        if (isset($this->steps[$step])) {
            $method = $this->steps[$step];
            return $this->{$method}($data);
        }

        throw new \InvalidArgumentException("Invalid step number");
    }

    /**
     * Get validation rules for each step
     */
    // private function getStepValidationRules($step)
    // {
    //     switch ($step) {
    //         case 1: // Basic Info
    //             return [
    //                 'name' => 'required|string|max:50',
    //                 'group_id' => 'required|exists:module_groups,id',
    //                 'parent_id' => 'required|integer',
    //                 'type' => 'required|in:1,2',
    //                 'module_type' => 'required|in:1,2',
    //                 'description' => 'nullable|string|max:200',
    //                 'show_in_menu' => 'boolean',
    //                 'url' => 'required|string|max:50|unique:modules,url',
    //                 'icon' => 'required|string|max:50',
    //                 'slug' => 'required|string|max:50|unique:modules,slug',
    //                 'sort_order' => 'required|integer|min:0',
    //                 'translations' => 'required|array',
    //                 'translations.*.locale' => 'required|string|size:2',
    //                 'translations.*.singular_name' => 'required|string|max:50',
    //                 'translations.*.plural_name' => 'required|string|max:50',
    //             ];

    //         case 2: // Database Structure
    //             return [
    //                 'table_name' => 'required|string|max:50|regex:/^[a-z][a-z0-9_]*$/',
    //                 'engine' => 'required|in:InnoDB,MyISAM',
    //                 'timestamps' => 'boolean',
    //                 'soft_deletes' => 'boolean',
    //                 'auditable' => 'boolean',
    //                 'primary_key_type' => 'required|in:increments,bigIncrements,uuid',
    //                 'primary_key_name' => 'required|string|max:50',
    //                 'columns' => 'required|array|min:1',
    //                 'columns.*.name' => 'required|string|max:50|regex:/^[a-z][a-z0-9_]*$/',
    //                 'columns.*.type' => 'required|string',
    //                 'columns.*.length' => 'nullable|string',
    //                 'columns.*.nullable' => 'boolean',
    //                 'columns.*.default' => 'nullable|string',
    //                 'columns.*.unsigned' => 'boolean',
    //                 'columns.*.index' => 'nullable|string|in:index,unique,primary',
    //             ];

    //         case 3: // Form Fields
    //             return [
    //                 'fields' => 'required|array|min:1',
    //                 'fields.*.name' => 'required|string|max:50',
    //                 'fields.*.type' => 'required|string',
    //                 'fields.*.label' => 'required|string|max:100',
    //                 'fields.*.placeholder' => 'nullable|string|max:100',
    //                 'fields.*.frontend_validation' => 'nullable|array',
    //                 'fields.*.backend_validation' => 'nullable|array',
    //             ];

    //         case 4: // Relationships
    //             return [
    //                 'relationships' => 'nullable|array',
    //                 'relationships.*.type' => 'required_with:relationships|string|in:hasOne,hasMany,belongsTo,belongsToMany,hasOneThrough,hasManyThrough',
    //                 'relationships.*.model' => 'required_with:relationships|exists:modules,id',
    //                 'relationships.*.foreign_key' => 'nullable|string',
    //                 'relationships.*.local_key' => 'nullable|string',
    //                 'relationships.*.pivot_table' => 'required_if:relationships.*.type,belongsToMany|string',
    //                 'relationships.*.pivot_columns' => 'nullable|array',
    //                 'relationships.*.pivot_columns.*.name' => 'required_with:relationships.*.pivot_columns|string',
    //                 'relationships.*.pivot_columns.*.type' => 'required_with:relationships.*.pivot_columns|string',
    //             ];

    //         default:
    //             return [];
    //     }
    // }

    /**
     * Generate the module based on the validated data
     */
    public function generateModule(array $data)
    {
        try {
            DB::beginTransaction();
            \Log::info('Starting module generation', ['data' => $data]);

            // 1. Create the module record
            \Log::info('Creating module record');
            $module = $this->createModuleRecord($data);
            \Log::info('Module record created', ['module_id' => $module->id]);

            // 2. Generate DTR
            \Log::info('Generating DTR');
            $this->generateDTR($data);
            \Log::info('DTR generated');

            // 3. Generate Service
            \Log::info('Generating Service');
            $this->generateService($data);
            \Log::info('Service generated');

            // 4. Generate migration file
            \Log::info('Generating migration');
            $migrationName = $this->generateMigration($data);
            \Log::info('Migration generated');

            // 5. Generate model
            \Log::info('Generating model');
            $this->generateModel($data);
            \Log::info('Model generated');

            // 6. Generate controller
            \Log::info('Generating controller');
            $this->generateController($data);
            \Log::info('Controller generated');

            // 7. Generate views
            \Log::info('Generating views');
            $this->generateViews($data);
            \Log::info('Views generated');

            // 8. Generate language files
            \Log::info('Generating language files');
            $this->generateLanguageFiles($data);
            \Log::info('Language files generated');

            // 9. Generate request class
            \Log::info('Generating request class');
            $this->generateRequest($data);
            \Log::info('Request class generated');

            // 10. Generate form component
            \Log::info('Generating form component');
            $this->generateFormComponent($data);
            \Log::info('Form component generated');

            DB::commit();

            // After generating migration file
            \Log::info('Executing migration');
            $this->executeMigration($migrationName, $data['name']);
            \Log::info('Migration executed successfully');

            // 11. Add routes
            \Log::info('Adding routes');
            $this->addRoutes($module, $data);
            \Log::info('Routes added');

            \Log::info('Module generation completed successfully');

            $redirectUrl = url('modules/list');

            return [
                'success' => true,
                'redirect_url' => $redirectUrl,
                'message' => 'Module generated successfully!'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Module generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Create the module record in the database
     */
    private function createModuleRecord(array $data)
    {
        try {
            // Decode translations if they're JSON string
            if (isset($data['translations']) && is_string($data['translations'])) {
                $data['translations'] = json_decode($data['translations'], true);
            }

            // Create module
            $module = $this->model->create([
                'name' => $data['name'],
                'group_id' => $data['group_id'],
                'parent_id' => $data['parent_id'],
                'type' => $data['type'],
                'module_type' => $data['module_type'],
                'description' => $data['description'] ?? null,
                'show_in_menu' => $data['show_in_menu'] ?? false,
                'url' => $data['url'],
                'icon' => $data['icon'],
                'slug' => $data['slug'],
                'sort_order' => $data['sort_order'],
                'readable' => $data['readable'] ?? false,
                'writable' => $data['writable'] ?? false,
                'editable' => $data['editable'] ?? false,
                'deletable' => $data['deletable'] ?? false,
                'added_by' => auth()->id(),
                'updated_by' => auth()->id()
            ]);

            // Create translations
            foreach ($data['translations'] as $locale => $translation) {
                $module->translations()->create([
                    'locale' => $locale,
                    'singular_name' => $translation['singular_name'],
                    'plural_name' => $translation['plural_name']
                ]);
            }

            return $module;
        } catch (\Exception $e) {
            \Log::error('Error creating module record: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function validateBasicInfo(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:50',
            'group_id' => 'required|exists:module_groups,id',
            'parent_id' => 'required|integer',
            'type' => 'required|in:1,2',
            'module_type' => 'required|in:1,2',
            'description' => 'nullable|string|max:200',
            'show_in_menu' => 'boolean',
            'url' => 'required|string|max:50|unique:modules,url',
            'icon' => 'required|string|max:50',
            'slug' => 'required|string|max:50|unique:modules,slug',
            'sort_order' => 'required|integer|min:0',
        ]);
    }

    protected function validateDatabaseStructure(array $data)
    {
        return Validator::make($data, [
            'table_name' => 'required|string|max:50',
            'columns' => 'required|array|min:1',
            'columns.*.name' => 'required|string|max:50',
            'columns.*.type' => 'required|string',
            'columns.*.length' => 'nullable',
            'columns.*.nullable' => 'boolean',
            'columns.*.default' => 'nullable|string',
            'columns.*.index' => 'nullable|string|in:index,unique,primary',
            'soft_deletes' => 'boolean',
            'relationships' => 'nullable|array',
            'relationships.*.type' => 'required_with:relationships|string|in:hasOne,hasMany,belongsTo,belongsToMany',
            'relationships.*.model' => 'required_with:relationships|exists:modules,id',
            'relationships.*.foreign_key' => 'nullable|string',
            'relationships.*.local_key' => 'nullable|string',
        ]);
    }

    protected function validateFormFields(array $data)
    {
        // Ensure boolean fields are properly set for all fields
        if (!empty($data['fields'])) {
            foreach ($data['fields'] as &$field) {
                $field['show_in_table'] = isset($field['show_in_table']) ? (bool)$field['show_in_table'] : true;
                $field['searchable'] = isset($field['searchable']) ? (bool)$field['searchable'] : false;
                $field['sortable'] = isset($field['sortable']) ? (bool)$field['sortable'] : false;
                $field['is_title'] = isset($field['is_title']) ? (bool)$field['is_title'] : false;
            }

            // Set first field options
            $data['fields'][0]['show_in_table'] = true;
            $data['fields'][0]['searchable'] = true;
            $data['fields'][0]['sortable'] = true;
            $data['fields'][0]['is_title'] = true;
        }

        return Validator::make($data, [
            'fields' => 'required|array|min:1',
            'fields.*.name' => 'required|string|max:50',
            'fields.*.type' => 'required|string',
            'fields.*.label' => 'required|string|max:100',
            'fields.*.placeholder' => 'nullable|string|max:100',
            'fields.*.validation' => 'nullable|array',
            'fields.*.show_in_table' => 'boolean',
            'fields.*.searchable' => 'boolean',
            'fields.*.sortable' => 'boolean',
            'fields.*.is_title' => 'boolean',
        ]);
    }

    protected function validateTranslations(array $data)
    {
        $rules = [
            'translations' => 'required|array',
        ];

        foreach (config('app.available_locales', ['en']) as $locale) {
            $rules["translations.{$locale}"] = 'required|array';
            $rules["translations.{$locale}.fields"] = 'required|array';

            // For each field, validate its label as required
            if (isset($data['translations'][$locale]['fields'])) {
                foreach ($data['translations'][$locale]['fields'] as $fieldName => $field) {
                    $rules["translations.{$locale}.fields.{$fieldName}.label"] = 'required|string|max:50';
                    // Placeholder is optional
                    $rules["translations.{$locale}.fields.{$fieldName}.placeholder"] = 'nullable|string|max:50';
                }
            }
        }

        return Validator::make($data, $rules);
    }

    protected function validatePermissions(array $data)
    {
        return Validator::make($data, [
            'readable' => 'boolean',
            'writable' => 'boolean',
            'editable' => 'boolean',
            'deletable' => 'boolean',
            'custom_permissions' => 'nullable|array',
        ]);
    }

    protected function generateMigration(array $data)
    {
        $tableName = $data['table_name'];
        $migrationName = date('Y_m_d_His') . "_create_{$tableName}_table.php";
        $stub = file_get_contents(base_path('stubs/migration.create.stub'));

        // Generate schema
        $schema = $this->generateSchema($data);

        // Replace placeholders
        $migration = str_replace(
            ['{{ class }}', '{{ table }}', '{{ schema_up }}'],
            [
                'Create' . Str::studly($tableName) . 'Table',
                $tableName,
                $schema
            ],
            $stub
        );

        // Save migration file
        $path = database_path('migrations/' . $migrationName);
        file_put_contents($path, $migration);

        return $migrationName;
    }

    protected function generateSchema(array $data)
    {
        $schema = [];

        // Add primary key
        $pkType = $data['primary_key_type'];
        $pkName = $data['primary_key_name'];

        if ($pkType === 'uuid') {
            $schema[] = "\$table->uuid('{$pkName}')->primary();";
        } else {
            $schema[] = "\$table->{$pkType}('{$pkName}');";
        }

        // Add columns
        foreach ($data['columns'] as $column) {
            $line = "\$table->{$column['type']}('{$column['name']}'";

            // Add length/precision if specified
            if (!empty($column['length'])) {
                if (str_contains($column['length'], ',')) {
                    // For decimal, float, etc.
                    list($precision, $scale) = explode(',', $column['length']);
                    $line .= ", $precision, $scale";
                } else {
                    $line .= ", {$column['length']}";
                }
            }
            $line .= ')';

            // Add modifiers
            if (!empty($column['unsigned']) && $column['unsigned']) {
                $line .= '->unsigned()';
            }

            if (!empty($column['nullable']) && $column['nullable']) {
                $line .= '->nullable()';
            }

            if (isset($column['default'])) {
                $default = $column['default'];
                if (in_array($column['type'], ['string', 'text'])) {
                    $default = "'{$default}'";
                }
                $line .= "->default({$default})";
            }

            // Add index
            if (!empty($column['index'])) {
                switch ($column['index']) {
                    case 'unique':
                        $line .= '->unique()';
                        break;
                    case 'index':
                        $line .= '->index()';
                        break;
                }
            }

            $line .= ';';
            $schema[] = $line;
        }

        // Add timestamps if enabled
        if (!empty($data['timestamps'])) {
            $schema[] = "\$table->timestamps();";
        }

        // Add soft deletes if enabled
        if (!empty($data['soft_deletes'])) {
            $schema[] = "\$table->softDeletes();";
        }

        // Add user tracking columns if enabled
        if (!empty($data['user_tracking'])) {
            $schema[] = "\$table->foreignId('added_by')->nullable()->constrained('users');";
            $schema[] = "\$table->foreignId('updated_by')->nullable()->constrained('users');";
        }

        // Add foreign key constraints for relationships
        if (!empty($data['relationships'])) {
            foreach ($data['relationships'] as $relationship) {
                if ($relationship['type'] === 'belongsTo') {
                    $foreignKey = $relationship['foreign_key'];
                    $referencedTable = $this->getTableNameFromModuleId($relationship['model']);
                    $schema[] = "\$table->foreignId('{$foreignKey}')->constrained('{$referencedTable}')->onDelete('cascade');";
                }
            }
        }

        return implode("\n            ", $schema);
    }

    protected function getTableNameFromModuleId($moduleId)
    {
        $module = Module::find($moduleId);
        return Str::snake(Str::pluralStudly($module->name));
    }

    protected function generateModel(array $data)
    {
        $modelName = Str::studly($data['name']);
        $stub = file_get_contents(base_path('stubs/model.stub'));

        // Prepare traits
        $traits = [];
        if (!empty($data['soft_deletes'])) {
            $traits[] = 'use SoftDeletes;';
        }
        if (!empty($data['translations'])) {
            $traits[] = 'use \App\Traits\HasTranslations;';
        }

        // Add our label mapping traits to the existing traits array
        if (!empty($this->modelTraits)) {
            $traits = array_merge($traits, $this->modelTraits);
        }

        // Prepare fillable fields
        $fillable = array_merge(
            array_map(function($column) {
                return "'" . $column['name'] . "'";
            }, $data['columns']),
            ["'added_by'", "'updated_by'"]
        );

        // Prepare casts
        $casts = $this->generateModelCasts($data['fields']);

        // Prepare relationships
        $relationships = $this->generateModelRelationships($data['relationships'] ?? []);

        // Prepare translations configuration if needed
        $translations = $this->generateTranslationsConfig($data['translations'] ?? []);

        // Replace placeholders
        $model = str_replace(
            [
                '{{ namespace }}',
                '{{ class }}',
                '{{ traits }}',
                '{{ table }}',
                '{{ fillable }}',
                '{{ casts }}',
                '{{ relationships }}',
                '{{ translations }}'
            ],
            [
                'App\\Models',
                $modelName,
                implode("\n    ", $traits),
                $data['table_name'],
                implode(",\n        ", $fillable),
                $casts,
                $relationships,
                $translations
            ],
            $stub
        );

        // Save model file
        $path = app_path('Models/' . $modelName . '.php');
        file_put_contents($path, $model);

        return $modelName;
    }

    protected function generateModelCasts(array $fields)
    {
        $casts = [];
        foreach ($fields as $field) {
            $cast = $this->determineFieldCast($field);
            if ($cast) {
                $casts[] = $cast;
            }
        }

        return empty($casts) ? '' : "protected \$casts = [\n        " . implode(",\n        ", $casts) . "\n    ];";
    }

    protected function determineFieldCast(array $field): ?string
    {
        $cast = '';
        switch ($field['type']) {
            case 'multiselect':
                    $cast = "'" . $field['name'] . "' => 'array'";
                break;
            case 'checkbox':
                $cast = "'" . $field['name'] . "' => 'boolean'";
                break;
            case 'radio':
                // Check first option's value type to determine cast
                $castType = isset($field['options'][0]) && is_numeric($field['options'][0]['value']) ? 'integer' : 'string';
                $cast = "'" . $field['name'] . "' => '{$castType}'";
                break;
            case 'date':
                $cast = "'" . $field['name'] . "' => 'date'";
                break;
            case 'datetime':
                $cast = "'" . $field['name'] . "' => 'datetime'";
                break;
            case 'password':
                $cast = "'" . $field['name'] . "' => 'hashed'";
                break;
            // case 'file':
            //     $cast = "'" . $field['name'] . "' => 'string'";
            //     break;
            // case 'image':
            //     $cast = "'" . $field['name'] . "' => 'string'";
            //     break;
            // case 'video':
            //     $cast = "'" . $field['name'] . "' => 'string'";
            //     break;
            // case 'color':
            //     $cast = "'" . $field['name'] . "' => 'string'";
            //     break;
            // case 'range':
            //     $cast = "'" . $field['name'] . "' => 'array'";
            //     break;
            // case 'textarea':
            //     $cast = "'" . $field['name'] . "' => 'string'";
            //     break;
            // case 'wysiwyg':
            //     $cast = "'" . $field['name'] . "' => 'string'";
            //     break;
        }
        return $cast;
    }

    protected function generateModelRelationships(array $relationships)
    {
        if (empty($relationships)) {
            return '';
        }

        $methods = [];
        foreach ($relationships as $relationship) {
            $targetModel = Str::studly(Str::singular($this->getTableNameFromModuleId($relationship['model'])));
            $method = $this->generateRelationshipMethod($relationship, $targetModel);
            $methods[] = $method;
        }

        return "\n    " . implode("\n\n    ", $methods);
    }

    protected function generateRelationshipMethod(array $relationship, string $targetModel)
    {
        $methodName = Str::camel($targetModel);
        $foreignKey = $relationship['foreign_key'] ?? null;
        $localKey = $relationship['local_key'] ?? null;

        switch ($relationship['type']) {
            case 'hasOne':
                return "public function {$methodName}()\n    {\n        return \$this->hasOne({$targetModel}::class" .
                       ($foreignKey ? ", '{$foreignKey}'" : '') .
                       ($localKey ? ", '{$localKey}'" : '') . ");\n    }";

            case 'hasMany':
                return "public function " . Str::plural($methodName) . "()\n    {\n        return \$this->hasMany({$targetModel}::class" .
                       ($foreignKey ? ", '{$foreignKey}'" : '') .
                       ($localKey ? ", '{$localKey}'" : '') . ");\n    }";

            case 'belongsTo':
                return "public function {$methodName}()\n    {\n        return \$this->belongsTo({$targetModel}::class" .
                       ($foreignKey ? ", '{$foreignKey}'" : '') .
                       ($localKey ? ", '{$localKey}'" : '') . ");\n    }";

            case 'belongsToMany':
                $pivotTable = $relationship['pivot_table'];
                $pivotColumns = !empty($relationship['pivot_columns']) ?
                    "->withPivot('" . implode("', '", array_column($relationship['pivot_columns'], 'name')) . "')" : '';

                return "public function " . Str::plural($methodName) . "()\n    {\n        return \$this->belongsToMany({$targetModel}::class, '{$pivotTable}'" .
                       ($foreignKey ? ", '{$foreignKey}'" : '') .
                       ($localKey ? ", '{$localKey}'" : '') . ")" .
                       $pivotColumns . ";\n    }";
        }
    }

    protected function generateTranslationsConfig(array $translations)
    {
        if (empty($translations)) {
            return '';
        }

        $translatableFields = array_keys($translations[array_key_first($translations)]['fields']);
        return "\n    public \$translatable = [\n        '" .
               implode("',\n        '", $translatableFields) .
               "'\n    ];";
    }

    protected function generateController(array $data)
    {
        $modelName = Str::studly($data['name']);
        $controllerName = $modelName . 'Controller';
        $stub = file_get_contents(base_path('stubs/controller.stub'));

        // Generate request class name
        $requestClass = $modelName . 'Request';

        // Generate service class name
        $serviceClass = $modelName . 'Service';

        // Generate module name (route folder name)
        $moduleName = Str::kebab($data['name']);

        // Generate custom methods based on relationships
        $customMethods = $this->generateControllerCustomMethods($data['relationships'] ?? []);

        // Replace placeholders
        $controller = str_replace(
            [
                '{{ namespace }}',
                '{{ class }}',
                '{{ request_class }}',
                '{{ service_class }}',
                '{{ module_name }}',
                '{{ custom_methods }}'
            ],
            [
                'App\\Http\\Controllers',
                $controllerName,
                $requestClass,
                $serviceClass,
                $moduleName,
                $customMethods
            ],
            $stub
        );

        // Save controller file
        $path = app_path('Http/Controllers/' . $controllerName . '.php');
        file_put_contents($path, $controller);

        return $controllerName;
    }

    protected function generateControllerCustomMethods(array $relationships)
    {
        if (empty($relationships)) {
            return '';
        }

        $methods = [];
        foreach ($relationships as $relationship) {
            if ($relationship['type'] === 'belongsTo') {
                $targetModel = Str::studly(Str::singular($this->getTableNameFromModuleId($relationship['model'])));
                $methods[] = $this->generateGetParentMethod($targetModel);
            }
        }

        return !empty($methods) ? "\n    " . implode("\n\n    ", $methods) : '';
    }

    protected function generateGetParentMethod($targetModel)
    {
        $methodName = 'get' . Str::plural($targetModel);
        $variableName = Str::camel(Str::plural($targetModel));

        return "public function {$methodName}()\n    {
            \${$variableName} = \$this->moduleService->get{$targetModel}List();
            return \${$variableName} != null
                ? \$this->sendSuccessResponse('Data found', ['" . Str::snake($variableName) . "' => \${$variableName}])
                : \$this->sendErrorResponse('Data not found', 404);
        }";
    }

    protected function generateRequest(array $data)
    {
        $modelName = Str::studly($data['name']);
        $requestName = $modelName . 'Request';
        $stub = file_get_contents(base_path('stubs/request.stub'));

        // Generate validation rules
        $rules = $this->generateValidationRules($data['fields'], $data['table_name']);
        $attributes = $this->generateValidationAttributes($data['fields']);
        $messages = $this->generateValidationMessages($data['fields']);

        // Generate prepare for validation method for boolean/checkbox fields
        $prepareForValidation = [];
        foreach ($data['fields'] as $field) {
            if (in_array($field['type'], ['checkbox', 'boolean'])) {
                $prepareForValidation[] = "            '{$field['name']}' => \$this->boolean('{$field['name']}'),";
            }
        }

        // Replace placeholders first
        $requestContent = str_replace(
            [
                '{{ namespace }}',
                '{{ class }}',
                '{{ rules }}',
                '{{ attributes }}',
                '{{ messages }}'
            ],
            [
                'App\\Http\\Requests',
                $requestName,
                $this->formatArray($rules['rules']),
                $this->formatArray($rules['attributes']),
                $this->formatArray($rules['messages'])
            ],
            $stub
        );

        // Add prepareForValidation method if we have any boolean fields
        if (!empty($prepareForValidation)) {
            // Find the position after the class opening brace
            $pos = strpos($requestContent, "class {$requestName} extends FormRequest\n{") + strlen("class {$requestName} extends FormRequest\n{");

            // Insert the prepareForValidation method
            $prepareMethod = "\n    protected function prepareForValidation()\n    {\n        \$this->merge([\n"
                . implode("\n", $prepareForValidation) . "\n        ]);\n    }\n";

            $requestContent = substr_replace($requestContent, $prepareMethod, $pos, 0);
        }

        // Save request file
        $path = app_path('Http/Requests/' . $requestName . '.php');
        file_put_contents($path, $requestContent);

        return $requestName;
    }

    protected function generateValidationRules(array $fields, $moduleName)
    {
        $rules = [];
        $attributes = [];
        $messages = [];

        // Add id validation rule for updates
        $rules['id'] = "sometimes|required|exists:{$moduleName},id";
        $messages['id.required'] = 'The :attribute field is required';
        $messages['id.exists'] = 'The selected :attribute is invalid';

        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $attributes[$fieldName] = $field['label'];

            $validationRules = [];

            // Add type-specific validation
            switch ($field['type']) {
                case 'multiselect':
                    $validationRules[] = 'array';
                    break;
                case 'radio':
                // case 'select':
                    if (!empty($field['options'])) {
                        $values = array_column($field['options'], 'value');
                        $validationRules[] = 'in:' . implode(',', $values);
                    }
                    break;
            }

            // Add backend validation rules
            if (!empty($field['backend_validation'])) {
                foreach ($field['backend_validation'] as $rule) {
                    if ($rule === 'required') {
                        $validationRules[] = 'required';
                        $messages["{$fieldName}.required"] = 'The :attribute field is required';
                    } else if ($rule === 'string') {
                        $validationRules[] = 'string';
                        $messages["{$fieldName}.string"] = 'The :attribute must be a string';
                    } else if ($rule === 'max') {
                        $max = $field['backend_params']['max'] ?? '255';
                        $validationRules[] = "max:{$max}";
                        $messages["{$fieldName}.max"] = "The :attribute may not be greater than {$max} characters";
                    } else if ($rule === 'integer') {
                        $validationRules[] = 'integer';
                        $messages["{$fieldName}.integer"] = 'The :attribute must be an integer';
                    } else if ($rule === 'boolean') {
                        $validationRules[] = 'boolean';
                        $messages["{$fieldName}.boolean"] = 'The :attribute field must be true or false';
                    } else if ($rule === 'unique') {
                        $validationRules[] = 'unique:'.$moduleName.','.$fieldName;
                        $messages["{$fieldName}.unique"] = 'The :attribute has already been taken';
                    } else {
                        $validationRules[] = $rule;
                    }
                }
            }

            // Add nullable if not required
            if (!in_array('required', $validationRules)) {
                $validationRules[] = 'nullable';
            }

            $rules[$fieldName] = implode('|', $validationRules);
        }

        return [
            'rules' => $rules,
            'attributes' => $attributes,
            'messages' => $messages
        ];
    }

    protected function formatRulesForStub(array $rules)
    {
        $formattedRules = [];

        foreach ($rules as $field => $fieldRules) {
            $rulesString = implode('|', $fieldRules);
            $formattedRules[] = "'{$field}' => '{$rulesString}'";
        }

        return implode(",\n            ", $formattedRules);
    }

    protected function generateValidationAttributes(array $fields)
    {
        $attributes = [];
        foreach ($fields as $field) {
            $attributes[$field['name']] = $field['label'];
        }
        return $attributes;
    }

    protected function generateValidationMessages(array $fields)
    {
        $messages = [];
        foreach ($fields as $field) {
            if (!empty($field['backend_validation'])) {
                foreach ($field['backend_validation'] as $rule) {
                    $messages[$field['name'] . '.' . $rule] = "The {$field['label']} field " . $this->getValidationMessage($rule);
                }
            }
        }
        return $messages;
    }

    protected function getValidationMessage($rule)
    {
        $messages = [
            'required' => 'is required.',
            'email' => 'must be a valid email address.',
            'min' => 'must be at least :min.',
            'max' => 'must not be greater than :max.',
            'unique' => 'has already been taken.',
            'numeric' => 'must be a number.',
            'date' => 'must be a valid date.',
            'file' => 'must be a file.',
            'mimes' => 'must be a file of type: :values.',
            'in' => 'must be one of the following types: :values',
            'array' => 'must be an array.',
        ];

        return $messages[$rule] ?? 'is invalid.';
    }

    protected function formatArray(array $array, $indent = 2)
    {
        $lines = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = "['" . implode("', '", $value) . "']";
            } else {
                $value = "'" . addslashes($value) . "'";
            }
            $lines[] = str_repeat(' ', $indent * 4) . "'" . $key . "' => " . $value;
        }
        return implode(",\n", $lines);
    }

    protected function generateViews(array $data)
    {
        $viewPath = resource_path('views/backend/' . Str::kebab($data['name']));

        // Create view directory if it doesn't exist
        if (!file_exists($viewPath)) {
            mkdir($viewPath, 0755, true);
        }

        // Generate index view
        $indexStub = file_get_contents(base_path('stubs/views/index.stub'));

        $columns = $this->generateDataTableColumns($data['fields'], Str::studly($data['name']));

        $index = str_replace(
            [
                '{{ studly_module_name }}',
                '{{ module_name }}',
                '{{ view_path }}',
                '{{ table_id }}',
                '{{ columns }}',
                '{{ default_order }}',
                '{{ datatable_columns }}',
                '{{ save_url }}',
                '{{ update_url }}',
                '{{ delete_url }}',
                '{{ get_single_url }}',
                '{{ archived_url }}',
                '{{ all_data_url }}',
                '{{ restore_url }}'
            ],
            [
                Str::studly($data['name']),
                Str::camel($data['name']),
                'backend.' . Str::kebab($data['name']),
                Str::kebab($data['name']) . '-table',
                $columns,
                "[1, 'desc']", // Default order by created_at desc
                $this->generateDataTableColumns($data['fields'], Str::studly($data['name'])),
                "route('" . Str::kebab($data['name']) . ".store')",
                "route('" . Str::kebab($data['name']) . ".update')",
                "route('" . Str::kebab($data['name']) . ".destroy')",
                "route('" . Str::kebab($data['name']) . ".show')",
                "route('" . Str::kebab($data['name']) . ".archived')",
                "route('" . Str::kebab($data['name']) . ".index')",
                "route('" . Str::kebab($data['name']) . ".restore')"
            ],
            $indexStub
        );

        file_put_contents($viewPath . '/' . Str::kebab($data['name']) . '.blade.php', $index);

        // Generate form view
        $formStub = file_get_contents(base_path('stubs/views/form.stub'));
        $form = str_replace(
            [
                // '{{ formId }}',
                // '{{ btnId }}',
                '{{ form_fields }}'
            ],
            [
                // $data['name'] . 'Form',
                // $data['name'] . 'Btn',
                app(ViewGeneratorService::class)->generateFormFields($data['fields'], Str::studly($data['name']))
            ],
            $formStub
        );

        file_put_contents($viewPath . '/form.blade.php', $form);

        return true;
    }

    protected function generateDataTableColumns(array $fields, string $moduleName)
    {
        $columns = [];
        foreach ($fields as $field) {
            if (!empty($field['show_in_table'])) {
                $column = [
                    'data' => $field['name'],
                    'title' => __("{$moduleName}/{$moduleName}.table_headers.{$field['name']}"),
                ];

                // Add sortable property if specified
                if (!empty($field['sortable'])) {
                    $column['orderable'] = true;
                } else {
                    $column['orderable'] = false;
                }

                $columns[] = $column;
            }
        }

        // Add created_at and action columns
        $columns[] = ['data' => 'created_at', 'title' => __("{$moduleName}/{$moduleName}.table_headers.created_at"), 'orderable' => true];
        $columns[] = ['data' => 'action', 'title' => __("{$moduleName}/{$moduleName}.table_headers.actions"), 'orderable' => false, 'responsivePriority' => 1];

        $formattedColumns = array_map(function($col) {
            $orderableStr = isset($col['orderable']) && !$col['orderable'] ? ", 'orderable' => false" : "";
            return "['data' => '{$col['data']}', 'title' => __('{$col['title']}'){$orderableStr}]";
        }, $columns);

        return implode(",\n            ", $formattedColumns);
    }

    protected function generateDTR(array $data)
    {
        $modelName = Str::studly($data['name']);
        $dtrName = $modelName . 'DTR';
        $stub = file_get_contents(base_path('stubs/dtr.stub'));

        // Generate DTR content
        $content = str_replace(
            [
                '{{ namespace }}',
                '{{ class }}',
                '{{ array_content }}'
            ],
            [
                'App\\Http\\Resources',
                $dtrName,
                $this->generateDTRArrayContent($data['fields'], $data['special_actions'] ?? [])
            ],
            $stub
        );

        // Create DTR file
        $dtrPath = app_path('Http/Resources/' . $dtrName . '.php');
        file_put_contents($dtrPath, $content);

        return $dtrName;
    }

    protected function generateDTRArrayContent(array $fields, array $specialActions = [])
    {
        $arrayContent = [];
        $arrayContent[] = "'id' => \$this->id,";

        foreach ($fields as $field) {
            if (empty($field['show_in_table'])) {
                continue;
            }

            $fieldName = $field['name'];
            // Convert field_name to fieldName for accessor
            $camelCaseField = Str::camel($fieldName);

            switch ($field['type']) {
                case 'radio':
                    // Generate the options array for mapping
                    $optionsArray = [];
                    if (isset($field['options'])) {
                        foreach ($field['options'] as $option) {
                            $optionsArray[] = "            '{$option['value']}' => '{$option['label']}'";
                        }
                    }

                    // Add the mapping property to the model
                    $this->modelTraits[] = "\n    protected \${$fieldName}Labels = [\n" . implode(",\n", $optionsArray) . "\n    ];";

                    // Add accessor method using camelCase
                    $this->modelTraits[] = "
    public function get{$camelCaseField}LabelAttribute()
    {
        return \$this->{$fieldName}Labels[\$this->{$fieldName}] ?? 'Unknown';
    }";

                    // Use snake_case in DTR for consistency
                    $arrayContent[] = "'{$fieldName}' => \$this->{$fieldName}_label,";
                    break;

                case 'checkbox':
                case 'boolean':
                    // Add accessor method using camelCase
                    $this->modelTraits[] = "
                    public function get{$camelCaseField}LabelAttribute()
                    {
                        return \$this->{$fieldName} ? 'Yes' : 'No';
                    }";

                    // Use snake_case in DTR
                    $arrayContent[] = "'{$fieldName}' => \$this->{$fieldName}_label,";
                    break;

                case 'text':
                    if (isset($field['is_title']) && $field['is_title']) {
                        $arrayContent[] = "'{$fieldName}' => \"<strong>{\$this->{$fieldName}}</strong>\",";
                    } else {
                        $arrayContent[] = "'{$fieldName}' => \$this->{$fieldName},";
                    }
                    break;

                case 'select':
                    // Similar to radio, but might need to handle multiple values
                    if (isset($field['options'])) {
                        $optionsArray = [];
                        foreach ($field['options'] as $option) {
                            $optionsArray[] = "            '{$option['value']}' => '{$option['label']}'";
                        }

                        $this->modelTraits[] = "\n    protected \${$fieldName}Labels = [\n" . implode(",\n", $optionsArray) . "\n    ];";

                        $this->modelTraits[] = "
    public function get{$fieldName}LabelAttribute()
    {
        return \$this->{$fieldName}Labels[\$this->{$fieldName}] ?? 'Unknown';
    }";

                        $arrayContent[] = "'{$fieldName}' => \$this->{$fieldName}_label,";
                    } else {
                        $arrayContent[] = "'{$fieldName}' => \$this->{$fieldName},";
                    }
                    break;

                case 'multiselect':
                    // Handle arrays of values
                    if (isset($field['options'])) {
                        $optionsArray = [];
                        foreach ($field['options'] as $option) {
                            $optionsArray[] = "            '{$option['value']}' => '{$option['label']}'";
                        }

                        $this->modelTraits[] = "\n    protected \${$fieldName}Labels = [\n" . implode(",\n", $optionsArray) . "\n    ];";

                        $this->modelTraits[] = "
    public function get{$fieldName}LabelAttribute()
    {
        \$values = \$this->{$fieldName};
        if (is_string(\$values)) {
            \$values = json_decode(\$values, true);
        }
        if (!is_array(\$values)) return '';

        \$labels = [];
        foreach (\$values as \$value) {
            \$labels[] = \$this->{$fieldName}Labels[\$value] ?? 'Unknown';
        }
        return implode(', ', \$labels);
    }";

                        $arrayContent[] = "'{$fieldName}' => \$this->{$fieldName}_label,";
                    } else {
                        $arrayContent[] = "'{$fieldName}' => \$this->{$fieldName},";
                    }
                    break;

                case 'date':
                    $arrayContent[] = "'{$fieldName}' => dateFormat(\$this->{$fieldName}) ?? null,";
                    break;

                case 'time':
                    $arrayContent[] = "'{$fieldName}' => timeFormat(\$this->{$fieldName}) ?? null,";
                    break;

                case 'timestamp':
                case 'datetime':
                    $arrayContent[] = "'{$fieldName}' => dateTimeFormat(\$this->{$fieldName}) ?? null,";
                    break;

                case 'file':
                    $arrayContent[] = "'{$fieldName}' => \$this->getFileUrl('{$fieldName}'),";
                    break;

                default:
                    $arrayContent[] = "'{$fieldName}' => \$this->{$fieldName},";
            }
        }

        // Add created_at and action columns
        $arrayContent[] = "'created_at' => dateTimeFormat(\$this->created_at) ?? null,";

        // Check if module has special actions
        if (!empty($specialActions)) {
            $action = $specialActions[0];
            $arrayContent[] = "'action' => getResourceActionButtons(self::\$moduleId, \$this, true, '{$action['label']}', '{$action['name']}', '{$action['icon']}', false),";
        } else {
            $arrayContent[] = "'action' => getResourceActionButtons(self::\$moduleId, \$this),";
        }

        return implode("\n            ", $arrayContent);
    }

    protected function generateService(array $data)
    {
        $modelName = Str::studly($data['name']);
        $serviceName = $modelName . 'Service';
        $stub = file_get_contents(base_path('stubs/service.stub'));
        $dtrName = $modelName . 'DTR';

        // Generate search columns
        $searchColumns = [];
        foreach ($data['fields'] as $field) {
            if (!empty($field['searchable'])) {
                $searchColumns[] = "'{$field['name']}'";
            }
        }
        // Always include created_at in search columns
        $searchColumns[] = "'created_at'";

        // Replace placeholders
        $service = str_replace(
            [
                '{{ namespace }}',
                '{{ class }}',
                '{{ model }}',
                '{{ dtr_class }}',
                '{{ search_columns }}'
            ],
            [
                'App\\Services',
                $serviceName,
                $modelName,
                $dtrName,
                implode(",\n            ", $searchColumns)
            ],
            $stub
        );

        // Save service file
        $path = app_path('Services/' . $serviceName . '.php');
        file_put_contents($path, $service);

        return $serviceName;
    }

    protected function generateLanguageFiles(array $data)
    {
        app(ViewGeneratorService::class)->generateTranslationFiles($data);
        // $modulePath = Str::studly($data['name']);
        // $fileName = Str::lower($data['name']);

        // foreach ($data['translations'] as $locale => $translation) {
        //     $langPath = resource_path(lang_path($locale) . "/{$modulePath}");
        //     $langFile = $langPath . "/{$fileName}.php";

        //     // Create locale directory if it doesn't exist
        //     if (!file_exists($langPath)) {
        //         mkdir($langPath, 0755, true);
        //     }

        //     $translations = [
        //         'singular_name' => $translation['singular_name'],
        //         'plural_name' => $translation['plural_name'],
        //         'fields' => [],
        //         'messages' => [
        //             'created' => ':name created successfully',
        //             'updated' => ':name updated successfully',
        //             'deleted' => ':name archived successfully',
        //             'restored' => ':name restored successfully',
        //         ],
        //         'validation' => $this->generateValidationMessages($data['fields'])
        //     ];

        //     // Add field translations
        //     foreach ($translation['fields'] as $field => $label) {
        //         $translations['fields'][$field] = $label;
        //     }

        //     // Generate module language file
        //     $content = "<?php\n\nreturn " . var_export($translations, true) . ";\n";
        //     file_put_contents($langFile, $content);
        // }

        // return true;
    }

    protected function addRoutes($module, array $data)
    {
        try {
            $moduleName = Str::camel($data['name']);
            $controllerClass = 'App\\Http\\Controllers\\' . Str::studly($moduleName) . 'Controller';

            // Generate route content
            $routeContent = "\n        // {$moduleName} Start\n" .
                $this->generateRouteContent($module, $data) .
                "\n        // {$moduleName} End\n";

            // Read the generated routes file
            $routesFile = base_path('routes/module-generated.php');
            $currentContent = file_get_contents($routesFile);

            // Find the auto-generated routes section
            $startMarker = '// ** Auto Generated Routes Start ** //';
            $endMarker = '// ** Auto Generated Routes End ** //';

            $startPos = strpos($currentContent, $startMarker);
            $endPos = strpos($currentContent, $endMarker);

            if ($startPos !== false && $endPos !== false) {
                // Find the position after the middleware group opening
                $middlewareGroupStart = strpos($currentContent, 'Route::middleware([\'auth\', \'check.module.permission\'])->group(function () {');
                if ($middlewareGroupStart !== false) {
                    // Calculate positions for insertion
                    $insertPos = $middlewareGroupStart + strlen('Route::middleware([\'auth\', \'check.module.permission\'])->group(function () {');

                    // Insert new routes while preserving existing routes
                    $newContent = substr($currentContent, 0, $insertPos) .
                                 $routeContent .
                                 substr($currentContent, $insertPos);

                    file_put_contents($routesFile, $newContent);
                } else {
                    \Log::error('Could not find middleware group in module-generated.php');
                    throw new \Exception('Could not find middleware group in module-generated.php');
                }
            } else {
                \Log::error('Could not find auto-generated routes markers in module-generated.php');
                throw new \Exception('Could not find auto-generated routes markers in module-generated.php');
            }

            \Log::info('Routes added successfully', [
                'module' => $moduleName,
                'routes' => $routeContent,
                'controller' => $controllerClass
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Error adding routes: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function generateRouteContent($module, $data)
    {
        $moduleName = Str::kebab($data['name']);
        $controllerClass = 'App\\Http\\Controllers\\' . Str::studly($moduleName) . 'Controller';

        $routes = [];
        $routes[] = "        Route::get('/{$moduleName}/list', [{$controllerClass}::class, 'index'])->name('all-{$moduleName}');";
        $routes[] = "        Route::get('/{$moduleName}/get', [{$controllerClass}::class, 'getAll'])->name('all-{$moduleName}-records');";
        $routes[] = "        Route::post('/{$moduleName}/save', [{$controllerClass}::class, 'store'])->name('save-{$moduleName}');";
        $routes[] = "        Route::post('{$moduleName}/getSingle', [{$controllerClass}::class, 'getSingle'])->name('get-single-{$moduleName}');";
        $routes[] = "        Route::post('{$moduleName}/update', [{$controllerClass}::class, 'update'])->name('update-{$moduleName}');";
        $routes[] = "        Route::post('{$moduleName}/delete', [{$controllerClass}::class, 'delete'])->name('delete-{$moduleName}');";
        $routes[] = "        Route::get('{$moduleName}/archived-{$moduleName}-records', [{$controllerClass}::class, 'getArchivedItems'])->name('archived-{$moduleName}-records');";
        $routes[] = "        Route::post('{$moduleName}/restore-{$moduleName}', [{$controllerClass}::class, 'restoreModule'])->name('restore-{$moduleName}');";

        return implode("\n", $routes);
    }

    // extra methods, keeping for later review and implementation
    protected function extra_generateValidationMessages(array $fields)
    {
        $messages = [];
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if (!empty($field['validation'])) {
                foreach ($field['validation'] as $rule) {
                    switch ($rule) {
                        case 'required':
                            $messages["{$fieldName}.required"] = 'The :attribute field is required';
                            break;
                        case 'email':
                            $messages["{$fieldName}.email"] = 'The :attribute must be a valid email address';
                            break;
                        case 'min':
                            $min = $field['validation_params']['min'] ?? '';
                            $messages["{$fieldName}.min"] = "The :attribute must be at least {$min} characters";
                            break;
                        case 'max':
                            $max = $field['validation_params']['max'] ?? '';
                            $messages["{$fieldName}.max"] = "The :attribute may not be greater than {$max} characters";
                            break;
                        case 'numeric':
                            $messages["{$fieldName}.numeric"] = 'The :attribute must be a number';
                            break;
                        case 'unique':
                            $messages["{$fieldName}.unique"] = 'The :attribute has already been taken';
                            break;
                    }
                }
            }
        }
        return $messages;
    }

    protected function generateFormComponent(array $data)
    {
        $modelName = Str::studly($data['name']);
        $componentName = $modelName . 'Form';
        $stub = file_get_contents(base_path('stubs/formComponent.stub'));

        // Replace placeholders
        $component = str_replace(
            [
                '{{ namespace }}',
                '{{ class }}',
                '{{ module_name }}',
                '{{ view_path }}'
            ],
            [
                'App\\View\\Components',
                $modelName,
                Str::camel($modelName),
                'backend.' . Str::kebab($data['name'])
            ],
            $stub
        );

        // Save component file
        $path = app_path('View/Components/' . $componentName . '.php');
        file_put_contents($path, $component);

        return $componentName;
    }


    protected function executeMigration($migrationName, $moduleName)
    {
        try {
            \Log::info('Starting migration execution for migration: ' . $migrationName);

            $migrationPath = database_path('migrations/' . $migrationName);

            if (!file_exists($migrationPath)) {
                throw new \Exception('Migration file not found: ' . $migrationPath);
            }

            // Include the migration file
            require_once $migrationPath;

            // Get the migration class name from the file, removing .php extension
            $migrationName = str_replace('.php', '', $migrationName);
            $className = Str::studly(implode('_', array_slice(explode('_', $migrationName), 4)));

            // Create an instance of the migration
            $migration = new $className();

            // Run the migration
            \Log::info('Executing up() method for migration: ' . $className);

            $migration->up();
            return true;
        } catch (\PDOException $e) {
            \Log::error('Database error during migration', [
                'module' => $moduleName,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            // Handle duplicate table error
            if ($e->getCode() == '42S01') { // Table already exists
                throw new \Exception('Table already exists for this module');
            }

            throw new \Exception('Database error during migration: ' . $e->getMessage());
        } catch (\Throwable $e) {
            \Log::error('Error during migration execution', [
                'module' => $moduleName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception('Failed to execute migration: ' . $e->getMessage());
        }
    }

    // Add other protected methods for generating models, controllers, views, etc.
}
