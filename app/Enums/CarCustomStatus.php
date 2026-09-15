<?php

namespace App\Enums;

enum CarCustomStatus: string
{
    case IF_BID = 'If Bid';
    case PASS = 'Pass';
    case SOLD = 'Sold';
}
