<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestMiddleware extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:middleware';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all middleware tests';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Running middleware tests...');
        
        // Check if test files exist
        $featureTestPath = base_path('tests/Feature/ApiTest.php');
        $unitTestPath = base_path('tests/Unit/MiddlewareTest.php');
        
        if (!file_exists($featureTestPath)) {
            $this->error("Feature test file not found: $featureTestPath");
            $this->info("Creating feature test file...");
            $this->call('make:test', ['name' => 'ApiTest']);
        }
        
        if (!file_exists($unitTestPath)) {
            $this->error("Unit test file not found: $unitTestPath");
            $this->info("Creating unit test file...");
            $this->call('make:test', ['name' => 'MiddlewareTest', '--unit' => true]);
        }
        
        // Run the feature tests
        $this->info('Running feature tests...');
        $featureResult = $this->runTest('ApiTest');
        
        // Run the unit tests
        $this->info('Running unit tests...');
        $unitResult = $this->runTest('MiddlewareTest');
        
        // Display summary
        $this->newLine();
        $this->info('Test Summary:');
        $this->info('-------------');
        $this->info('Feature Tests: ' . ($featureResult ? '✓ PASSED' : '✗ FAILED'));
        $this->info('Unit Tests: ' . ($unitResult ? '✓ PASSED' : '✗ FAILED'));
        
        return $featureResult && $unitResult ? 0 : 1;
    }
    
    /**
     * Run a specific test class
     *
     * @param string $testClass
     * @return bool
     */
    protected function runTest(string $testClass)
    {
        $command = 'php artisan test --filter=' . $testClass;
        $this->comment("Executing: $command");
        
        $result = 0;
        passthru($command, $result);
        
        return $result === 0;
    }
}