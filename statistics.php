<?php
/*
 *      statistics.php
 *      part of WLANalyse
 *
 *      Author: Raphael Michel <webmaster@raphaelmichel.de>
 *      Copyright: 2009 geek's factory
 *      License: MIT-Lizenz
 */

if(!file_exists('config.inc.php'))
	die("File config.inc.php not found!\n");

if(!is_dir('stats')) mkdir('stats');

include('config.inc.php');

// Check for compatibility
if(!version_compare(PHP_VERSION, '5.0.0', '>='))
	die("I need PHP version 5 or greater!\n");

if(!class_exists('SQLiteDatabase'))
	die("Cannot find PHP-SQLite extension!\n");

if(!file_exists(PWS_DATABASE))
	die("The database file was not found!\n");

$db = new SQLiteDatabase(PWS_DATABASE);

/////////////////////// ENCRYPTED
$protected = $db->query('SELECT encrypted FROM accesspoints WHERE encrypted = 1')->numRows();
$unprotected = $db->query('SELECT encrypted FROM accesspoints WHERE encrypted = 0')->numRows();
$total = $protected+$unprotected;

$part_p = $protected/$total;
$part_u = $unprotected/$total;

$arc_p = $part_p*360;
$arc_u = $part_u*360;

$image = imagecreatetruecolor(380, 202);

// allocate some solors
$white    = imagecolorallocate($image, 0xFF, 0xFF, 0xFF);
$darkred  = imagecolorallocate($image, 0x99, 0x00, 0x00);
$darkgreen= imagecolorallocate($image, 0x00, 0x99, 0x00);

imagefill($image, 0, 0, $white);

imagefilledarc($image, 100, 100, 200, 200, 0, $arc_p, $darkred, IMG_ARC_PIE);
imagefilledarc($image, 100, 100, 200, 200, $arc_p, 360 , $darkgreen, IMG_ARC_PIE);

imagestring($image, 2, 220, 10, 'unprotected accesspoints', $darkgreen);
imagestring($image, 2, 220, 21, round(($part_u*100), 2).'% ('.$unprotected.')', $darkgreen);
imagestring($image, 2, 220, 35, 'protected accesspoints', $darkred);
imagestring($image, 2, 220, 46, round(($part_p*100), 2).'% ('.$protected.')', $darkred);


imagepng($image, 'stats/encrypted.png');
imagedestroy($image);


/////////////////////// ENCRYPTION
$wpa = $db->query('SELECT encrypted FROM accesspoints WHERE encryption =  \'WPA\'')->numRows();
$unk = $db->query('SELECT encrypted FROM accesspoints WHERE encryption != \'WPA\' AND encrypted = 1')->numRows();
$total = $wpa+$unk;

$part_w = $wpa/$total;
$part_u = $unk/$total;

$arc_w = $part_w*360;
$arc_u = $part_u*360;

$image = imagecreatetruecolor(380, 201);

// allocate some solors
$white    = imagecolorallocate($image, 0xFF, 0xFF, 0xFF);
$darkred  = imagecolorallocate($image, 0x99, 0x00, 0x00);
$darkblue = imagecolorallocate($image, 0x00, 0x00, 0x99);

imagefill($image, 0, 0, $white);

imagefilledarc($image, 100, 100, 200, 200, 0, $arc_w, $darkred, IMG_ARC_PIE);
imagefilledarc($image, 100, 100, 200, 200, $arc_w, 360 , $darkblue, IMG_ARC_PIE);

imagestring($image, 2, 220, 10, 'WPA', $darkred);
imagestring($image, 2, 220, 21, round(($part_w*100), 2).'% ('.$wpa.')', $darkred);
imagestring($image, 2, 220, 35, 'unknown', $darkblue);
imagestring($image, 2, 220, 46, round(($part_u*100), 2).'% ('.$unk.')', $darkblue);


imagepng($image, 'stats/encryption.png');
imagedestroy($image);

