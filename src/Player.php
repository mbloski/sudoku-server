<?php
namespace Blo\MultiplayerSudoku;

use Hoa\Websocket\Node;

class Player extends Node {
    const STATE_NEW = 1;
    const STATE_LOBBY = 2;
    const STATE_ROOM = 4;

    protected $state;
    private $name;
    private $country;

    private $room = 0;
    private $roomOperator = false;
    private $playing = false;
    private $points = 0;
    private $x;
    private $y;

    public function setName($name)
    {
        $this->name = $name;

        /* if it's a new client, upgrade its state */
        if ($this->state == self::STATE_NEW)
        {
            $this->state = self::STATE_LOBBY;
        }
    }

    public function assignRoom($id)
    {
        $this->room = $id;
    }

    public function setPlaying($playing)
    {
        $this->playing = $playing;
    }

    public function isPlaying()
    {
        return $this->room !== 0 && $this->playing;
    }

    public function setCoords($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function getCoords()
    {
        return [$this->x, $this->y];
    }

    public function setRoomOperator($op)
    {
        $this->roomOperator = $op;
    }

    public function isRoomOperator()
    {
        return $this->roomOperator;
    }

    public function resetPoints()
    {
        $this->points = 0;
    }

    public function addPoints($points)
    {
        $this->points += $points;
    }

    public function getPoints()
    {
        return $this->points;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getRoom()
    {
        return $this->room;
    }

    public function setState($state)
    {
        $this->state = intval($state);
    }

    public function getState()
    {
        return $this->state;
    }

    public function setCountry($country)
    {
        $this->country = $country;
    }

    public function getCountry()
    {
        return strval($this->country);
    }
}
