<?php
    require("phpToPDF.php");
    // Imagenes
    $imgs = glob('./tests/img*');

    // Fusiona todas las imagenes (soporta jpeg, jpg, png y webp) 
    // de la carpeta tests y los guarda en fusionadoIMG.pdf
    fusionaIMG($imgs, "./tests/fusionadoIMG.pdf", FALSE);

    // Todos los pdfs de la carpeta tests
    $pdfs = glob('./tests/*.pdf');
    fusionaPDF($pdfs, "./tests/fusionado.pdf"); 
    
    // Disolvemos el pdf fusionado.pdf
    $pdf = "./tests/fusionado.pdf";
    disuelvePDF($pdf, [[1, 2], [2,3], [2], [5,6,7]]);

?>