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
    
    // EJEMPLOS
    // $files = glob('./*.pdf');

    function fusionaPDF($files, $output_file){
        // La funcion fusionaPDF recibe:
        // $files: Un array con el path a los archivos PDF que se desean
        //         fusionar.
        // $output_file: Un string que indica el path en el que se guardará el
        //               el archivo fusionado

        // Primero comprobamos que el archivo destino no este en la lista de 
        // archivos
        try {
            foreach ($files as $file){
                if (file_exists($file)){
                    if (preg_match('@'.$output_file.'@', $file)) {
                        $files = array_diff($files, [$file]);
                    }
                } else {
                    $files = array_diff($files, [$file]);
                }
            }
            if ($files == []) {
                throw new FileNotFound;
            }
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
            
            // Creamos el objeto PDF
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
        } catch (Exception $e) {
            echo "No se ha podio generar el PDF porque el/los archivo/s no existen.";
        }
    };

    function fusionaIMG($files, $output_file, $deleteWebp = TRUE){
        // Esta función toma como argumento el path de las imagenes a fusionar
        // y el archivo PDF donde se guardarán-
        // Además tenemos el argumento $deleteWebp, que lo que hará es:
        // Si tenemos una imagen con el formato .webp, la transformará a .jpg
        // y borrará la .webp
        // En caso de que este argumento sea FALSE, no se borrará la imagen.
        try {
            $pdf = new fpdf();
            $pdf->SetAutoPageBreak(false, 0);
            $pdf->SetMargins(0, 0, 0);
            $pdf->SetAutoPageBreak(false, 0);

            foreach ($files as $file){
                if (!file_exists($file)){
                    $files = array_diff($files, [$file]);
                }
            }
            if ($files == []) {
                throw new FileNotFound;
            }
            
            foreach ($files as $file){
                if (str_contains($file, ".webp")){
                    $new_file = str_replace(".webp", ".jpg", $file);
                    webp2jpg($file, $new_file);
                    if ($deleteWebp){
                        unlink($file);
                    }
                    $file = $new_file;
                }
                $img_info = getimagesize($file);
                list($x, $y) = getimagesize($file);
                $orientation = ($x > $y) ? 'L' : 'P';
                $pdiSIZE = ($x > $y) ? 297 : 210;
        
                $pdf->AddPage($orientation);
                $pdf->Image($file, 0, 0, $pdiSIZE);
            }
            $pdf->Output($output_file, 'F');
            echo "PDF creado";
        } catch (Exception $e) {
            echo "No se ha podio generar el PDF porque el/los archivo/s no existen.";
        }
    };

    function disuelvePDF($target_pdf, $file_pages){
        // Esta función toma como argumento el path del PDF a disolver y
        // un array de arrays.
        // El primer nivel de arrays representa un archivo individual,
        // es decir, si tenemos dos arrays dentro del array se generarán 2
        // archivos.
        // El array interior indica que páginas se usarán para generar dicho
        // PDF (ver ejemplo)

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
            $global_name = explode(".pdf", $target_pdf)[0];
            foreach($file_pages as $pages){
                $file_name = str_replace(",", "-", substr(json_encode($pages), 1, -1));
                // Otra manera de nombrarlos
                // $file_name = substr(json_encode($pages), 1, -1);
                // $file_name = substr($file_name, 0,1)."-".substr($file_name, -1);
                $file_name = $global_name.$file_name.".pdf";

                $pdf = new \setasign\Fpdi\Fpdi();
                $pageCount = $pdf->setSourceFile($target_pdf) + 1;
                foreach ($pages as $page) {
                    if ($page < $pageCount){
                        $tpl = $pdf->importPage($page, '/MediaBox');
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
                };
                // Lo guardamos
                $pdf->Output('F', $file_name);
            }
            echo "PDF/s creado/s";
        } catch (FileNotFound $e){
            echo "No se ha podio generar el PDF porque el/los archivo/s no existen.";
        }
    };
?>

