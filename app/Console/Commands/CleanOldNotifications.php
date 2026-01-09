<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanOldNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:clean {--days=30 : Number of days to keep notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean old notifications to improve performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = now()->subDays($days);

        $this->info("Cleaning notifications older than {$days} days (before {$cutoffDate->format('Y-m-d H:i:s')})...");

        try {
            // Contar notificaciones a eliminar
            $count = \DB::table('notifications')
                ->where('created_at', '<', $cutoffDate)
                ->count();

            if ($count === 0) {
                $this->info('No old notifications found to clean.');
                return 0;
            }

            // Eliminar notificaciones antiguas
            $deleted = \DB::table('notifications')
                ->where('created_at', '<', $cutoffDate)
                ->delete();

            $this->info("✅ Successfully deleted {$deleted} old notifications.");

            // Limpiar trabajos fallidos antiguos también
            $failedJobs = \DB::table('failed_jobs')
                ->where('failed_at', '<', $cutoffDate)
                ->count();

            if ($failedJobs > 0) {
                $deletedJobs = \DB::table('failed_jobs')
                    ->where('failed_at', '<', $cutoffDate)
                    ->delete();
                $this->info("✅ Also deleted {$deletedJobs} old failed jobs.");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error cleaning notifications: ' . $e->getMessage());
            return 1;
        }
    }
}
