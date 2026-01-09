<?php

use Illuminate\Support\Facades\Route;

// NOTA: Las rutas de broadcasting están en routes/api.php para usar auth:sanctum

Route::get('/', function () {
    return response()->json([
        'message' => 'Laravel Backend API',
        'version' => '2.1',
        'status' => 'running'
    ]);
});





