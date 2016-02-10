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

				$js .= $this->_config['FrontEnd']['extrajs'];

				$this->_printOutput($body, $js, $head, $action);
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
		$bitcoin = $this->_getBitcoin();

		return array(
			"<div style=\"text-align: center;\">
					<p>This server is used pretty much solely as a bitcoin node.</p>

					<p>Please feel free to donate bitcoins as you see fit, to help keep this individual node running... :)</p>

					<div class=\"inlineInfo\">
						<p><span>Balance: <a href=\"{$this->_getUrl('transactions')}\">{$bitcoin->getbalance()}</a></span> <span>Connections: <a href='{$this->_getUrl('connections')}'>{$bitcoin->getConnectionCount()}</a></span></p>
					</div>

					<script src=\"js/cw/coin.js\"></script>
					<script>
						CoinWidgetCom.go({
							wallet_address: \"{$this->_config['FrontEnd']['donateaddress']}\"
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

					<noscript>You don't have javascript enabled, so you can't see the fancy button. You can still send me some BTC though - send them to {$this->_config['FrontEnd']['donateaddress']} (if you want).</noscript>
				</div>",
			false,
			false
		);
	}

	public function actionAddNode()
	{
		$this->_error();

		$this->_assertMethod('POST');

		if (!filter_var($_POST['node_ip'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))
		{
			$this->_error('Invalid IP', 'The entered node IP could not be validated!');
		}

		$this->_getBitcoin()->addNode($_POST['node_ip'], 'onetry');

		header('Location: ' . $this->_getUrl(''));

		return array(
			false,
			false,
			false
		);
	}

	public function actionTransactions()
	{
		$bitcoin = $this->_getBitcoin();

		$txs = $bitcoin->listtransactions("", 50);

		uasort($txs, function (array $a, array $b)
		{
			$sortRow = isset($_GET['sort']) ? $_GET['sort'] : 'time';

			if ($sortRow != 'time' && $sortRow != 'amount')
			{
				$sortRow = 'time';
			}

			return $b[$sortRow] <=> $a[$sortRow];
		});

		$body = <<<BODY

		<table>
			<tr>
				<th>TX ID (Blockchain Link)</th>
				<th>Address</th>
				<th>Amount</th>
				<th>Date/Time (D/M/Y, London Time)</th>
			</tr>
BODY;

		foreach ($txs as $tx)
		{
			if ($tx['category'] != 'send' && $tx['category'] != 'receive')
			{
				continue;
			}

			$date = new DateTime();
			$date->setTimezone(new DateTimeZone('Europe/London'));
			$date->setTimestamp($tx['time']);

			$body .= <<<TX
			<tr>
				<td><a href="https://blockchain.info/tx/{$tx['txid']}">Click Me</a></td>
				<td>{$tx['address']}</td>
				<td>{$tx['amount']}</td>
				<td>{$date->format('d/m/Y g:i A')}</td>
			</tr>
TX;
		}

		$body .= <<<ENDB
		</table>
ENDB;

		return array(
			$body,
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

		return array(
			$body,
			false,
			false
		);
	}

	protected function _getAction()
	{
		$action = isset($_GET['action']) ? $_GET['action'] : 'index';

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
<table id="connections">
	<tr>
		<th>Host</th>
		<th>IP</th>
	</tr>
TABLE;
		}
		else
		{
			$body = <<<TABLE
<table id="connections">
	<tr>
		<th>IP</th>
	</tr>
TABLE;
		}

		foreach ($connections as $connection)
		{
			if ($this->_config['FrontEnd']['resolvehostname'])
			{
				if (substr_count($connection['addr'], ':') > 1)
				{
					$parts = explode(']', $connection['addr']);
					$parts[0] = substr($parts[0], '1');

					$parts[1] = str_replace(':', '', $parts[1]);
				}
				else
				{
					$parts = explode(':', $connection['addr']);
				}

				$hostname = gethostbyaddr($parts[0]);

				$body .= <<<CONN
	<tr>
		<td>{$hostname}</td>
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

		return array(
			$body,
			false,
			false
		);
	}

	protected function _getUrl($action)
	{
		if ($action == '')
		{
			return '/';
		}

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

	protected function _printOutput($body, $js, $head, $action)
	{
		print "<html><head><title>{$this->_config['FrontEnd']['title']} - " . ucfirst($action) . "</title>$js$head<link rel=\"stylesheet\" href=\"bitcoin.css\" /></head>";
		print "<body>$body</body></html>";
	}

	protected function _getBitcoin()
	{
		$bitcoin = new BitCoin($this->_config['BitCoin']['rpcuser'], $this->_config['BitCoin']['rpcpassword'],
			$this->_config['BitCoin']['host']);
		$bitcoin->setProtocol($this->_config['BitCoin']['rpcssl'] ? BitCoin::PROTOCOL_SSL : BitCoin::PROTOCOL_HTTP);

		return $bitcoin;
	}

	private function _assertMethod($string)
	{
		if ($_SERVER['REQUEST_METHOD'] != $string)
		{
			$this->_error('Invalid Method', 'This action is not available via the request method used.', 405);
		}
	}
}