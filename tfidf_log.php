<?php

require __DIR__."/init.php";

print "Connecting to DB2...";
$pdo2 = DB2::pdo();
$pdo2->exec("SET NAMES utf8mb4;");
print "OK\n";

$i = 0;
$offset = 0;
while (true):
	$i++;
	$found = false;
	print "Querying terms...";
	$st1 = $pdo2->prepare(
		"SELECT `t`.`id`,LOWER(`t`.`term`) AS `term` FROM
			(SELECT `id` FROM `terms` WHERE `calculated` = '0' LIMIT 30 OFFSET {$offset}) AS `u`
		INNER JOIN `terms` AS `t` ON `u`.`id` = `t`.`id`;"
	);
	$st2 = $pdo2->prepare("SELECT `id`,LOWER(`main_text`) AS `text` FROM `all_text` WHERE `main_text` LIKE :term;");
	$st3 = $pdo2->prepare(
		"INSERT INTO `tf` (`tf_hash`,`term_id`, `all_text_id`, `value`, `created_at`, `updated_at`) VALUES (:tf_hash, :term_id, :all_text_id, :value, :created_at, NULL) ON DUPLICATE KEY UPDATE `value` = :value, `updated_at` = :created_at;"
	);
	$st1->execute();
	$c = 0;
	while ($r = $st1->fetch(PDO::FETCH_ASSOC)) {
		$c++;
		$r["term"] = trim($r["term"]);
		print "Calculating term \"{$r["term"]}\"...";
		$st2->execute([":term" => "%{$r["term"]}%"]);
		while ($rr = $st2->fetch(PDO::FETCH_ASSOC)) {
			$rr["text"] = trim($rr["text"]);
			$ftd = 0;
			str_ireplace($r["term"], "", $rr["text"], $ftd);
			unset($rr["text"]);
			$tf = 1 + log10($ftd);
			$st3->execute(
				[
					":tf_hash" => md5(json_encode(
						[
							"a" => $r["id"],
							"b" => $rr["id"]
						]
					)),
					":term_id" => $r["id"],
					":all_text_id" => $rr["id"],
					":value" => $tf,
					":created_at" => date("Y-m-d H:i:s")
				]
			);
		}
		print "OK\n";
	}
	$st1 = $st2 = $st3 = null;
	

	if ($found) {
		$offset += $c;
	} else {
		print "Didn't find any new data...\n";
	}

	if (($i % 30) === 0) {
		print "Closing all DB2 connection...";
		$pdo2 = null;
		DB2::close();
		print "OK\n";

		print "Sleeping for ".A01_SLEEP_DELAY." seconds";
		for ($i=0; $i < A01_SLEEP_DELAY; $i++) { 
			print ".";
			sleep(1);
		}
		print "OK\n";
		print "Preparing to reopen connection...\n";

		print "Connecting to DB2...";
		$pdo2 = DB2::pdo();
		print "OK\n";
	}

endwhile;
