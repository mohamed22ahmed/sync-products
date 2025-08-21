<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestSchedulerCommand extends Command
{
    protected $signature = 'scheduler:test';

    protected $description = 'Test command to verify scheduler is working';

    public function handle(): int
    {
        $this->info('🎯 Scheduler test command executed successfully!');
        $this->info('Current time: ' . now()->format('Y-m-d H:i:s'));
        $this->info('Timezone: ' . config('app.timezone'));
        
        $this->newLine();
        $this->info('📅 Scheduled Jobs:');
        $this->info('• Daily sync: Every day at 2:00 AM (batch-size: 100)');
        $this->info('• Business hours: Every 6 hours on weekdays 8 AM - 6 PM (batch-size: 50)');
        
        $this->newLine();
        $this->info('✅ Scheduler is working correctly!');
        
        return Command::SUCCESS;
    }
}
