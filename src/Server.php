<?php
namespace Blo\MultiplayerSudoku;

class Server
{
    const COMMAND_MAP = [
        /* 'COMMAND' => [Handler, Minimum Arguments, Required State] */
        'N'  => ['SetName',     0, Player::STATE_NEW],
        'M'  => ['ChatMessage', 1, Player::STATE_LOBBY | Player::STATE_ROOM],
        'C'  => ['CreateRoom',  1, Player::STATE_LOBBY],
        'J'  => ['JoinRoom',    1, Player::STATE_LOBBY],
        'LR' => ['LeaveRoom'  , 0, Player::STATE_ROOM],
        'SG' => ['StartGame',   0, Player::STATE_ROOM],
        'A'  => ['Answer',      3, Player::STATE_ROOM],
        'F'  => ['Focus',       2, Player::STATE_ROOM],
    ];

    private $ws;
    private $geoip;

    private $rooms;

    public function __construct(String $wsUri, $geoIP = null)
    {
        $this->geoip = $geoIP;
        $this->rooms = array();
        $this->ws = new \Hoa\Websocket\Server(new \Hoa\Socket\Server($wsUri));
        $this->ws->getConnection()->setNodeName('\Blo\MultiplayerSudoku\Player');
        $this->registerEvents();
        $this->ws->run();
    }

    private function registerEvents()
    {
        $this->ws->on('open', function(\Hoa\Event\Bucket $bucket) {
            $node = $bucket->getSource()->getConnection()->getCurrentNode();
            $node->setState(Player::STATE_NEW);
        });

        $this->ws->on('message', function (\Hoa\Event\Bucket $bucket) {
            $node = $bucket->getSource()->getConnection()->getCurrentNode();
            $data = $bucket->getData();
            $exploded = explode(' ', $data['message']);

            $hnd = self::COMMAND_MAP[strtoupper($exploded[0])] ?? ['Handler', 0, Player::STATE_NEW];
            $class = '\Blo\MultiplayerSudoku\\'.$hnd[0];
            if (class_exists($class) && count($exploded) > $hnd[1] && ($hnd[2] | $node->getState()) === $hnd[2])
            {
                $handler = new $class($this, $bucket);
                $handler->handle($data['message']);
            }
        });

        $this->ws->on('error', function (\Hoa\Event\Bucket $bucket) {
            $this->onQuit($bucket);
        });

        $this->ws->on('close', function (\Hoa\Event\Bucket $bucket) {
            $this->onQuit($bucket);
        });
    }

    private function onQuit($bucket)
    {
        $currentNode = $bucket->getSource()->getConnection()->getCurrentNode();

        if ($currentNode->getState() < Player::STATE_LOBBY)
            return;

        if ($currentNode->getRoom() !== 0)
        {
            $this->removePlayerFromRoom($currentNode->getRoom(), $currentNode);
        }

        $bucket->getSource()->broadcastIf(function($node) use($currentNode) {
            return $node->getState() === Player::STATE_LOBBY && $node !== $currentNode;
        }, 'Q '.htmlentities($currentNode->getName(), ENT_QUOTES));
    }

    private function broadcastRoom($id)
    {
        if (!isset($this->rooms[$id]))
        {
            return false;
        }

        $room = $this->rooms[$id];
        $ent = array(
            'id' => $room->getID(),
            'players' => $room->getPlayerNames(),
            'capacity' => $room->getCapacity(),
            'inprogress' => $room->isGameInProgress()
        );

        $this->ws->broadcastIf(function($node) {
            return $node->getState() === Player::STATE_LOBBY;
        }, 'RL '.json_encode($ent));
    }

    public function joinLobby($node)
    {
        $node->assignRoom(0);
        $node->setState(Player::STATE_LOBBY);

        $rooms = array();
        foreach ($this->getRooms() as $room)
        {
            $players = array_map(function($x) { return htmlentities($x->getName(), ENT_QUOTES); }, $room->getPlayingPlayers());

            $ent = array(
                'id' => $room->getID(),
                'players' => $players,
                'spectators' => $room->getPlayerCount() - $room->getPlayingPlayersCount(),
                'capacity' => $room->getCapacity(),
                'inprogress' => $room->isGameInProgress()
            );

            array_push($rooms, $ent);
        }

        $this->ws->broadcastIf(function($n) use($node) {
            return $n === $node;
        }, 'NOK '.json_encode(array('name' => htmlentities($node->getName(), ENT_QUOTES), 'rooms' => $rooms)));

        $this->ws->broadcastIf(function($n) use($node) {
            return $n->getState() === Player::STATE_LOBBY && $n !== $node;
        }, 'N '.json_encode(array(
                'name' => htmlentities($node->getName(), ENT_QUOTES),
                'country' => $node->getCountry()
            ))
        );
    }

