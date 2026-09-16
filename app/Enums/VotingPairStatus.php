<?php

namespace App\Enums;

enum VotingPairStatus: string
{
    case READY = 'ready';
    case EXHAUSTED = 'exhausted';
    case UNAVAILABLE = 'unavailable';
}
