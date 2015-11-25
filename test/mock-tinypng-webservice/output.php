<?php
ob_start();

require_once('common.php');

if (preg_match('#output/.+[.](png|jpg)$#', $_SERVER['REQUEST_URI'], $match)) {
    $file = str_replace('/', '-', $match[0]);
    $ext = $match[1];
    $mime = $match[1] == 'jpg' ? 'image/jpeg' : "image/$ext";
} else {
    $file = null;
}

$api_key = get_api_key();
if (!is_null($api_key)) {
    $data = get_json_body();
    if (is_null($data) || $api_key != 'JPG123') {
        mock_invalid_response();
        ob_end_flush();
        exit();
    }

    $resize = $data->resize;
    if ($resize->method) {
        $file = "output-resized.$ext";
        header("Image-Width: {$resize->width}");
        header("Image-Height: {$resize->height}");
    }
}

if ($file && file_exists($file)) {
    header("Content-Type: $mime");
    header('Content-Disposition: attachment');
    readfile($file);
} else {
    header("HTTP/1.1 404 Not Found");
}

ob_end_flush();