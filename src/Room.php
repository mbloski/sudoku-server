<?php
namespace Blo\MultiplayerSudoku;

use Hoa\Websocket\Node;

class Room {
    private $capacity;
    private $players;
    private $id;

    private $game;

    public function __construct($server, $id, $capacity)
    {
        $this->id = $id;
        $this->capacity = $capacity;
        $this->players = array();
        $this->game = null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPlayers()
    {
        return $this->players;
    }

    public function getPlayersArray()
    {
        return array_map(function($x) {
            $ret = array();
            $ret['name'] = htmlentities($x->getName(), ENT_QUOTES);
            $ret['country'] = $x->getCountry();
            $ret['op'] = $x->isRoomOperator();
            $ret['playing'] = $x->isPlaying();
            $ret['points'] = $x->getPoints();
            list($ret['x'], $ret['y']) = $x->getCoords();
            return $ret;
        }, $this->getPlayers());
    }

    public function getPlayerCoords()
    {
        $ret = array();
        foreach ($this->players as $player)
        {
            if ($player->isPlaying())
            {
                $r['name'] = htmlentities($player->getName(), ENT_QUOTES);
                list($r['x'], $r['y']) = $player->getCoords();

                if ($r['x'] === null || $r['y'] === null)
                    continue;

                array_push($ret, $r);
            }
        }

        return $ret;
    }

    public function getPlayerNames()
    {
        $ret = array();
        foreach ($this->players as $player)
        {
            array_push($ret, $player->getName());
        }

        return $ret;
    }

    public function getPlayingPlayers()
    {
        $ret = array();
        foreach ($this->players as $player)
        {
            if ($player->isPlaying()) {
                array_push($ret, $player);
            }
        }

        return $this->players;
    }

    public function getPlayerCount()
    {
        return count($this->players);
    }

    public function getPlayingPlayersCount()
    {
        $ret = 0;
        foreach ($this->players as $player)
        {
            if ($player->isPlaying())
            {
                ++$ret;
            }
        }

        return $ret;
    }

    public function getCapacity()
    {
        return $this->capacity;
    }

    private function getPlayerByName($name)
    {
        foreach ($this->players as $player)
        {
            if ($player->getName() === $name)
            {
                return $player;
            }
        }

        return null;
    }

    public function addPoints($name, $points)
    {
        foreach ($this->players as &$player)
        {
            if ($player->getName() === $name)
            {
                $player->addPoints($points);
                return true;
            }
        }

        return false;
    }

    public function startGame()
    {
        $this->game = new Game(25);
    }

    public function getGame()
    {
        return $this->game;
    }

    public function recalculatePlayers()
    {
        foreach ($this->players as $k => $player)
        {
            if ($k < $this->getCapacity())
            {
                $this->players[$k]->setPlaying(true);
            }
        }
    }

    public function resetGame()
    {
        $this->game = null;

        foreach ($this->players as $k => $player)
        {
            $this->players[$k]->resetPoints();
        }
    }

    public function isGameInProgress()
    {
        return $this->game !== null;
    }

    public function addPlayer($node)
    {
        $node->setRoomOperator($this->getPlayingPlayersCount() === 0);
        $node->setPlaying($this->getPlayingPlayersCount() < $this->getCapacity() && !$this->isGameInProgress());

        array_push($this->players, $node);
    }

    public function removePlayer($node)
    {
        $ret = false;
        foreach ($this->players as $k => $player)
        {
            if ($player === $node)
            {
                $this->players[$k]->resetPoints();
                $this->players[$k]->assignRoom(0);
                $this->players[$k]->setPlaying(false);

                unset($this->players[$k]);
                $this->players = array_values($this->players);

                if ($k === 0 && count($this->players) > 0)
                {
                    $this->players[$k]->setRoomOperator(true);
                }

                $ret = true;
                break;
            }
        }

        if ($this->getPlayingPlayersCount() === 0)
        {
            $this->recalculatePlayers();
            $this->resetGame();
        }

        return $ret;
    }
}