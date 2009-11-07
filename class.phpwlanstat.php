<?php
class PHPWLANstat {
	private $db;
	private $new = 0;

	public function PHPWLANstat(){
		// Initialize Database
		$this->db = new SQLiteDatabase(PWS_DATABASE);

		// Enter loop
		$this->mainloop();
	}

	private function mainloop(){
		while(true){
			$this->scan();

			echo "Waiting";
			for($i = 0; $i < PWS_SCANFREQ; $i++){
				echo ".";
				sleep(1);
			}
			echo "\n";
		}
	}

	private function scan(){
		$db = $this->db;
		echo "Start Scan...\n";
		$handle = popen('iwlist '.PWS_INTERFACE.' scanning 2>&1', 'r');
		$out = "";
		while(!feof($handle)){
			$out .= trim(fread($handle, 2096));
		}
		pclose($handle);
		$known = $new = 0;
		if(strpos($out, 'completed') !== false){
			// Filter output
			$array = preg_split('#Cell [0-9]+#i', $out);
			foreach($array as $part){
				preg_match('#Address: ([a-z0-9:]+)#i', $part, $treffer);
				if(!isset($treffer[1])) continue;
				$MAC = $treffer[1];

				preg_match('#ESSID:"(.*)"#i', $part, $treffer);
				$ESSID = $treffer[1];

				// Anonymize MAC
				$macparts = preg_replace('#([0-9abcdef]{2}):([0-9abcdef]{2}):([0-9abcdef]{2}):[0-9abcdef]{2}:[0-9abcdef]{2}:[0-9abcdef]{2}#i', '$1:$2:$3:**:**:**', $MAC);

				// Hash MAC
				$machash = sha1($MAC);

				// Encryption
				if(strpos($part, 'Encryption key:on'))
					$encrypted = 1;
				else
					$encrypted = 0;

				if(strpos($part, 'WPA Version'))
					$encryption = "WPA";
				elseif($encrypted == 1)
					$encryption = "Other"; // we cannot identify WEP with such an easy way

				if($db->query('SELECT encrypted FROM accesspoints WHERE machash = \''.$machash.'\'')->numRows() > 0){
					$known++;
				}else{
					$db->query('INSERT INTO accesspoints (machash, macparts, essid, encrypted, encryption) '
								.'VALUES (\''.$machash.'\', \''.$macparts.'\', \''.sqlite_escape_string($ESSID).'\', '.$encrypted.', \''.$encryption.'\')');
					$new++;
				}
			}
			echo "$new new accessports and $known known detected.\n";
		}else{
			echo "Scan failed! More details in error.log\n";
			file_put_contents('error.log', $out, FILE_APPEND);
		}
	}
}
/*
 string(2040) "eth2      Scan completed :
          Cell 01 - Address: 00:04:0E:D7:F0:4E
                    ESSID:"Lummerland"
                    Mode:Managed
                    Frequency:2.412 GHz (Channel 1)
                    Quality:1/5  Signal level:-86 dBm  Noise level:-92 dBm
                    IE: IEEE 802.11i/WPA2 Version 1
                        Group Cipher : TKIP
                        Pairwise Ciphers (1) : CCMP
                        Authentication Suites (1) : PSK
                    IE: WPA Version 1
                        Group Cipher : TKIP
                        Pairwise Ciphers (1) : TKIP
                        Authentication Suites (1) : PSK
                    Encryption key:on
                    Bit Rates:1 Mb/s; 2 Mb/s; 5.5 Mb/s; 11 Mb/s; 6 Mb/s
                              9 Mb/s; 12 Mb/s; 18 Mb/s; 24 Mb/s; 36 Mb/s
                              48 Mb/s; 54 Mb/s
          Cell 02 - Address: 00:1B:11:8A:A5:7C
                    ESSID:"Default"
                    Mode:Managed
                    Frequency:2.437 GHz (Channel 6)
                    Quality:1/5  Signal level:-85 dBm  Noise level:-92 dBm
                    Encryption key:on
                    Bit Rates:1 Mb/s; 2 Mb/s; 5.5 Mb/s; 11 Mb/s; 6 Mb/s
                              9 Mb/s; 12 Mb/s; 18 Mb/s; 24 Mb/s; 36 Mb/s
                              48 Mb/s; 54 Mb/s
          Cell 03 - Address: 00:13:49:D7:6F:2C
                    ESSID:"DSLWLANModem200"
                    Mode:Managed
                    Frequency:2.437 GHz (Channel 6)
                    Quality:1/5  Signal level:-89 dBm  Noise level:-92 dBm
                    IE: WPA Version 1
                        Group Cipher : TKIP
                        Pairwise Ciphers (1) : TKIP
                        Authentication Suites (1) : PSK
                    Encryption key:on
                    Bit Rates:1 Mb/s; 2 Mb/s; 5.5 Mb/s; 11 Mb/s; 6 Mb/s
                              9 Mb/s; 12 Mb/s; 18 Mb/s; 24 Mb/s; 36 Mb/s
                              48 Mb/s; 54 Mb/s"*/