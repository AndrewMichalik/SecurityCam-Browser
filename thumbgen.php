<?php
header('Content-Type: image/jpeg');

// Get scaling factor
$scale=$_GET[scale];
if ($scale <= 1) $scale = 2;

// Retrieve image
$image = LoadJpeg($_GET[dir] . $_GET[img]);
$w = imagesx($image);
$h = imagesy($image);

// Scale to new dimensions
$w_new = $w/$scale;
$h_new = $h/$scale;

// Reduces and resample image size
$image_res = imagecreatetruecolor($w_new, $h_new);
imagecopyresampled($image_res, $image, 0, 0, 0, 0, $w_new, $h_new, $w, $h);

// Display
imagejpeg ($image_res);
imagedestroy($image);

function LoadJpeg($imgname)
{
    /* Attempt to open */
    $im = @imagecreatefromjpeg($imgname);

    /* See if it failed */
    if(!$im)
    {
        /* Create a black image */
        $im  = imagecreatetruecolor(150, 30);
        $bgc = imagecolorallocate($im, 255, 255, 255);
        $tc  = imagecolorallocate($im, 0, 0, 0);

        imagefilledrectangle($im, 0, 0, 150, 30, $bgc);

        /* Output an error message */
        imagestring($im, 1, 5, 5, 'Error loading ' . $imgname, $tc);
    }

    return $im;
}

?> 