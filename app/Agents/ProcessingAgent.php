<?php

declare(strict_types=1);

namespace App\Agents;

use LarAgent\Agent;

class ProcessingAgent extends Agent
{
    protected $model = 'gpt-4o-mini';

    protected $history = 'in_memory';

    protected $provider = 'default';

    protected $tools = [];

    /**
     * Processing agent instructions - this is a utility agent for processing prompts.
     */
    public function instructions(): string
    {
        return 'You are a helpful assistant that processes and summarizes information. Follow the instructions provided in each prompt precisely and return the requested output format.';
    }
}
