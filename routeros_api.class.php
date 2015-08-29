<?php
//
// RouterOS API class
// Author: Denis Basta
//
// read() function altered by Nick Barnes to take into account the placing
// of the "!done" reply and also correct calculation of the reply length.
///
// read() function altered by Ben Menking (ben@infotechsc.com); removed
// echo statement that dumped byte data to screen
//
///////////////////////////
// Revised by: Jeremy Jefferson (http://jeremyj.com)
// January 8, 2010
//
//	Fixed write function in order to allow for queries to be executed
//

class routeros_api {

	var $debug = false;			// Show debug information
	var $error_no;				// Variable for storing connection error number, if any
	var $error_str;				// Variable for storing connection error text, if any

	var $attempts = 5;			// Connection attempt count
	var $connected = false;		// Connection state
	var $delay = 3;				// Delay between connection attempts in seconds
	var $port = 8728;			// Port to connect to
	var $timeout = 3;			// Connection attempt timeout and data read timeout

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

	function encode_length($length) {

		if ($length < 0x80) {

			$length = chr($length);

		}
		else
		if ($length < 0x4000) {

			$length |= 0x8000;

			$length = chr( ($length >> 8) & 0xFF) . chr($length & 0xFF);

		}
		else
		if ($length < 0x200000) {

			$length |= 0xC00000;

			$length = chr( ($length >> 16) & 0xFF) . chr( ($length >> 8) & 0xFF) . chr($length & 0xFF);

		}
		else
		if ($length < 0x10000000) {

			$length |= 0xE0000000;

			$length = chr( ($length >> 24) & 0xFF) . chr( ($length >> 16) & 0xFF) . chr( ($length >> 8) & 0xFF) . chr($length & 0xFF);

		}
		else
		if ($length >= 0x10000000)
			$length = chr(0xF0) . chr( ($length >> 24) & 0xFF) . chr( ($length >> 16) & 0xFF) . chr( ($length >> 8) & 0xFF) . chr($length & 0xFF);

		return $length;

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
							$this->write('=response=00' . md5(chr(0) . $password . pack('H*', $MATCHES[0][1]) ) );

							$RESPONSE = $this->read(false);

							if ($RESPONSE[0] == '!done') {

								$this->connected = true;

								break;

							}

						}

					}

				}

				fclose($this->socket);

			}

			sleep($this->delay);

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

			foreach ($response as $x) {

				if (in_array($x, array('!fatal', '!re', '!trap') ) ) {

					if ($x == '!re')
						$CURRENT = &$PARSED[];
					else
						$CURRENT = &$PARSED[$x][];

				}
				else
				if ($x != '!done') {

					if (preg_match_all('/[^=]+/i', $x, $MATCHES) )
						$CURRENT[$MATCHES[0][0]] = (isset($MATCHES[0][1]) ? $MATCHES[0][1] : '');

				}

			}

			return $PARSED;

		}
		else
			return array();

	}

	/**************************************************
	 *
	 *************************************************/

        function array_change_key_name(&$array) {
                if (is_array($array) ) {
                        foreach ($array as $k => $v) {
                                $tmp = str_replace("-","_",$k);
                                $tmp = str_replace("/","_",$tmp);
                                if ($tmp) {
                                        $array_new[$tmp] = $v;
                                } else {
                                        $array_new[$k] = $v;
                                }
                        }
                        return $array_new;
                } else {
                        return $array;
                }
        }

        /**************************************************
         *
         *************************************************/

        function parse_response4smarty($response) {
                if (is_array($response) ) {
                        $PARSED = array();
                        $CURRENT = null;
                        foreach ($response as $x) {
                                if (in_array($x, array('!fatal', '!re', '!trap') ) ) {
                                        if ($x == '!re')
                                                $CURRENT = &$PARSED[];
                                        else
                                                $CURRENT = &$PARSED[$x][];
                                }
                                else
                                if ($x != '!done') {
                                        if (preg_match_all('/[^=]+/i', $x, $MATCHES) )
                                                $CURRENT[$MATCHES[0][0]] = (isset($MATCHES[0][1]) ? $MATCHES[0][1] : '');
                                }
                        }
                        foreach ($PARSED as $key => $value) {
                                $PARSED[$key] = $this->array_change_key_name($value);
                        }
                        return $PARSED;
                }
                else {
                        return array();
                }
        }

	/**************************************************
	 *
	 *************************************************/

   function read($parse = true) {

      $RESPONSE = array();

      while (true) {

         // Read the first byte of input which gives us some or all of the length
         // of the remaining reply.
         $BYTE = ord(fread($this->socket, 1) );
         $LENGTH = 0;

         // If the first bit is set then we need to remove the first four bits, shift left 8
         // and then read another byte in.
         // We repeat this for the second and third bits.
         // If the fourth bit is set, we need to remove anything left in the first byte
         // and then read in yet another byte.
         if ($BYTE & 128) {
            if (($BYTE & 192) == 128) {
               $LENGTH = (($BYTE & 63) << 8 ) + ord(fread($this->socket, 1)) ;
            } else {
               if (($BYTE & 224) == 192) {
                  $LENGTH = (($BYTE & 31) << 8 ) + ord(fread($this->socket, 1)) ;
                  $LENGTH = ($LENGTH << 8 ) + ord(fread($this->socket, 1)) ;
               } else {
                  if (($BYTE & 240) == 224) {
                     $LENGTH = (($BYTE & 15) << 8 ) + ord(fread($this->socket, 1)) ;
                     $LENGTH = ($LENGTH << 8 ) + ord(fread($this->socket, 1)) ;
                     $LENGTH = ($LENGTH << 8 ) + ord(fread($this->socket, 1)) ;
                  } else {
                     $LENGTH = ord(fread($this->socket, 1)) ;
                     $LENGTH = ($LENGTH << 8 ) + ord(fread($this->socket, 1)) ;
                     $LENGTH = ($LENGTH << 8 ) + ord(fread($this->socket, 1)) ;
                     $LENGTH = ($LENGTH << 8 ) + ord(fread($this->socket, 1)) ;
                  }
               }
            }
         } else {
            $LENGTH = $BYTE;
         }

         // If we have got more characters to read, read them in.
         if ($LENGTH > 0) {
            $_ = "";
            $retlen=0;
            while ($retlen < $LENGTH) {
               $toread = $LENGTH - $retlen ;
               $_ .= fread($this->socket, $toread);
               $retlen = strlen($_);
            }
            $RESPONSE[] = $_ ;
            $this->debug('>>> [' . $retlen . '/' . $LENGTH . ' bytes read.');
         }

         // If we get a !done, make a note of it.
         if ($_ == "!done")
            $receiveddone=true;

         $STATUS = socket_get_status($this->socket);

         
         if ($LENGTH > 0)
            $this->debug('>>> [' . $LENGTH . ', ' . $STATUS['unread_bytes'] . '] ' . $_);

         if ( (!$this->connected && !$STATUS['unread_bytes']) ||
            ($this->connected && !$STATUS['unread_bytes'] && $receiveddone) )
            break;

      }

      if ($parse)
         $RESPONSE = $this->parse_response($RESPONSE);

      return $RESPONSE;

   }
	/**************************************************
	 *
	 *************************************************/

	function write($command, $param2 = true) {

		if ($command) {
			
			$data = explode("\n",$command);
			
			foreach ($data as $com) {
				$com = trim($com);
			        fwrite($this->socket, $this->encode_length(strlen($com) ) . $com);
			        $this->debug('<<< [' . strlen($com) . '] ' . $com);
			}

			

			if (gettype($param2) == 'integer') {

				fwrite($this->socket, $this->encode_length(strlen('.tag=' . $param2) ) . '.tag=' . $param2 . chr(0) );

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
