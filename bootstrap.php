<?php

require_once('FrontEnd.php');
require_once('BitCoin.php');

$config = parse_ini_file('config.ini.php', true, PHP_VERSION_ID >= 50601 ? INI_SCANNER_TYPED : INI_SCANNER_NORMAL);

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

if (!$config['BitCoin']['rpcuser'] || !$config['BitCoin']['rpcpassword'] || !$config['FrontEnd']['donateaddress'] || !$config['FrontEnd']['title'])
{
	die('Please configure the system by editing the config.ini.php file.');
}