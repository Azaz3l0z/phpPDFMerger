<?php
    function webp2jpg($source_file, $destination_file, $compression_quality = 100)
    {
        $image = imagecreatefromwebp($source_file);
        $result = imagejpeg($image, $destination_file, $compression_quality);
        if (false === $result) {
            return false;
        }
        imagedestroy($image);
        return $destination_file;
    }
?>
