<?php
namespace Blo\MultiplayerSudoku;

class StartGame extends Handler
{
    public function handle(String $input)
    {
        $room = $this->server->getRoomById($this->node->getRoom());

        if ($room === null)
            return;

        if (!$this->node->isRoomOperator())
        {
            $this->bucket->getSource()->send('CN You are not a room operator');
            return;
        }

        if ($room->isGameInProgress())
        {
            $this->bucket->getSource()->send('CN Game already in progress');
            return;
        }

        $room->startGame();

        $myNode = $this->node;
        $this->bucket->getSource()->broadcastIf(function($node) use($myNode) {
            return $node->getRoom() === $myNode->getRoom();
        }, 'GL '.json_encode(array(
                'grid' => $room->getGame()->getGrid(),
            ))
        );
    }
}