<?php

require_once('functions.inc.php');
require_once('BitCoin.php');

$config = parse_ini_file('config.ini.php', true);

if ($config['Core']['mode'] > 0)
{
	error_reporting(E_ALL);
	@ini_set('display_errors', 1);
	@ini_set('display_startup_errors', 1);
}
else
{
	error_reporting(0);
	@ini_set('display_errors', 0);
	@ini_set('display_startup_errors', 0);
}