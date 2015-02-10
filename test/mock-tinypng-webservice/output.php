<?php
ob_start();

if (preg_match('/png$/', $_SERVER['REQUEST_URI'])) {
    png_response();
} else if (preg_match('/jpg$/', $_SERVER['REQUEST_URI'])) {
    jpg_response();
} else {
    header("HTTP/1.1 404 Not Found");
}

function png_response() {
    header('Content-Type: image/png');
    header('Content-Disposition: attachment');
    readfile('example-tinypng.png');

}

function jpg_response() {
    header('Content-Type: image/jpg');
    header('Content-Disposition: attachment');
    readfile('example-tinyjpg.jpg');
}

ob_end_flush();
