<?php
    require("phpToPDF.php");

    // Todos los archivos de la carpeta tests
    $pdfs = glob('./tests/*.pdf');
    fusionaPDF($files, "./tests/fusionado.pdf", FALSE); 
    
    // Disolvemos el pdf fusionado.pdf
    $pdf = "./tests/fusionado.pdf";
    disuelvePDF($pdf, [[1, 2], [2,3], [2], [5,6,7]]);

?>