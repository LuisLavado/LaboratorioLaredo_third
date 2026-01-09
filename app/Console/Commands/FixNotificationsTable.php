<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FixNotificationsTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:fix-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix notifications table structure to use UUIDs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing notifications table structure...');

        try {
            // Eliminar la tabla existente
            \DB::statement('DROP TABLE IF EXISTS notifications');
            $this->info('Dropped existing notifications table');

            // Crear la nueva tabla con estructura correcta
            \DB::statement('
                CREATE TABLE notifications (
                    id CHAR(36) PRIMARY KEY,
                    type VARCHAR(255) NOT NULL,
                    notifiable_type VARCHAR(255) NOT NULL,
                    notifiable_id BIGINT UNSIGNED NOT NULL,
                    data JSON NOT NULL,
                    read_at TIMESTAMP NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    INDEX notifications_notifiable_type_notifiable_id_index (notifiable_type, notifiable_id)
                )
            ');
            $this->info('Created new notifications table with UUID primary key');

            $this->info('✅ Notifications table fixed successfully!');

        } catch (\Exception $e) {
            $this->error('❌ Error fixing notifications table: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
