<?php

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
$input = preg_split("/\s/", $input);

require __DIR__."/../bin/init.php";
$pdo2 = DB2::pdo();
$pdo2->exec("SET NAMES utf8mb4;");

$st = $pdo2->prepare(
	"INSERT INTO `terms` (`hit_count`, `term`, `calculated`, `created_at`, `updated_at`) VALUES (1, :term, '1', :created_at, NULL) ON DUPLICATE KEY UPDATE `updated_at` = :created_at, `calculated` = '1', `hit_count` = `hit_count` + 1;"
);
$st2 = $pdo2->prepare("SELECT `id`,LOWER(`main_text`) AS `text` FROM `all_text` WHERE `main_text` LIKE :term ORDER BY `created_at` DESC LIMIT 30;");
$st3 = $pdo2->prepare(
	"INSERT INTO `tf` (`tf_hash`,`term_id`, `all_text_id`, `value`, `created_at`, `updated_at`) VALUES (:tf_hash, :term_id, :all_text_id, :value, :created_at, NULL) ON DUPLICATE KEY UPDATE `value` = :value, `updated_at` = :created_at;"
);

$i = 0;
$execData = [];
$unionQuery = "SELECT `r`.`text`,`q`.`value` FROM (
	SELECT `x`.`value`,`x`.`all_text_id` FROM (";
foreach ($input as $k => &$v) {
	$v = strtolower(trim(preg_replace("/[^a-z0-9]/i", "", $v)));
	if (strlen($v) > 3) {
		$st->execute(
			[
				":term" => $v,
				":created_at" => date("Y-m-d H:i:s")
			]
		);
		$termId = $pdo2->lastInsertId();
		$st2->execute([":term" => "%{$v}%"]);
		while ($r = $st2->fetch(PDO::FETCH_ASSOC)) {
			$ftd = 0;
			$count_words = count(preg_split("/\s/", $r["text"]));
			str_ireplace($v, "", $r["text"], $ftd);
			if ($ftd > 0) {
				$tf = (1 + log10($ftd)) * ($ftd / $count_words);
				$st3->execute(
					[
						":tf_hash" => md5(json_encode(
							[
								"a" => $termId,
								"b" => $r["id"]
							]
						)),
						":term_id" => $termId,
						":all_text_id" => $r["id"],
						":value" => $tf,
						":created_at" => date("Y-m-d H:i:s")
					]
				);
			}
		}
		$execData[":term_{$i}"] = $termId;
		$unionQuery .= "(";
		$unionQuery .= "SELECT `all_text_id`, `value` FROM `tf` WHERE `term_id` = :term_{$i} ORDER BY `value` DESC LIMIT 1";
		$unionQuery .= ") UNION ALL ";
		$i++;
	}
}
$st = $st2 = null;
$unionQuery  = rtrim($unionQuery, " UNION ALL");
$unionQuery .= ") AS `x` ORDER BY `value` DESC LIMIT 1
) AS `q` 
INNER JOIN (SELECT CEIL(RAND() * (SELECT MAX(id) FROM `replies`)) AS `id`,`text`,`all_text_id` FROM `replies` ORDER BY `all_text_id` ASC) AS `r` ON `q`.`all_text_id` = `r`.`all_text_id` ORDER BY `r`.`id` ASC LIMIT 1;";
$st = $pdo2->prepare($unionQuery);
$st->execute($execData);
unset($execData);

if ($st = $st->fetch(PDO::FETCH_ASSOC)) {
	print json_encode($st);
}
$st = $pdo2 = null;
DB2::close();


// SELECT `r`.`text`,`q`.`value` FROM (
// 	SELECT `x`.`value`,`x`.`all_text_id` FROM (
// 		(SELECT `all_text_id`, `value` FROM `tf` ORDER BY `value` DESC LIMIT 1) UNION ALL
// 		(SELECT `all_text_id`, `value` FROM `tf` ORDER BY `value` DESC LIMIT 1) UNION ALL
// 		(SELECT `all_text_id`, `value` FROM `tf` ORDER BY `value` DESC LIMIT 1) UNION ALL
// 		(SELECT `all_text_id`, `value` FROM `tf` ORDER BY `value` DESC LIMIT 1) UNION ALL
// 		(SELECT `all_text_id`, `value` FROM `tf` ORDER BY `value` DESC LIMIT 1) UNION ALL
// 		(SELECT `all_text_id`, `value` FROM `tf` ORDER BY `value` DESC LIMIT 1) UNION ALL
// 		(SELECT `all_text_id`, `value` FROM `tf` ORDER BY `value` DESC LIMIT 1) UNION ALL
// 		(SELECT `all_text_id`, `value` FROM `tf` ORDER BY `value` DESC LIMIT 1) UNION ALL
// 		(SELECT `all_text_id`, `value` FROM `tf` ORDER BY `value` DESC LIMIT 1) UNION ALL
// 		(SELECT `all_text_id`, `value` FROM `tf` ORDER BY `value` DESC LIMIT 1) UNION ALL
// 		(SELECT `all_text_id`, `value` FROM `tf` ORDER BY `value` DESC LIMIT 1) UNION ALL
// 		(SELECT `all_text_id`, `value` FROM `tf` ORDER BY `value` DESC LIMIT 1) UNION ALL
// 		(SELECT `all_text_id`, `value` FROM `tf` ORDER BY `value` DESC LIMIT 1) UNION ALL
// 		(SELECT `all_text_id`, `value` FROM `tf` ORDER BY `value` DESC LIMIT 1) UNION ALL
// 		(SELECT `all_text_id`, `value` FROM `tf` ORDER BY `value` DESC LIMIT 1) UNION ALL
// 		(SELECT `all_text_id`, `value` FROM `tf` ORDER BY `value` DESC LIMIT 1)
// 	) AS `x` ORDER BY `value` DESC LIMIT 1
// ) AS `q` 
// INNER JOIN (SELECT CEIL(RAND() * (SELECT MAX(id) FROM `replies`)) AS `id`,`text`,`all_text_id` FROM `replies` ORDER BY `all_text_id` ASC) AS `r` 
// ON `q`.`all_text_id` = `r`.`all_text_id`
// ORDER BY `r`.`id` ASC LIMIT 1;

