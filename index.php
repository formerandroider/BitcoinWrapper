<?php

require_once('bootstrap.php');

ob_start();

completeAction($_GET['action'], $body, $js, $extraHead);

function completeAction(&$action, &$body, &$js, &$extraHead)
{
	global $config;

	// Mod rewrite/nginx try_files clean URL's
	if (preg_match('#^/?([A-Z0-9]+)/?$#i', $action, $matches))
	{
		$action = $matches[1];
	}
	else if (!$action)
	{
		$action = 'index';
	}
	else
	{
		$action = '';
	}

	switch ($action)
	{
		case "index":
			$body .=
				"<div style=\"text-align: center;\">
					<p>This server is used only as a testbed, and as a bitcoin node.</p>

					<p>Please feel free to donate bitcoins as you see fit :)</p>

					<script src=\"js/cw/coin.js\"></script>
					<script>
						CoinWidgetCom.go({
							wallet_address: \"{$config['BitCoin']['donateaddress']}\"
							, currency: \"bitcoin\"
							, counter: \"amount\"
							, alignment: \"bc\"
							, qrcode: true
							, lbl_button: \"Donate\"
							, lbl_address: \"My Bitcoin Address:\"
							, lbl_count: \"donations\"
							, lbl_amount: \"BTC\"
						});
					</script>

					<noscript>You don't have javascript enabled, so you can't see the fancy button. You can still send me some BTC though - send them to {$config['BitCoin']['donateaddress']}.</noscript>

					<p><a href=\"/info\">View Info</a></p>
					<p><a href=\"/connections\">View Connections</a></p>
				</div>";
			break;
		case "info":
			$class = getBitCoin();

			$info = array();

			try
			{
				if (!$info = $class->callMethod('getinfo'))
				{
					error();
				}
			} catch (Exception $e)
			{
				error();
			}

			$allowedKeys = array(
				'balance' => 'Wallet Balance: ',
				'blocks' => 'Downloaded Blocks: ',
				'connections' => 'Connections: '
			);

			foreach ($allowedKeys as $key => $printable)
			{
				if (isset($info[$key]))
				{
					$body .= $printable . $info[$key] . "<br />";
				}
			}

			break;
		case "connections":
			$class = getBitCoin();

			$connections = array();

			try
			{
				$connections = $class->callMethod('getpeerinfo');
			} catch (Exception $e)
			{
				error();
			}

			if (!$connections)
			{
				error();
			}

			$body .= <<<TABLE
<table>
	<tr>
		<th>Hostname</th>
	</tr>
TABLE;
			foreach ($connections as $connection)
			{
				$body .= <<<CONN
	<tr>
		<td>{$connection['addr']}</td>
	</tr>
CONN;
			}

			$body .= "</table>";

			break;
		default:
			error('Invalid Action', 'The requested action (' . $action . ') is invalid!');
			break;
	}

	finalise($body, $js);
}

function finalise($body, $js, $extraHead = '')
{
	global $config;

	print "<html><head><title>{$config['Core']['title']} - " . ucfirst($_GET['action']) . "</title><script>$js</script>$extraHead</head>";
	print "<body>$body</body></html>";

	ob_end_flush();

	exit;
}