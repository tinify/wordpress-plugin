<?php
ob_start();

require_once 'common.php';

const HOST = 'http://host.docker.internal:8100';

function mock_png_response()
{
    global $session;

    $session['Compression-Count'] += 1;
    header('HTTP/1.1 201 Created');
    header("Location: " . HOST . "/output/example.png");
    header("Content-Type: application/json; charset=utf-8");
    header("Compression-Count: {$session['Compression-Count']}");
    header("Image-Width: 720");
    header("Image-Height: 1080");

    $response = array(
        "input" => array("size" => 641206, "type" => "image/png"),
        "output" => array("size" => 151021, "type" => "image/png", "ratio" => 0.933)
    );
    return json_encode($response);
}

function mock_jpg_response()
{
    global $session;

    $session['Compression-Count'] += 1;
    header('HTTP/1.1 201 Created');
    header("Location: " . HOST . "/output/example.jpg");
    header("Content-Type: application/json; charset=utf-8");
    header("Compression-Count: {$session['Compression-Count']}");
    header("Image-Width: 200");
    header("Image-Height: 150");

    $response = array(
        "input" => array("size" => 15391, "type" => "image/jpeg"),
        "output" => array("size" => 13910, "type" => "image/jpeg", "ratio" => 0.904)
    );
    return json_encode($response);
}

function mock_webp_response()
{
    global $session;

    $session['Compression-Count'] += 1;
    header('HTTP/1.1 201 Created');
    header("Location: " . HOST . "/output/example.webp");
    header("Content-Type: application/json; charset=utf-8");
    header("Compression-Count: {$session['Compression-Count']}");
    header("Image-Width: 200");
    header("Image-Height: 150");

    $response = array(
        "input" => array("size" => 15391, "type" => "image/png"),
        "output" => array("size" => 11023, "type" => "image/webp", "ratio" => 0.2304)
    );
    return json_encode($response);
}
function mock_avif_response()
{
    global $session;

    $session['Compression-Count'] += 1;
    header('HTTP/1.1 201 Created');
    header("Location: " . HOST . "/output/example.avif");
    header("Content-Type: application/json; charset=utf-8");
    header("Compression-Count: {$session['Compression-Count']}");
    header("Image-Width: 1080");
    header("Image-Height: 720");

    $response = array(
        "input" => array("size" => 641206, "type" => "image/jpeg"),
        "output" => array("size" => 101549, "type" => "image/avif", "ratio" => 0.1584)
    );
    return json_encode($response);
}

function mock_preserve_jpg_copyright_response()
{
    global $session;

    $session['Compression-Count'] += 1;
    header('HTTP/1.1 201 Created');
    header("Location: " . HOST . "/output/copyright.jpg");
    header("Content-Type: application/json; charset=utf-8");
    header("Compression-Count: {$session['Compression-Count']}");
    header("Image-Width: 330");
    header("Image-Height: 1080");

    $response = array(
        "input" => array("size" => 110329, "type" => "image/jpeg"),
        "output" => array("size" => 97835, "type" => "image/jpeg", "ratio" => 0.8868)
    );
    return json_encode($response);
}

function mock_empty_response()
{
    global $session;

    header('HTTP/1.1 400 Bad Request');
    header("Content-Type: application/json; charset=utf-8");
    header("Compression-Count: {$session['Compression-Count']}");

    $response = array(
        "error" => "Input missing",
        "message" => "File is empty"
    );
    return json_encode($response);
}

function mock_limit_reached_response()
{
    global $session;

    header('HTTP/1.1 429 Too Many Requests');
    header("Content-Type: application/json; charset=utf-8");
    header("Compression-Count: 500");
    header("Compression-Count-Remaining: 0");
    header("Paying-State: free");

    $response = array(
        "error" => "Too many requests",
        "message" => "Your monthly limit has been exceeded"
    );
    return json_encode($response);
}

function mock_invalid_json_response()
{
    global $session;

    $session['Compression-Count'] += 1;
    header('HTTP/1.1 201 Created');
    header("Location: " . HOST . "/output/example.png");
    header("Content-Type: application/json; charset=utf-8");
    header("Compression-Count: {$session['Compression-Count']}");
    return '{invalid: json}';
}


$api_key = get_api_key();
if (substr($api_key, 0, 6) == 'PNG123') {
    if (intval($_SERVER['CONTENT_LENGTH']) == 0) {
        echo mock_empty_response();
    } else {
        echo mock_png_response();
    }
} else if ($api_key == 'JPG123') {
    if (intval($_SERVER['CONTENT_LENGTH']) == 0) {
        echo mock_empty_response();
    } else {
        echo mock_jpg_response();
    }
} else if ($api_key == 'PRESERVEJPG123') {
    if (intval($_SERVER['CONTENT_LENGTH']) == 0) {
        echo mock_empty_response();
    } else {
        echo mock_preserve_jpg_copyright_response();
    }
} else if ($api_key == 'JSON1234') {
    if (intval($_SERVER['CONTENT_LENGTH']) == 0) {
        echo mock_empty_response();
    } else {
        echo mock_invalid_json_response();
    }
} else if ($api_key == 'LIMIT123') {
    echo mock_limit_reached_response();
} else if ($api_key == 'GATEWAYTIMEOUT') {
    echo mock_service_unavailable_response();
} else if ($api_key == 'WEBP123') {
    if (intval($_SERVER['CONTENT_LENGTH']) == 0) {
        echo mock_empty_response();
    } else {
        echo mock_webp_response();
    }
} else if ($api_key == 'AVIF123') {
    if (intval($_SERVER['CONTENT_LENGTH']) == 0) {
        echo mock_empty_response();
    } else {
        echo mock_avif_response();
    }
} else {
    echo mock_invalid_response();
}

ob_end_flush();
