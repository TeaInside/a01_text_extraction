<?php

require __DIR__."/init.php";

print "Connecting to DB2...";
$pdo2 = DB2::pdo();
$pdo2->exec("SET NAMES utf8mb4;");
print "OK\n";

$input = "menarik";
$input = preg_split("/\s/", $input);

$st = $pdo2->prepare(
	"INSERT INTO `terms` (`hit_count`, `term`, `calculated`, `created_at`, `updated_at`) VALUES (1, :term, '1', :created_at, NULL) ON DUPLICATE KEY UPDATE `updated_at` = :created_at, `calculated` = '1', `hit_count` = `hit_count` + 1;"
);
$st2 = $pdo2->prepare("SELECT `id`,LOWER(`main_text`) AS `text` FROM `all_text` WHERE `main_text` LIKE :term;");
$st3 = $pdo2->prepare(
	"INSERT INTO `tf` (`tf_hash`,`term_id`, `all_text_id`, `value`, `created_at`, `updated_at`) VALUES (:tf_hash, :term_id, :all_text_id, :value, :created_at, NULL) ON DUPLICATE KEY UPDATE `value` = :value, `updated_at` = :created_at;"
);

$i = 0;
$execData = [];
foreach ($input as $k => &$v) {
	$v = strtolower(trim(preg_replace("/[^a-z0-9]/i", "", $v)));
	if (strlen($v) > 3) {
		print "Calculating \"{$v}\"...";
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
		print "OK\n";
	}
}
