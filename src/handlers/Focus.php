<?php
namespace Blo\MultiplayerSudoku;

class Focus extends Handler
{
    public function handle(String $input)
    {
        $room = $this->server->getRoomById($this->node->getRoom());
        if ($room === null || !$room->isGameInProgress() || !$this->node->isPlaying())
            return;

        $input_array = explode(' ', $input);

        if (count($input_array) < 3)
            return;

        $x = intval($input_array[1]);
        $y = intval($input_array[2]);

        if ($room->getGame()->getCell($x, $y) !== null)
            return;

        $myNode = $this->node;

        $this->node->setCoords($x, $y);

        $ret = $room->getPlayerCoords();

        $this->bucket->getSource()->broadcastIf(function($node) use($myNode) {
            return $node->getRoom() === $myNode->getRoom() && $node !== $myNode;
        }, 'F '.json_encode($ret));
    }
}
