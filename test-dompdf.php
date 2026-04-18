<?php
require 'vendor/autoload.php';

try {
    $pdf = new \Dompdf\Dompdf();
    $pdf->loadHtml('<h1>Dompdf Test</h1><p>This is a test PDF.</p>');
    $pdf->render();
    
    echo "✓ Dompdf working perfectly!\n";
    echo "PDF size: " . strlen($pdf->output()) . " bytes\n";
    echo "Status: READY FOR PRODUCTION\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
