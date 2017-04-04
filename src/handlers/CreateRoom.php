<?php
namespace Blo\MultiplayerSudoku;

class CreateRoom extends Handler
{
    public function handle(String $input)
    {
        $capacity = intval(substr($input, strpos($input, ' ') + 1));
        if ($capacity < 1 || $capacity > 4)
        {
            $this->bucket->getSource()->send('RN');
            return false;
        }

        $roomID = $this->server->createRoom($capacity);
        $this->server->addPlayerToRoom($roomID, $this->node);

        return true;
    }
}
