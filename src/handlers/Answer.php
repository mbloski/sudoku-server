<?php
namespace Blo\MultiplayerSudoku;

class Answer extends Handler
{
    public function handle(String $input)
    {
        $room = $this->server->getRoomById($this->node->getRoom());
        if ($room === null || !$room->isGameInProgress() || !$this->node->isPlaying())
            return;

        $input_array = explode(' ', $input);

        if (count($input_array) < 4)
            return;

        $x = intval($input_array[1]);
        $y = intval($input_array[2]);
        $a = intval($input_array[3]);

        if ($room->getGame()->getCell($x, $y) !== null)
            return;

        $res = $room->getGame()->testCell($x, $y, $a);

        if ($res === null)
            return;

        $points = $res? 100 : -200;

        $ret = array(
            'name' => htmlentities($this->node->getName(), ENT_QUOTES),
            'op' => $this->node->isRoomOperator(),
            'country' => $this->node->getCountry(),
            'x' => $x,
            'y' => $y,
            'answer' => $a,
            'points' => $points
        );

        $this->node->addPoints($points);

        $myNode = $this->node;
        $this->bucket->getSource()->broadcastIf(function($node) use($myNode) {
            return $node->getRoom() === $myNode->getRoom();
        }, 'A '.json_encode($ret));

        if ($room->getGame()->isSolved())
        {
            $room->recalculatePlayers();

            $this->bucket->getSource()->broadcastIf(function($node) use($myNode) {
                return $node->getRoom() === $myNode->getRoom();
            }, 'R '.json_encode(array(
                    'id' => $room->getId(),
                    'players' => $room->getPlayersArray(),
                    'grid' => $room->isGameInProgress()? $room->getGame()->getGrid() : null,
                    'end' => true
                ))
            );

            $room->resetGame();
        }
    }
}