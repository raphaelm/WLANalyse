<?php
/*
 *      config.inc.example.php
 *      part of WLANalyse
 *
 *      Author: Raphael Michel <webmaster@raphaelmichel.de>
 *      Copyright: 2009 geek's factory
 *      License: MIT-Lizenz
 */
define('PWS_INTERFACE', ''); // The interface which should be used.
                             // Use the command "iwconfig" to list
                             // all possible interfaces.

define('PWS_DATABASE', 'database.db'); // The database file which should
                                       // be used to store data. There
                                       // is an empty one, named
                                       // "database.db"

define('PWS_SCANFREQ', 5); // Length of the break between two scans
                           // (in seconds)