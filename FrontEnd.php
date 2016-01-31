<?php

class FrontEnd
{
	protected $_config;

	public function __construct(array $config)
	{
		$this->_config = $config;
	}

	public function dispatch()
	{
		ob_start();

		$action = $this->_getAction();

		if ($action === false)
		{
			$this->_error("Invalid Action", "The action you requested is invalid.", 500);
		}
		else
		{
			$method = "action" . ucwords($action, "-/");

			if (method_exists($this, $method))
			{
				list($body, $js, $head) = $this->{$method}();

				$this->_printOutput($body, $js, $head);
			}
			else
			{
				$action = htmlspecialchars($action);
				$this->_error("Invalid Action", "The action you requested (<em>$action</em>) could not be found.", 404,
					true);
			}
		}

		ob_end_flush();
	}

	public function actionIndex()
	{
		return array(
			"<div style=\"text-align: center;\">
					<p>This server is used only as a testbed, and as a bitcoin node.</p>

					<p>Please feel free to donate bitcoins as you see fit :)</p>

					<script src=\"js/cw/coin.js\"></script>
					<script>
						CoinWidgetCom.go({
							wallet_address: \"{$this->_config['BitCoin']['donateaddress']}\"
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

					<noscript>You don't have javascript enabled, so you can't see the fancy button. You can still send me some BTC though - send them to {$this->_config['BitCoin']['donateaddress']}.</noscript>

					<p><a href=\"{$this->_getUrl('info')}\">View Info</a></p>
					<p><a href=\"{$this->_getUrl('connections')}\">View Connections</a></p>
				</div>",
			false,
			false
		);
	}

	public function actionInfo()
	{
		$class = $this->_getBitcoin();

		$info = array();

		try
		{
			if (!$info = $class->callMethod('getinfo'))
			{
				$this->_error();
			}
		} catch (Exception $e)
		{
			$this->_error();
		}

		$allowedKeys = array(
			'balance' => 'Wallet Balance: ',
			'blocks' => 'Downloaded Blocks: ',
			'connections' => 'Connections: '
		);

		$body = '';

		foreach ($allowedKeys as $key => $printable)
		{
			if (isset($info[$key]))
			{
				$body .= $printable . $info[$key] . "<br />";
			}
		}

		return $body;
	}

	protected function _getAction()
	{
		$action = $_GET['action'] ?: 'index';

		if (preg_match('#^/?([A-Z0-9\/]+)/?$#i', $action, $matches))
		{
			$action = $matches[1];
		}
		else if (!$action)
		{
			$action = 'index';
		}
		else
		{
			$action = false;
		}

		return $action;
	}

	public function actionConnections()
	{
		$class = $this->_getBitcoin();

		$connections = array();

		try
		{
			$connections = $class->callMethod('getpeerinfo');
		} catch (Exception $e)
		{
			$this->_error();
		}

		if (!$connections)
		{
			$this->_error();
		}

		if ($this->_config['FrontEnd']['resolvehostname'])
		{
			$body = <<<TABLE
<table>
	<tr>
		<th>Host</th>
	</tr>
	<tr>
		<th>IP</th>
	</tr>
TABLE;
		}
		else
		{
			$body = <<<TABLE
<table>
	<tr>
		<th>IP</th>
	</tr>
TABLE;
		}

		foreach ($connections as $connection)
		{
			if ($this->_config['FrontEnd']['resolvehostname'])
			{
				$hostname = Utils::gethostbyaddr_timeout(reset(explode(':', $connection['addr'])),
					$this->_config['FrontEnd']['resolver'], $this->_config['FrontEnd']['resolvetimeout']);

				$body .= <<<CONN
	<tr>
		<td>{$hostname}</td>
	</tr>
	<tr>
		<td>{$connection['addr']}</td>
	</tr>
CONN;
			}
			else
			{
				$body .= <<<CONN
	<tr>
		<td>{$connection['addr']}</td>
	</tr>
CONN;
			}
		}

		$body .= "</table>";

		return $body;
	}

	protected function _getUrl($action)
	{
		if ($this->_config['FrontEnd']['friendlyurl'])
		{
			return $action;
		}
		else
		{
			return 'index.php?action=' . $action;
		}
	}

	protected function _error($title = "Temporarily Unavailable", $error = "The bitcoin server appears to be down or running slowly. Please try again later.", $status = 503, $raw = false)
	{
		if (isset($_GET['noerror']))
		{
			return;
		}

		ob_clean();

		header("Status: $status");

		if (!$raw)
		{
			$title = htmlspecialchars($title);
			$error = htmlspecialchars($error);
		}

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

	protected function _set404()
	{
		header('Status: 404 Not Found');
	}

	protected function _printOutput($body, $js, $head)
	{
		print "<html><head><title>{$this->_config['FrontEnd']['title']} - " . ucfirst($_GET['action']) . "</title><script>$js</script>$head</head>";
		print "<body>$body</body></html>";
	}

	protected function _getBitcoin()
	{
		$bitcoin = new BitCoin($this->_config['BitCoin']['rpcuser'], $this->_config['BitCoin']['rpcpassword'],
			$this->_config['BitCoin']['host']);
		$bitcoin->setProtocol($this->_config['BitCoin']['rpcssl'] ? BitCoin::PROTOCOL_SSL : BitCoin::PROTOCOL_HTTP);

		return $bitcoin;
	}
}