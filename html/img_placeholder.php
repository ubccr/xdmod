<?php

   $src = imagecreatetruecolor(7, 12);
   $background = imagecolorallocate($src, 255, 255, 255);
   imagefill($src, 0, 0, $background);
   
   // Output and free from memory
   header('Content-Type: image/png');
   imagepng($src);

   imagedestroy($src);
