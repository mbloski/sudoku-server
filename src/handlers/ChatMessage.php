<?php
namespace Blo\MultiplayerSudoku;

class ChatMessage extends Handler
{
    public function handle(String $input)
    {
        $msg = substr($input, strpos($input, ' ') + 1);

        if (empty($msg) || strlen($msg) > 120)
            return;

        $myNode = $this->node;
        $this->bucket->getSource()->broadcastIf(function($node) use($myNode) {
            return $node->getRoom() === $myNode->getRoom();
        }, 'M '.json_encode(array(
                'name' => htmlentities($this->node->getName(), ENT_QUOTES),
                'message' => htmlentities($msg, ENT_QUOTES),
                'country' => $this->node->getCountry()
            ))
        );
    }
}
