# sudoku-server
This is a WebSocket sudoku server.
Live demo with an example client can be seen at http://blo.ski/sudoku/

## Installation
~~~
$ git clone https://github.com/mbloski/sudoku-server.git
$ cd sudoku-server
$ composer install
~~~

## Usage
~~~
$geoIP = geoip_open('GeoIP.dat', GEOIP_STANDARD);
$server = new \Blo\MultiplayerSudoku\Server('ws://0.0.0.0:9182', $geoIP);
~~~
The second argument is optional. GeoIP is used by the client to show country flags.

## Protocol
I'll write the documentation soon... maybe.
