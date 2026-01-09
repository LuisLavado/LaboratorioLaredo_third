<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Log::info('🟢 BroadcastServiceProvider::boot() - EXECUTING');
        
        Broadcast::routes(['middleware' => ['auth:sanctum']]);

        \Log::info('🟢 About to require channels.php');
        require base_path('routes/channels.php');
        \Log::info('🟢 channels.php required successfully');
    }
}