/////////////////////// HTML STATISTICS

if(!file_exists('oui.txt')) echo "Warning: There is no list of MAC-".
			"OUIs (Organizationally Unique Identifiers). Execute ".
			"/dloui.sh if you are connected to the internet to ".
			"download them now.\n\n";

$html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
	<title>WLANalyse HTML Statistics</title>
	<meta http-equiv="content-type" content="text/html;charset=utf-8" />
	<meta name="generator" content="WLANalyse" />
	<style type="text/css">
		body, h1, h2, h3 {
			font-family: sans-serif;
		}
		td {
			border: 1px solid dotted;
		}
	</style>
</head>

<body>';

$html .= '<h1>WL<span style="color:#900;">ANalyse</span> Report</h1>';
$html .= '<ul>
	<li><a href="#pie-enc">Percentage of unencrypted WLAN</a></li>
	<li><a href="#pie-wpa">Percentage of WPA encryption</a></li>
	<li><a href="#macs-all">50 most detected MAC-Ranges</a></li>
	<li><a href="#macs-unenc">50 most detected MAC-Ranges on <u>unprotected</u> WLANs</a></li>
</ul>';
$html .= '<h2><a name="pie-enc"></a>Percentage of unencrypted WLAN</h2>';
$html .= '<img src="encrypted.png" alt="Encrypted?" />';

$html .= '<h2><a name="pie-wpa"></a>Percentage of WPA encryption</h2>';
$html .= '<img src="encryption.png" alt="WPA?" />';


if(file_exists('oui.txt')) {
	$oui_file_content = file_get_contents('oui.txt');
}

$html .= '<h2><a name="macs-all"></a>50 most detected MAC-Ranges</h2>';
$html .= '<table>
			  <tr>
				  <th>Count</th>
				  <th>MAC-Range</th>
				  <th>Owner (OUI)</th>
			  </tr>';

$query = $db->unbufferedQuery('SELECT macparts as m, COUNT(machash) as c FROM accesspoints GROUP by macparts ORDER by c DESC LIMIT 50');
$result = $query->fetchAll(SQLITE_ASSOC);
foreach ($result as $row) {
	$html .= '<tr><td>'.$row['c'].'</td><td>'.substr($row['m'], 0, 8).'</td>';

	if($oui_file_content){
		preg_match('#'.str_replace(':', '-', substr($row['m'], 0, 8)).'[^(]*\(hex\)[	 ]*(.*)#i', $oui_file_content, $sub);
		$html .= '<td>'.$sub[1].'</td>';
		unset($sub);
	}else{
		$html .= '<td></td>';
	}
	$html .= '</tr>';
}
$html .= '</table>';

$html .= '<h2><a name="macs-unenc"></a>50 most detected MAC-Ranges on <u>unprotected</u> WLANs</h2>';
$html .= '<table>
			  <tr>
				  <th>Count</th>
				  <th>MAC-Range</th>
				  <th>Owner (OUI)</th>
			  </tr>';

$query = $db->unbufferedQuery('SELECT macparts as m, COUNT(machash) as c FROM accesspoints WHERE encrypted = 0 GROUP by macparts ORDER by c DESC LIMIT 50');
$result = $query->fetchAll(SQLITE_ASSOC);
foreach ($result as $row) {
	$html .= '<tr><td>'.$row['c'].'</td><td>'.substr($row['m'], 0, 8).'</td>';

	if($oui_file_content){
		preg_match('#'.str_replace(':', '-', substr($row['m'], 0, 8)).'[^(]*\(hex\)[	 ]*(.*)#i', $oui_file_content, $sub);
		$html .= '<td>'.$sub[1].'</td>';
		unset($sub);
	}else{
		$html .= '<td></td>';
	}
	$html .= '</tr>';
}
$html .= '</table>';


$html .= '</body></html>';
file_put_contents('stats/main.html', $html);

/////////////////////// OK
echo "Statistics generated and saved to stats/main.html\n";
