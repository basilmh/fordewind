<?php

namespace App\Http\Middleware;

final class ThrottleVotingPairs extends ThrottleVotingRequests
{
    protected function group(): string
    {
        return 'pairs';
    }

    protected function limitNames(): array
    {
        return ['session_minute', 'ip_minute'];
    }

    protected function errorMessage(): string
    {
        return 'Too many voting pair requests.';
    }
}
