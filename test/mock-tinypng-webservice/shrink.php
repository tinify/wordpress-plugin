<?php
ob_start();

define('SESSION_FILE', '/tmp/session.dat');

if (file_exists(SESSION_FILE)) {
    $session = unserialize(file_get_contents(SESSION_FILE));
} else {
    $session = array('Compression-Count' => 0);
}

function mock_png_response() {
    global $session;

    $session['Compression-Count'] += 1;
    header('HTTP/1.1 201 Created');
    header("Location: http://webservice/output/example.png");
    header("Compression-Count: {$session['Compression-Count']}");
    $response = array(
        "input" => array("size" => 161885, "type" => "image/png"),
        "output" => array("size" => 151021, "type" => "image/png", "ratio" => 0.933)
    );
    return json_encode($response);
}

function mock_jpg_response() {
    global $session;

    $session['Compression-Count'] += 1;
    header('HTTP/1.1 201 Created');
    header("Location: http://webservice/output/example.jpg");
    header("Compression-Count: {$session['Compression-Count']}");

    $response = array(
        "input" => array("size" => 15391, "type" => "image/jpg"),
        "output" => array("size" => 13910, "type" => "image/jpg", "ratio" => 0.904)
    );
    return json_encode($response);
}

function mock_large_response() {
    global $session;

    $session['Compression-Count'] += 1;
    header('HTTP/1.1 201 Created');
    header("Location: http://webservice/output/large.png");
    header("Compression-Count: {$session['Compression-Count']}");

    $response = array(
        "input" => array("size" => 80506, "type" => "image/jpg"),
        "output" => array("size" => 70200, "type" => "image/jpg", "ratio" => 0.872)
    );
    return json_encode($response);
}

function mock_invalid_response() {
    global $session;

    header('HTTP/1.1 401 Unauthorized');
    header("Content-Type: application/json; charset=utf-8");

    $response = array(
        "error" => "Unauthorized",
        "message" => "Credentials are invalid"
    );
    return json_encode($response);
}

function mock_empty_response() {
    global $session;

    header('HTTP/1.1 400 Bad Request');
    header("Content-Type: application/json; charset=utf-8");
    header("Compression-Count: {$session['Compression-Count']}");

    $response = array(
        "error" => "InputMissing",
        "message" => "Your monthly limit has been exceeded"
    );
    return json_encode($response);
}

function mock_limit_reached_response() {
    global $session;

    header('HTTP/1.1 429 Too Many Requests');
    header("Content-Type: application/json; charset=utf-8");
    header("Compression-Count: 500");

    $response = array(
        "error" => "TooManyRequests",
        "message" => "Your monthly limit has been exceeded"
    );
    return json_encode($response);
}

function mock_invalid_json_response() {
    global $session;

    $session['Compression-Count'] += 1;
    header('HTTP/1.1 201 Created');
    header("Location: http://webservice/output/example.png");
    header("Compression-Count: {$session['Compression-Count']}");
    return '{invalid: json}';
}

function mock_resized_response() {
    global $session;

    $session['Compression-Count'] += 1;
    header('HTTP/1.1 201 Created');
    header("Location: http://webservice/output/resized.jpg");
    header("Compression-Count: {$session['Compression-Count']}");
    $response = array(
        "input" => array("size" => 161885, "type" => "image/jpg"),
        "output" => array("size" => 14238, "type" => "image/jpg", "ratio" => 0.95, "width" => 1080, "height" => 720)
    );
    return json_encode($response);
}

$request_headers = apache_request_headers();
$basic_auth = base64_decode(str_replace('Basic ', '', $request_headers['Authorization']));
$api_key_elements = explode(':', $basic_auth);
$api_key = $api_key_elements[1];

header("Content-Type: application/json; charset=utf-8");

if ($api_key == 'PNG123') {
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
} else if ($api_key == 'JSON1234') {
    if (intval($_SERVER['CONTENT_LENGTH']) == 0) {
        echo mock_empty_response();
    } else {
        echo mock_invalid_json_response();
    }
} else if ($api_key == 'LIMIT123') {
    echo mock_limit_reached_response();
} else if ($api_key == 'RESIZE123') {
    if (intval($_SERVER['CONTENT_LENGTH']) == 0) {
        echo mock_empty_response();
    } else {
        echo mock_resized_response();
    }
} else {
    echo mock_invalid_response();
}

file_put_contents(SESSION_FILE, serialize($session));

ob_end_flush();
