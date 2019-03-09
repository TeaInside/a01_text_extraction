<?php

if(!defined("CONFIG_PHP")):
define("CONFIG_PHP", true);

define("PDO_1_PARAMETERS",
	[
		"mysql:host=192.168.50.2;port=9999;dbname=bot_telegram_s5",
		"memcpy",
		"858869123qweASDzxc",
		[
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		]
	]
);

define("PDO_2_PARAMETERS",
	[
		"mysql:host=192.168.50.2;port=9999;dbname=a01_nlp",
		"memcpy",
		"858869123qweASDzxc",
		[
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		]
	]
);

define("A01_SLEEP_DELAY", 300);

endif;
