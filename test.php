<?php
    require("phpToPDF.php");

    anhadePagina('./tests/pdf2.pdf', './tests/img4.webp', 1); 
    anhadePagina('./tests/pdf1.pdf', './tests/pdf2.pdf', 1); 
    eliminaPaginas("./tests/pdf2.pdf", "1");

?>