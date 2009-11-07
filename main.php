<?php
/*
 *      main.php
 *      part of PHPWLANstat
 *
 *      Author: Raphael Michel <webmaster@raphaelmichel.de>
 *      Copyright: 2009 geek's factory
 *      License: MIT-Lizenz
 */

if(!file_exists('config.inc.php'))
	die('File config.inc.php not found!');

include('config.inc.php');

// Check for compatibility

// 'cause PHP is to silly to tetect if we are root, we have to guess...
ob_start();
$user = system('whoami');
ob_end_clean();
if($user != "root")
	die("I dislike it too, but you have to be root to execute this "
	     ."program.\n");

if(strtoupper(substr(PHP_SAPI, 0, 3)) != 'CLI')
	die("Please use me with the CLI-SAPI\n");

if(strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
	die("I won't work with Microsoft Windows. Please use a real ".
	    "operating system like Linux.\n");

if(!version_compare(PHP_VERSION, '5.0.0', '>='))
	die("I need PHP version 5 or greater!\n");

if(!class_exists('SQLiteDatabase'))
	die("Cannot find PHP-SQLite extension!\n");

if(!file_exists('/bin/iwlist')
   and !file_exists('/sbin/iwlist')
   and !file_exists('/usr/bin/iwlist')
   and !file_exists('/usr/sbin/iwlist')
   and !file_exists('/usr/local/bin/iwlist')
   and !file_exists('/usr/local/sbin/iwlist')
   and !file_exists('~/bin/iwlist'))
	die("The command 'iwlist' was not found!\n");

if(!file_exists(PWS_DATABASE))
	die("The database file was not found!\n");

// Include Files
require('class.phpwlanstat.php');
echo "This program comes with ABSOLUTELY NO WARRANTY! You use it AT YOUR OWN RISK!\n";
echo "All right! Starting PHPWLANstat engine now...\n";

$pws = new PHPWLANstat;