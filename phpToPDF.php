<?php
    require('./vendor/autoload.php');
    require('./vendor/setasign/fpdf/fpdf.php');
    require('./modules/webpToJPG.php');

    use Symfony\Component\Filesystem\Filesystem,
    Xthiago\PDFVersionConverter\Converter\GhostscriptConverterCommand,
    Xthiago\PDFVersionConverter\Converter\GhostscriptConverter,
    Xthiago\PDFVersionConverter\Guesser\RegexGuesser;

    class FileNotFound extends Exception {
        public function errorMessage() {
          //error message
          $errorMsg = "Archivo no encontrado";
          return $errorMsg;
        }
    }

    function anhadePagina($original_file, $merge_file, $page){
        // Está función toma como argumentos un $original_file, un $merge_file
        // y una $page.

        // Lo que hace es introducir el archivo $merge_file en la página $page
        // de $original_file
        try {
            $filesystem = new Filesystem();
            $guesser = new RegexGuesser();
            $command = new GhostscriptConverterCommand();
            $converter = new GhostscriptConverter($command, $filesystem);

            if (!file_exists($original_file)){
                throw new FileNotFound;
            }

            $files = array($original_file, $merge_file);
            
            foreach ($files as $file){
                if (str_contains($file, ".pdf")){
                    if ($guesser->guess($file)!=1.4){
                        $converter->convert($file, '1.4');
                    }
                }
            }

            $pdf = new \setasign\Fpdi\Fpdi();
            $pageCount = $pdf->setSourceFile($original_file);

            for ($i = 0; $i < $pageCount; $i++) {
                // Vemos si estamos en la página en la que hay que añadir
                // contenido
                if ($i + 1 == $page) {
                    $pdf->SetAutoPageBreak(FALSE, 0);
                    if (str_contains($merge_file, ".pdf")) {
                        $pageCountMerge = $pdf->setSourceFile($merge_file);
                        for ($j = 1; $j < $pageCountMerge + 1; $j++) {
                            $pdf->addPage();
                            $tplidx = $pdf->ImportPage($j);
                            $pdf->useTemplate($tplidx);   
                        }    
                
                        $pageCount = $pdf->setSourceFile($original_file); 
                    } else { // Si es una imagen
                        // Si es .webp se convierte a .jpg y se da la opcion de
                        // borrar la imagen .webp
                        if (str_contains($merge_file, ".webp")){
                            $new_file = str_replace(".webp", ".jpg", $merge_file);
                            webp2jpg($merge_file, $new_file);
                            if ($deleteWebp){
                                unlink($merge_file);
                            }
                            $merge_file = $new_file;
                        }
                        $img_info = getimagesize($merge_file);
                        list($x, $y) = getimagesize($merge_file);
                        $orientation = ($x > $y) ? 'L' : 'P';
                        $pdiSIZE = ($x > $y) ? 297 : 210;
                
                        $pdf->AddPage($orientation);
                        $pdf->Image($merge_file, 0, 0, $pdiSIZE); 
                    }
                    
                }

                // Añadimos el resto del pdf de forma normal
                $tpl = $pdf->importPage($i + 1, '/MediaBox');
                $size = $pdf->getTemplateSize($tpl);
                $orientation = ($size['height'] > $size['width']) ? 'P' : 'L';

                // Orientamos el PDF
                if ($orientation == "P") {
                    $pdf->addPage($orientation, array($size['width'],
                        $size['height']));
                    } else {
                    $pdf->addPage($orientation, array($size['height'], 
                        $size['width']));
                    }
                $pdf->useTemplate($tpl);
            }
            $pdf->Output('F', $original_file);
            echo "PDF creado";

        } catch (FileNotFound $e){
            echo "No se ha podio generar el PDF porque el/los archivo/s no existen.";

        }
    }

    function eliminaPaginas($target_pdf, $pages){
        // Esta función toma como argumento el path del PDF del que se quieren
        // eliminar las páginas y
        // IMPORTANTE: Las paginas empiezan en 1, no en 0.
        $pages = explode(",", $pages);

        // Comprobamos que el archivo existe
        try {
            // Convertimos los PDF's a la version PDF 1.4, ya que para
            // versiones mas nuevas el mergePDF no funciona. Si ya estan en la
            // version 1.4, los dejamos como estan
            $filesystem = new Filesystem();
            $guesser = new RegexGuesser();
            $command = new GhostscriptConverterCommand();
            $converter = new GhostscriptConverter($command, $filesystem);

            if (!file_exists($target_pdf)){
                throw new FileNotFound;
            }
            
            if ($guesser->guess($target_pdf)!=1.4){
                $converter->convert($target_pdf, '1.4');
            }

            // Definimos el nombre del archivo y empezamos a seleccionar las paginas
            // que queremos
            $pdf = new \setasign\Fpdi\Fpdi();
            $pageCount = $pdf->setSourceFile($target_pdf);
            for ($i = 0; $i < $pageCount; $i++) {
                if (!in_array($i + 1, $pages)){
                    $tpl = $pdf->importPage($i + 1, '/MediaBox');
                    $size = $pdf->getTemplateSize($tpl);
                    $orientation = ($size['height'] > $size['width']) ? 'P' : 'L';
    
                    // Orientamos el PDF
                    if ($orientation == "P") {
                        $pdf->addPage($orientation, array($size['width'],
                            $size['height']));
                        } else {
                        $pdf->addPage($orientation, array($size['height'], 
                            $size['width']));
                        }
                    $pdf->useTemplate($tpl);
                }
            }

            // Lo guardamos
            $pdf->Output('F', $target_pdf);
            
            echo "PDF/s creado/s";
        } catch (FileNotFound $e){
            echo "No se ha podio generar el PDF.";
        }
    };
?>

