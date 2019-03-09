<?php
ini_set("display_errors", true);
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "GET") {
	if (isset($_GET["in"])) {
		$input = &$_GET["in"];
	} else {
		http_response_code(400);
		print json_encode(
			[
				"status" => "error",
				"message" => "\"in\" parameter required!"
			],
			128
		);
		exit;
	}
} else {
	if (isset($_POST["in"])) {
		$input = &$_POST["in"];
	} else if (isset($_GET["in"])) {
		$input = &$_GET["in"];
	} else {
		http_response_code(400);
		print json_encode(
			[
				"status" => "error",
				"message" => "\"in\" parameter required!"
			],
			128
		);
		exit;
	}
}

if (!is_string($input)) {
	http_response_code(400);
	print json_encode(
		[
			"status" => "error",
			"message" => "\"in\" parameter must be a string!"
		],
		128
	);
	exit;
}

$input = trim(strtolower($input));

if ($input === "") {
	http_response_code(400);
	print json_encode(
		[
			"status" => "error",
			"message" => "\"in\" parameter cannot be empty!"
		],
		128
	);
	exit;
}


if (isset($_GET["name"]) && is_string($_GET["name"])) {
	$name = $_GET["name"];
}

$input = escapeshellarg($input);
$name = escapeshellarg($name);
$output = trim(shell_exec("echo {$input} | /usr/bin/php -d extension=/var/app/teaai/teaai.so /var/app/teaai/bin/TeaAI.php chat --stdin-input --stdout-output --name={$name}"));

print json_encode(
	[
		"response" => (($output === "") ? null : $output)
	]
);

