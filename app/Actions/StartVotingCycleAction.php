<?php

namespace App\Actions;

use Illuminate\Contracts\Session\Session;

final readonly class StartVotingCycleAction
{
    public function __construct(private Session $session) {}

    public function run(): string
    {
        $this->session->invalidate();
        $this->session->regenerateToken();

        return $this->session->token();
    }
}
