<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ReporteController;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TestPdfGeneration extends Command
{
    protected $signature = 'test:pdf-generation';
    protected $description = 'Test PDF generation for debugging';

    public function handle()
    {
        $this->info('Testing PDF generation...');
        
        try {
            // Crear una request simulada
            $request = new Request([
                'type' => 'general',
                'start_date' => '2025-05-31',
                'end_date' => '2025-06-30'
            ]);
            
            // Crear el controlador
            $controller = app(ReporteController::class);
            
            // Intentar generar el PDF
            $response = $controller->generatePDF($request);
            
            $this->info('PDF generated successfully!');
            $this->info('Response type: ' . get_class($response));
            
        } catch (\Exception $e) {
            $this->error('Error generating PDF: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
            $this->error('Trace: ' . $e->getTraceAsString());
        }
    }
}
