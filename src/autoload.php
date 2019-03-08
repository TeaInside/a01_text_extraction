<?php

if (!defined("AUTOLOAD_PHP")):
define("AUTOLOAD_PHP", true);

/**
 * @param string $class
 * @return void
 */
function myInternalAutoload(string $class)
{
	$class = str_replace("\\", "/", $class);
	require __DIR__."/classes/{$class}.php";
}

spl_autoload_register("myInternalAutoload");

require __DIR__."/helpers.php";

endif;
