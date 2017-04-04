<?php
require 'vendor/autoload.php';

use Blo\MultiplayerSudoku\Server;

$geoIP = geoip_open('GeoIP.dat', GEOIP_STANDARD);
$server = new Server('ws://0.0.0.0:9182', $geoIP);
