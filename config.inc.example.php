<?php
/*
 *      config.inc.example.php
 *      part of PHPWLANstat
 *
 *      Author: Raphael Michel <webmaster@raphaelmichel.de>
 *      Copyright: 2009 geek's factory
 *      License: MIT-Lizenz
 */
define('PWS_INTERFACE', ''); // Das Interface, das genutzt werden
                             // soll. Zum Herausfinden führe "iwconfig"
                             // aus und schaue, bei welchem Interface
                             // etwas wie "IEEE 802.11" dabeisteht ;-)

define('PWS_DATABASE', 'database.db'); // Die zu nutzende Datenbank-
									// Datei. Eine leere namens
									// "database.db" liegt bei.