<?php

namespace App\AiAgents;

use LarAgent\Agent;

class ProcessingAgent extends Agent
{
    protected $model = 'gpt-4o-mini';

    protected $history = 'in_memory';

    protected $provider = 'default';

    protected $tools = [];

    public function prompt($message)
    {
        return $message;
    }
}
