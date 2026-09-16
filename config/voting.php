<?php

return [
    'rate_limits' => [
        'pairs' => [
            'session_minute' => [
                'max_attempts' => (int) env('VOTING_PAIRS_SESSION_PER_MINUTE', 30),
                'decay_seconds' => (int) env('VOTING_PAIRS_SESSION_MINUTE_WINDOW', 60),
            ],
            'ip_minute' => [
                'max_attempts' => (int) env('VOTING_PAIRS_IP_PER_MINUTE', 60),
                'decay_seconds' => (int) env('VOTING_PAIRS_IP_MINUTE_WINDOW', 60),
            ],
        ],
        'votes' => [
            'session_minute' => [
                'max_attempts' => (int) env('VOTING_VOTES_SESSION_PER_MINUTE', 60),
                'decay_seconds' => (int) env('VOTING_VOTES_SESSION_MINUTE_WINDOW', 60),
            ],
            'session_hour' => [
                'max_attempts' => (int) env('VOTING_VOTES_SESSION_PER_HOUR', 60),
                'decay_seconds' => (int) env('VOTING_VOTES_SESSION_HOUR_WINDOW', 3600),
            ],
            'ip_minute' => [
                'max_attempts' => (int) env('VOTING_VOTES_IP_PER_MINUTE', 60),
                'decay_seconds' => (int) env('VOTING_VOTES_IP_MINUTE_WINDOW', 60),
            ],
        ],
    ],
];
