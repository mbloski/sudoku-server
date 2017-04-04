<?php
namespace Blo\MultiplayerSudoku;

class Handler
{
    protected $server;
    protected $bucket;
    protected $node;

    final public function __construct(Server $server, \Hoa\Event\Bucket $bucket)
    {
        $this->server = $server;
        $this->bucket = $bucket;
        $this->node = $bucket->getSource()->getConnection()->getCurrentNode();
    }

    public function handle(String $command)
    {
        /* default handler */
    }
}
