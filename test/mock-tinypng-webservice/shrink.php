<?php
ob_start();

function mock_png_response() {
    header("Location: http://webservice/output/2351zxcf2359.png");
    header("Content-Type: application/json; charset=utf-8");
    header("Compression-Count: 1");

    $response = array(
            "input" => array("size" => 12345, "type" => "image/png"),
            "output" => array("size" => 1234, "type" => "image.png", "ratio" => 0.307)
    );
    return json_encode($response);
}

function mock_jpg_response() {
    header("Location: http://webservice/output/2351zxcf2359.jpg");
    header("Content-Type: application/json; charset=utf-8");
    header("Compression-Count: 1");

    $response = array(
            "input" => array("size" => 12345, "type" => "image/jpg"),
            "output" => array("size" => 1234, "type" => "image/jpg", "ratio" => 0.307)
    );
    return json_encode($response);
}

$request_headers = apache_request_headers();
$basic_auth = base64_decode(str_replace('Basic ', '', $request_headers['Authorization']));
$api_key_elements = explode(':', $basic_auth);
$api_key = $api_key_elements[1];
header('HTTP/1.1 201 Created');

if ($api_key == 'PNG123') {
    print_r(mock_png_response());
} else if ($api_key == 'JPG123') {
    print_r(mock_jpg_response());
} else {
    header('HTTP/1.1 401 Unauthorized');
    print_r(json_encode(array(
        "error" => "Unauthorized",
        "message" => "Credentials are invalid"
    )));
}

ob_end_flush();
