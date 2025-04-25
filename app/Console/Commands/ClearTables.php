<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearTables extends Command
{
    protected $signature = 'tables:clear 
                            {tables* : The tables to clear}
                            {--reset-checkpoint-delivery : Reset all checkpoint deliveryID values to null}';
    protected $description = 'Clear specific tables in the database and optionally reset checkpoint deliveryID values';

    public function handle()
    {
        $tables = $this->argument('tables');
        $connection = DB::connection()->getDriverName();
        
        // Disable foreign key constraints based on database driver
        if ($connection === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif ($connection === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            $this->warn("Foreign key check disabling not implemented for {$connection}. Proceeding anyway.");
        }
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->info("Table '{$table}' has been cleared.");
            } else {
                $this->warn("Table '{$table}' does not exist. Skipping.");
            }
        }
        
        // Check if we should reset checkpoint deliveryID values
        if ($this->option('reset-checkpoint-delivery') && Schema::hasTable('checkpoints')) {
            try {
                $affected = DB::table('checkpoints')->update(['deliveryID' => null]);
                $this->info("Reset deliveryID to NULL for {$affected} checkpoint records");
            } catch (\Exception $e) {
                $this->error("Failed to reset checkpoint deliveryID values: " . $e->getMessage());
            }
        }
        
        // Re-enable foreign key constraints
        if ($connection === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif ($connection === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }
        
        $this->info('All specified tables have been cleared successfully.');
        
        return Command::SUCCESS;
    }
}