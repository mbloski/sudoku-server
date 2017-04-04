<?php
namespace Blo\MultiplayerSudoku;

class SetName extends Handler
{
    public function validate($name)
    {
        return strlen($name) >= 3 && strlen($name) <= 12;
    }

    public function handle(String $input)
    {
        $name = substr($input, strpos($input, ' ') + 1);

        if (!$this->validate($name)) {
            $this->bucket->getSource()->send('NI');
            return;
        }

        if ($this->server->nameInUse($name)) {
            $this->bucket->getSource()->send('NE');
            return;
        }

        $this->node->setName($name);

        $socket = $this->node->getSocket();
        $addr = stream_socket_get_name($socket, true);
        $addr = substr($addr, 0, strpos($addr, ':'));

        if ($this->server->getGeoIP())
        {
            $country = geoip_country_code_by_addr($this->server->getGeoIP(), $addr);
            $this->node->setCountry($country);
        }

        $this->server->joinLobby($this->node);
    }
}
