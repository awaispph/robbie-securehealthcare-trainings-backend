<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestS3Storage extends Command
{
    protected $signature = 'storage:test-s3';
    protected $description = 'Test S3 storage connectivity and operations';

    public function handle()
    {
        $this->info('Testing S3 Storage...');
        $this->newLine();

        // Check configuration
        $this->info('Configuration:');
        $this->table(
            ['Key', 'Value'],
            [
                ['FILESYSTEM_DISK', config('filesystems.default')],
                ['AWS_DEFAULT_REGION', config('filesystems.disks.s3.region')],
                ['AWS_BUCKET', config('filesystems.disks.s3.bucket') ?: '(not set)'],
                ['AWS_ACCESS_KEY_ID', config('filesystems.disks.s3.key') ? '***' . substr(config('filesystems.disks.s3.key'), -4) : '(not set)'],
            ]
        );
        $this->newLine();

        if (!config('filesystems.disks.s3.key') || !config('filesystems.disks.s3.bucket')) {
            $this->error('S3 credentials not configured. Please set AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, and AWS_BUCKET in .env');
            return 1;
        }

        $testFile = 'test/s3-test-' . time() . '.txt';
        $testContent = 'S3 Test File - ' . now()->toDateTimeString();

        try {
            // Test 1: Write file
            $this->info('1. Testing file upload...');
            Storage::disk('s3')->put($testFile, $testContent);
            $this->info('   ✓ File uploaded successfully');

            // Test 2: Check file exists
            $this->info('2. Testing file exists...');
            if (Storage::disk('s3')->exists($testFile)) {
                $this->info('   ✓ File exists check passed');
            } else {
                throw new \Exception('File does not exist after upload');
            }

            // Test 3: Read file
            $this->info('3. Testing file read...');
            $content = Storage::disk('s3')->get($testFile);
            if ($content === $testContent) {
                $this->info('   ✓ File content matches');
            } else {
                throw new \Exception('File content mismatch');
            }

            // Test 4: Get URL
            $this->info('4. Testing URL generation...');
            $url = Storage::disk('s3')->temporaryUrl($testFile, now()->addMinutes(5));
            $this->info('   ✓ Temporary URL: ' . $url);

            // Test 5: Delete file
            $this->info('5. Testing file deletion...');
            Storage::disk('s3')->delete($testFile);
            if (!Storage::disk('s3')->exists($testFile)) {
                $this->info('   ✓ File deleted successfully');
            } else {
                throw new \Exception('File still exists after deletion');
            }

            $this->newLine();
            $this->info('✓ All S3 tests passed successfully!');
            return 0;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('✗ S3 Test Failed: ' . $e->getMessage());
            
            // Cleanup on failure
            try {
                Storage::disk('s3')->delete($testFile);
            } catch (\Exception $cleanupError) {
                // Ignore cleanup errors
            }
            
            return 1;
        }
    }
}
