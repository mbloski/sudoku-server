<?php
namespace Blo\MultiplayerSudoku;

class LeaveRoom extends Handler
{
    public function handle(String $input)
    {
        if ($this->node->getRoom() !== 0)
        {
            $this->server->removePlayerFromRoom($this->node->getRoom(), $this->node);
            $this->server->joinLobby($this->node);
        }
    }
}
