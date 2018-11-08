<?php

ob_start();

function mock_key_details_free_request($remaining = 500) {
	header("HTTP/1.1 200 OK");
	header("Content-Type: application/json; charset=utf-8");
	header("Compression-Count: 0");
	header("Compression-Count-Remaining: " . $remaining);
	header("Email-Address: test@example.com");
	header("Paying-State: free");

	return '{}';
}

function mock_key_details_paid_request() {
	header("HTTP/1.1 200 OK");
	header("Content-Type: application/json; charset=utf-8");
	header("Compression-Count: 0");
	header("Email-Address: test@example.com");
	header("Paying-State: paid");

	return '{}';
}

function mock_invalid_key_request() {
	header("HTTP/1.1 404 Not Found");
	header("Content-Type: application/json; charset=utf-8");

	return '{}';
}

function mock_key_creation_request() {
	header("HTTP/1.1 202 Pending");
	header("Content-Type: application/json; charset=utf-8");

	return json_encode(
		array(
			'key' => 'PENDING123',
		)
	);
}

if ($_SERVER["REQUEST_METHOD"] == "GET") {
	if (preg_match("#keys/(.+)$#", $_SERVER["REQUEST_URI"], $match)) {
		$key = $match[1];
		if ($key == "INVALID123" or $key == "PENDING123") {
			echo mock_invalid_key_request();
		} elseif ($key == "LIMIT123") {
			echo mock_key_details_free_request();
		} elseif ($key == "INSUFFICIENTCREDITS123") {
			echo mock_key_details_free_request(1);
		} elseif ($key == "NOCREDITS123") {
			echo mock_key_details_free_request(0);
		} else {
			echo mock_key_details_paid_request();
		}
	} else {
		echo mock_invalid_key_request();
	}
} else {
	echo mock_key_creation_request();
}

ob_end_flush();
