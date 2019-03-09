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
$lost_count = 0;
while (true):
	$i++;
	$found = false;
	print "Querying group_messages...";
	$st1 = $pdo1->prepare(
		"SELECT 	
			`t`.`group_id` AS `group_id`,
			`t`.`reply_to_tmsg_id` AS `reply_to_msg_id`,
			`t`.`tmsg_id` AS `tmsg_id`,
			`t`.`text` AS `reply`,	
			`v`.`text` AS `msg`
		FROM 
		(SELECT `id` FROM `group_messages` WHERE `reply_to_tmsg_id` IS NOT NULL ORDER BY `id` ASC LIMIT 500 OFFSET {$offset}) AS `u`
		INNER JOIN `group_messages` AS `t` ON 
			`u`.`id` = `t`.`id` 
		INNER JOIN `group_messages` AS `v` ON
			`v`.`group_id` = `t`.`group_id` AND `v`.`tmsg_id` = `t`.`reply_to_tmsg_id`
		WHERE `v`.`text` IS NOT NULL;"
	);
	print "OK\n";
	$st2 = $pdo2->prepare(
		"INSERT IGNORE INTO `all_text` (`hash_text`, `main_text`, `created_at`) VALUES (:hash_text, :main_text, :created_at);"
	);
	$st3 = $pdo2->prepare(
		"INSERT IGNORE INTO `replies` (`all_text_id`, `hash_text`, `text`, `created_at`) VALUES ((SELECT `id` FROM `all_text` WHERE `hash_text` = :hash_all_text LIMIT 1), :hash_text, :_text, :created_at);"
	);
	print "Analyzing data...\n";
	$st1->execute();
	$c = 0;
	while ($r = $st1->fetch(PDO::FETCH_ASSOC)) {
		$c++;
		print "Collecting data...";
		if (strlen($r["reply"]) > 0) {
			$all_text_hash = sha1($r["msg"]);
			$reply_hash = sha1(json_encode(
				[
					"a" => $all_text_hash,
					"b" => $r["reply"],
					"c" => $r["group_id"],
					"d" => $r["tmsg_id"]
				]
			));
			$st2->execute(
				[
					":hash_text" => $all_text_hash,
					":main_text" => $r["msg"],
					":created_at" => date("Y-m-d H:i:s")
				]
			);
			$st3->execute(
				[
					":hash_all_text" => $all_text_hash,
					":hash_text" => $reply_hash,
					":_text" => $r["reply"],
					":created_at" => date("Y-m-d H:i:s")
				]
			);
			print "OK\n";
		}
	}

	$st1 = $st2 = $st3 = $st4 = null;
	if ($c > 0) {
		$offset += $c;
	} else {
		print "Didn't find any new data...\n";

		$lost_count++;

		if ($lost_count >= 60) {
			print "Reached the max numbers of lost...\n";
			print "Reset offset to 0...OK\n";
			$offset = 0;
			$lost_count = 0;
		}
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
