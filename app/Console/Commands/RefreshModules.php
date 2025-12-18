<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Module;
use App\Services\ModuleDestroyerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefreshModules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:refresh {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh all non-protected modules';

    /**
     * The list of protected module slugs that should not be deleted.
     *
     * @var array
     */
    protected const PROTECTED_SLUGS = [
        'all-users',
        'all-user-role',
        'all-designation',
        'all-user-document',
        'general-settings',
        'all-email-template',
        // Add any other protected modules here
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('This will delete all non-protected modules. Are you sure you want to continue?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info('Starting module refresh...');
        $this->info('Finding modules to delete...');

        // Get all modules except protected ones
        $modules = Module::whereNotIn('slug', self::PROTECTED_SLUGS)->get();

        if ($modules->isEmpty()) {
            $this->info('No non-protected modules found to delete.');
            return 0;
        }

        $this->info('Found ' . $modules->count() . ' modules to delete.');

        // Start a transaction
        $connection = DB::connection();
        $pdo = $connection->getPdo();

        try {
            // Start transaction at PDO level
            $pdo->beginTransaction();

            $bar = $this->output->createProgressBar($modules->count());
            $bar->start();

            $deletedModules = [];
            $failedModules = [];

            foreach ($modules as $module) {
                try {
                    $destroyer = new ModuleDestroyerService($module);
                    $destroyer->setInTransaction(true);
                    $result = $destroyer->destroy(true); // true to delete children

                    if ($result['success']) {
                        $deletedModules[] = $module->name;
                    } else {
                        $failedModules[] = "{$module->name} ({$result['error']})";
                    }
                } catch (\Exception $e) {
                    $failedModules[] = "{$module->name} ({$e->getMessage()})";
                    Log::error("Error deleting module during refresh", [
                        'module' => $module->name,
                        'error' => $e->getMessage()
                    ]);
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            if (!empty($failedModules)) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $this->error('Failed to delete some modules:');
                foreach ($failedModules as $module) {
                    $this->line(" - $module");
                }

                return 1;
            }

            // Commit the transaction
            if ($pdo->inTransaction()) {
                $pdo->commit();
            }

            $this->info('Successfully deleted modules:');
            foreach ($deletedModules as $module) {
                $this->line(" - $module");
            }

            $this->info('Module refresh completed successfully!');
            return 0;

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $this->error('Error during module refresh: ' . $e->getMessage());
            Log::error('Error during module refresh', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }
    }
}
