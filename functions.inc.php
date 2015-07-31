<?php

/**
 * Output error and stop execution immediately.
 *
 * @param string $title  Page Title
 * @param string $error  Error String
 * @param int    $status HTTP Status Code
 */
function error($title = 'An Error Occurred!', $error = 'An error occurred getting the data from the bitcoin server.', $status = 503)
{
	if (isset($_GET['noerror']))
	{
		return;
	}

	ob_clean();

	header("Status: $status");

	$title = htmlspecialchars($title);
	$error = htmlspecialchars($error);

	print <<<HTML
<html>
	<head>
		<title>$title</title>
	</head>
	<body>
		<p>$error</p>
	</body>
</html>
HTML;
	ob_end_flush();

	exit(1);
}

/**
 * @return BitCoin
 */
function getBitCoin()
{
	global $config;

	$bitcoin = new BitCoin($config['BitCoin']['rpcuser'], $config['BitCoin']['rpcpassword'],
		$config['BitCoin']['host']);
	$bitcoin->setProtocol(BitCoin::PROTOCOL_SSL);

	return $bitcoin;
}