<?php
namespace Blo\MultiplayerSudoku;

class JoinRoom extends Handler
{
    public function handle(String $input)
    {
        $roomID = intval(substr($input, strpos($input, ' ') + 1));
        $joinRoom = $this->server->addPlayerToRoom($roomID, $this->node);
    }
}
