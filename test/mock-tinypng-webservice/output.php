<?php
ob_start();

if (preg_match('#output/.+[.](png|jpg)$#', $_SERVER['REQUEST_URI'], $match)) {
    $file = str_replace('/', '-', $match[0]);
    $mime = $match[1] == 'jpg' ? 'image/jpeg' : "image/$ext";
} else {
    $file = null;
}

if ($file && file_exists($file)) {
    header("Content-Type: $mime");
    header('Content-Disposition: attachment');
    readfile($file);
} else {
    header("HTTP/1.1 404 Not Found");
}

ob_end_flush();
