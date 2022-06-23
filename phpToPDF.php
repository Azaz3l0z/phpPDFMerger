<?php
    require('./vendor/autoload.php');
    use Symfony\Component\Filesystem\Filesystem,
    Xthiago\PDFVersionConverter\Converter\GhostscriptConverterCommand,
    Xthiago\PDFVersionConverter\Converter\GhostscriptConverter,
    Xthiago\PDFVersionConverter\Guesser\RegexGuesser;
    
    // EJEMPLOS
    $files = glob('./*.pdf');
    $output_file = "fusionado.pdf";
    mergePDF($files, $output_file);

    function mergePDF($files, $output_file){
        // La funcion mergePDF recibe:
        // $files: Un array con el path a los archivos PDF que se desean
        //         fusionar.
        // $output_file: Un string que indica el path en el que se guardará el
        //               el archivo fusionado

        // Primero comprobamos que el archivo destino no este en la lista de 
        // archivos
        foreach ($files as $file){
            if (preg_match("/".$output_file."/", $file)) {
                $files = array_diff($files, [$file]);
            }
        };

        // Despues convertimos los PDF's a la version PDF 1.4, ya que para
        // versiones mas nuevas el mergePDF no funciona. Si ya estan en la
        // version 1.4, los dejamos como estan
        $filesystem = new Filesystem();
        $guesser = new RegexGuesser();
        $command = new GhostscriptConverterCommand();
        $converter = new GhostscriptConverter($command, $filesystem);
        
        foreach ($files as $file){
            if ($guesser->guess($file)!=1.4){
                $converter->convert($file, '1.4');
            }
        }
        
        $pdf = new \setasign\Fpdi\Fpdi();

        foreach ($files as $file) {
            $pageCount = $pdf->setSourceFile($file);
            for ($i = 0; $i < $pageCount; $i++) {
                $tpl = $pdf->importPage($i + 1, '/MediaBox');
                $size = $pdf->getTemplateSize($tpl);
                $orientation = ($size['height'] > $size['width']) ? 'P' : 'L';

                if ($orientation == "P") {
                    $pdf->addPage($orientation, array($size['width'], $size['height']));
                  } else {
                    $pdf->addPage($orientation, array($size['height'], $size['width']));
                  }
                $pdf->useTemplate($tpl);
            }
        }
        $pdf->Output('F', $output_file);
        echo "PDF creado";
    };
?>

