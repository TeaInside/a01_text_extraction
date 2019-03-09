<?php

require __DIR__."/init.php";

print "Connecting to DB2...";
$pdo2 = DB2::pdo();
$pdo2->exec("SET NAMES utf8mb4;");
print "OK\n";

$input = "laper banget gw, pingin bareng keluarga";
$input = preg_split("/\s/", $input);
$st = $pdo2->prepare(
	"SELECT 
		`a`.`value`,`a`.`all_text_id`,`b`.`term`
	FROM `tf` AS `a` INNER JOIN `terms` AS `b` ON `a`.`term_id` = `b`.`id` WHERE `term` = :term
	ORDER BY `a`.`value` DESC LIMIT 30;"
);
$st2 = $pdo2->prepare("SELECT `id`,`text` FROM `answers` WHERE `all_text_id` = :all_text_id;");

foreach ($input as $k => &$v) {
	$v = strtolower(trim(preg_replace("/[^a-z0-9]/i", "", $v)));
	if (strlen($v) > 3) {
		$st->execute([":term" => $v]);
		$answers = [];
		while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
			$st2->execute([":all_text_id" => $r["all_text_id"]]);
			while ($rr = $st2->fetch(PDO::FETCH_NUM)) {
				$answers[] = $rr[0];
			}
		}
	}
}
sort($input);

print json_encode($input);


// $st = $pdo2->prepare("INSERT INTO `zinput` (`text`, `answers_id`, `created_at`, `updated_at`) VALUES (:_text, :answers_id, :created_at, NULL);");


