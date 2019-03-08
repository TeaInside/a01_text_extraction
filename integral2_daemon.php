<?php

require __DIR__."/init.php";

print "Connecting to DB1...";
$pdo1  = DB1::pdo();
$pdo1->exec("SET NAMES utf8mb4;");
print "OK\n";

print "Connecting to DB2...";
$pdo2 = DB2::pdo();
$pdo2->exec("SET NAMES utf8mb4;");
print "OK\n";

$i = 0;
$offset = 0;
while (true):
	$i++;
	$found = false;
	print "Querying group_messages...";
	$st1 = $pdo1->prepare(
		"SELECT `t`.`group_id`,`t`.`reply_to_tmsg_id`,`t`.`text` FROM 
			(SELECT `id` FROM `group_messages` WHERE `reply_to_tmsg_id` IS NOT NULL ORDER BY `id` ASC LIMIT 100 OFFSET {$offset}) AS `u`
		INNER JOIN `group_messages` AS `t` ON `u`.`id` = `t`.`id`;"
	);
	print "OK\n";


	$st2 = $pdo2->prepare(
		"INSERT IGNORE INTO `all_text` (`hash_text`, `main_text`, `hit_count`, `created_at`, `updated_at`) VALUES (:hash_text, :main_text, 0, :created_at, NULL);"
	);
	$st3 = $pdo1->prepare("SELECT `text` FROM `group_messages` WHERE `group_id` = :group_id AND `tmsg_id` = :tmsg_id LIMIT 1;");
	$st4 = $pdo2->prepare(
		"INSERT IGNORE INTO `answers` (`all_text_id`, `hash_text`, `text`, `hit_count`, `created_at`, `updated_at`) VALUES ((SELECT `id` FROM `all_text` WHERE `hash_text` = :hash_all_text LIMIT 1), :hash_text, :_text, 0, :created_at, NULL);"
	);
	print "Analyzing data...\n";
	$st1->execute();
	$c = 0;
	while ($r = $st1->fetch(PDO::FETCH_ASSOC)) {
		$c++;
		print "Collecting data...";
		$st3->execute([":group_id" => $r["group_id"], "tmsg_id" => $r["reply_to_tmsg_id"]]);
		if ($rr = $st3->fetch(PDO::FETCH_ASSOC)) {
			$answer_hash = sha1(json_encode(
				[
					"a" => $r["reply_to_tmsg_id"],
					"b" => $r["text"],
					"c" => $r["group_id"]
				]
			));
			$all_text_hash = sha1($rr["text"]);
			$st2->execute(
				[
					":hash_text" => $all_text_hash,
					":main_text" => $rr["text"],
					":created_at" => date("Y-m-d H:i:s")
				]
			);
			$st4->execute(
				[
					":hash_all_text" => $all_text_hash,
					":hash_text" => $answer_hash,
					":_text" => $r["text"],
					":created_at" => date("Y-m-d H:i:s")
				]
			);
			print "OK\n";
		} else {
			print "(No reply) Skip\n";
		}
	}

	$st1 = $st2 = $st3 = null;
	if ($c > 0) {
		$offset += $c;
	} else {
		print "Didn't find any new data...\n";
	}

	print "Analyze completed!\n";
	if (($i % 30) === 0) {
		print "Closing all DB1 connection...";
		$pdo1 = null;
		DB1::close();
		print "OK\n";
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

		print "Connecting to DB1...";
		$pdo1  = DB1::pdo();
		print "OK\n";
		print "Connecting to DB2...";
		$pdo2 = DB2::pdo();
		print "OK\n";
	}
endwhile;
