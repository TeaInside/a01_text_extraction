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
require __DIR__."/../bin/init.php";
$pdo2 = DB2::pdo();
$pdo2->exec("SET NAMES utf8mb4;");
$st = $pdo2->prepare("SELECT `me`.`text`,`me`.`score` FROM (
	SELECT 
		(SELECT CEIL(RAND() * (SELECT MAX(id) FROM `replies`))) AS `id`,
		`b`.`text`,
		MATCH (`a`.`main_text`) AGAINST (:input IN NATURAL LANGUAGE MODE) AS `score`
	FROM `all_text` AS `a` 
		INNER JOIN `replies` AS `b` ON `a`.`id` = `b`.`all_text_id` 
		WHERE MATCH (`a`.`main_text`) AGAINST (:input IN NATURAL LANGUAGE MODE) LIMIT 3
) AS `me` ORDER BY `id` ASC LIMIT 1;");
$st->execute([":input" => $input]);
if ($r = $st->fetch(PDO::FETCH_ASSOC)) {
	print json_encode($r);
} else {
	print json_encode(["text" => null, "score" => 0]);
}
$pdo = $st = null;
DB2::close();
