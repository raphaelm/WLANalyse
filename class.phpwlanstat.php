<?php
/*
 *      class.phpwlanstat.php
 *      part of WLANalyse
 *
 *      Author: Raphael Michel <webmaster@raphaelmichel.de>
 *      Copyright: 2009 geek's factory
 *      License: MIT-Lizenz
 */

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
			echo "$new new accesspoints and $known known detected.\n";
		}else{
			echo "Scan failed! More details in error.log\n";
			file_put_contents('error.log', $out, FILE_APPEND);
		}
	}
}