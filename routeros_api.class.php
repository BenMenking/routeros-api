<?php

//
// RouterOS API class
// Author: Denis Basta
//

class routeros_api {

	var $debug = false;			// Show debug information
	var $error_no;				// Variable for storing connection error number, if any
	var $error_str;				// Variable for storing connection error text, if any

	var $attempts = 5;			// Connection attempt count
	var $connected = false;		// Connection state
	var $delay = 3;				// Delay between connection attempts in seconds
	var $port = 8728;			// Port to connect to
	var $timeout = 5;			// Connection attempt timeout and data read timeout

	var $socket;				// Variable for storing socket resource

	/**************************************************
	 *
	 *************************************************/

	function debug($text) {

		if ($this->debug)
			echo $text . "\n";

	}

	/**************************************************
	 *
	 *************************************************/

	function encode_length($command) {

		$LENGTH = strlen($command);

		if ($LENGTH < 0x80) {

			$LENGTH = chr($LENGTH);

		}
		else
		if ($LENGTH < 0x4000) {

			$LENGTH |= 0x8000;

			$LENGTH = chr( ($LENGTH >> 8) & 0xFF) . chr($LENGTH & 0xFF);

		}
		else
		if ($LENGTH < 0x200000) {

			$LENGTH |= 0xC00000;

			$LENGTH = chr( ($LENGTH >> 8) & 0xFF) . chr( ($LENGTH >> 8) & 0xFF) . chr($LENGTH & 0xFF);

		}
		else
		if ($LENGTH < 0x10000000) {

			$LENGTH |= 0xE0000000;

			$LENGTH = chr( ($LENGTH >> 8) & 0xFF) . chr( ($LENGTH >> 8) & 0xFF) . chr( ($LENGTH >> 8) & 0xFF) . chr($LENGTH & 0xFF);

		}
		else
		if ($LENGTH < 0x10000000) {

			$LENGTH |= 0xE0000000;

			$LENGTH = chr( ($LENGTH >> 8) & 0xFF) . chr( ($LENGTH >> 8) & 0xFF) . chr( ($LENGTH >> 8) & 0xFF) . chr($LENGTH & 0xFF);

		}

		return $LENGTH;

	}

	/**************************************************
	 *
	 *************************************************/

	function connect($ip, $login, $password) {

		for ($ATTEMPT = 1; $ATTEMPT <= $this->attempts; $ATTEMPT++) {

			$this->connected = false;

			$this->debug('Connection attempt #' . $ATTEMPT . ' to ' . $ip . ':' . $this->port . '...');

			if ($this->socket = @fsockopen($ip, $this->port, $this->error_no, $this->error_str, $this->timeout) ) {

				socket_set_timeout($this->socket, $this->timeout);

				$this->write('/login');

				$RESPONSE = $this->read(false);

				if ($RESPONSE[0] == '!done') {

					if (preg_match_all('/[^=]+/i', $RESPONSE[1], $MATCHES) ) {

						if ($MATCHES[0][0] == 'ret' && strlen($MATCHES[0][1]) == 32) {

							$this->write('/login', false);
							$this->write('=name=' . $login, false);
							$this->write('=response=00' . md5(chr(0) . $password . pack('H*', $MATCHES[0][1]) ));

							$RESPONSE = $this->read(false);

							if ($RESPONSE[0] == '!done') {

								$this->connected = true;

								break;

							}

						}

					}

				}

				fclose($this->socket);

				sleep($this->delay);

			}

		}

		if ($this->connected)
			$this->debug('Connected...');
		else
			$this->debug('Error...');

		return $this->connected;

	}

	/**************************************************
	 *
	 *************************************************/

	function disconnect() {

		fclose($this->socket);

		$this->connected = false;

		$this->debug('Disconnected...');

	}

	/**************************************************
	 *
	 *************************************************/

	function parse_response($response) {

		if (is_array($response) ) {

			$PARSED = array();
			$CURRENT = null;

			for ($i = 0, $imax = count($response); $i < $imax; $i++) {

				if (in_array($response[$i], array('!fatal', '!re', '!trap') )) {

					if ($response[$i] == '!re')
						$CURRENT = &$PARSED[];
					else
						$CURRENT = &$PARSED[$response[$i]][];

				}
				else
				if ($response[$i] != '!done') {

					if (preg_match_all('/[^=]+/i', $response[$i], $MATCHES) ) {

						$CURRENT[$MATCHES[0][0]] = $MATCHES[0][1];

					}

				}

			}

			return (count($PARSED) == 1 ? $PARSED[0] : $PARSED);

		}
		else
			return array();

	}

	/**************************************************
	 *
	 *************************************************/

	function read($parse = true) {

		$RESPONSE = array();

		do {

			$LENGTH = ord(fread($this->socket, 1) );

			if ($LENGTH) {

				$_ = fread($this->socket, $LENGTH);

				$RESPONSE[] = $_;

				$this->debug('>>> [' . $LENGTH . '] ' . $_);

			}

			$STATUS = socket_get_status($this->socket);

		} while ($STATUS['unread_bytes']);

		if ($parse)
			$RESPONSE = $this->parse_response($RESPONSE);

		return $RESPONSE;

	}

	/**************************************************
	 *
	 *************************************************/

	function write($command, $param2 = true) {

		if ($command) {

			fputs($this->socket, $this->encode_length($command) . $command);

			$this->debug('<<< [' . strlen($command) . '] ' . $command);

			if (gettype($param2) == 'integer') {

				fwrite($this->socket, $this->encode_length('.tag=' . $param2) . '.tag=' . $param2 . chr(0) );

				$this->debug('<<< [' . strlen('.tag=' . $param2) . '] .tag=' . $param2);

			}
			else
			if (gettype($param2) == 'boolean')
				fwrite($this->socket, ($param2 ? chr(0) : '') );

			return true;

		}
		else
			return false;

	}

}

?>