    public function createRoom($capacity)
    {
        $getFreeRoomId = function()
        {
            for ($i = 1; $i <= $this->getRoomCount() + 1; ++$i)
            {
                if (!isset($this->rooms[$i]))
                {
                    return $i;
                }
            }

            return 0; // should never happen
        };

        $roomID = $getFreeRoomId();
        $this->rooms[$roomID] = new Room($this, $roomID, $capacity);

        return $roomID;
    }

    private function removeRoom($id)
    {
        if (!isset($this->rooms[$id]) || $this->rooms[$id]->getPlayerCount() !== 0)
        {
            return false;
        }

        unset($this->rooms[$id]);
        $this->ws->broadcastIf(function($node) {
            return $node->getState() === Player::STATE_LOBBY;
        }, 'RR '.$id);
        return true;
    }

    public function addPlayerToRoom($id, $node)
    {
        if (!isset($this->rooms[$id])) {
            return false;
        }

        $this->rooms[$id]->addPlayer($node);

        $node->setState(Player::STATE_ROOM);
        $node->assignRoom($id);
        $this->broadcastRoom($id);

        $ret = array();
        $ret['name'] = htmlentities($node->getName(), ENT_QUOTES);
        $ret['country'] = $node->getCountry();
        $ret['op'] = $node->isRoomOperator();
        $ret['playing'] = $node->isPlaying();
        $ret['points'] = $node->getPoints();

        $this->ws->broadcastIf(function($n) use($node) {
            return $n->getState() === Player::STATE_LOBBY && $n !== $node;
        }, 'L '.htmlentities($node->getName(), ENT_QUOTES));

        $this->ws->broadcastIf(function($n) use($node) {
            return $n->getState() === Player::STATE_ROOM && $n->getRoom() === $node->getRoom() && $n !== $node;
        }, 'N '.json_encode($ret));

        $room = $this->getRoomById($node->getRoom());
        $this->ws->broadcastIf(function($n) use($node, $room) {
            return $n === $node;
        }, 'R '.json_encode(array(
                'id' => $id,
                'players' => $room->getPlayersArray(),
                'grid' => $room->isGameInProgress()? $room->getGame()->getGrid() : null
            ))
        );

        return true;
    }

    public function removePlayerFromRoom($id, $node)
    {
        if (!isset($this->rooms[$id])) {
            return false;
        }

        $ret = $this->rooms[$id]->removePlayer($node);

        if ($this->rooms[$id]->getPlayerCount() === 0)
        {
            $this->removeRoom($id);
        }
        else
        {
            $this->broadcastRoom($id);

            $this->ws->broadcastIf(function($n) use($node, $id) {
                return $n->getState() === Player::STATE_ROOM && $n->getRoom() === $id && $n !== $node;
            }, 'L '.htmlentities($node->getName(), ENT_QUOTES));

            $room = $this->rooms[$id];
            $this->ws->broadcastIf(function($n) use($node, $id, $room) {
                return $n->getState() === Player::STATE_ROOM && $n->getRoom() === $id && $n !== $node;
            }, 'R '.json_encode(array(
                    'id' => $id,
                    'players' => $room->getPlayersArray(),
                    'grid' => $room->isGameInProgress()? $room->getGame()->getGrid() : null
                ))
            );
        }

        return $ret;
    }

    public function getPlayerList()
    {
        $ret = array();
        foreach($this->ws->getConnection()->getNodes() as $node)
        {
            if ($node->getState() > 0)
            {
                array_push($ret, $node->getName());
            }
        }

        return $ret;
    }

    public function getPlayer($name)
    {
        foreach ($this->ws->getConnection()->getNodes() as $node)
        {
            if ($node->getName() === $name)
            {
                return $node;
            }
        }

        return false;
    }

    public function nameInUse($name)
    {
        return in_array($name, $this->getPlayerList());
    }

    public function getGeoIP()
    {
        return $this->geoip;
    }

    public function getRooms()
    {
        return $this->rooms;
    }

    public function getRoomById($id)
    {
        return $this->rooms[$id] ?? null;
    }

    public function getRoomCount()
    {
        return count($this->rooms);
    }
}
