<?php

namespace App\Http\Middleware;

use App\Data\Responses\ErrorResponseData;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

final class ThrottleVotingVotes
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $limits = $this->limits($request);

        foreach ($limits as $limit) {
            if (RateLimiter::tooManyAttempts($limit['key'], $limit['max_attempts'])) {
                return $this->tooManyRequests($limit['key']);
            }
        }

        foreach ($limits as $limit) {
            RateLimiter::hit($limit['key'], $limit['decay_seconds']);
        }

        return $next($request);
    }

    /**
     * @return list<array{key: string, max_attempts: int, decay_seconds: int}>
     */
    private function limits(Request $request): array
    {
        $sessionHash = hash_hmac('sha256', $request->session()->getId(), (string) config('app.key'));
        $ipHash = hash('sha256', (string) $request->ip());

        return [
            $this->configuredLimit('session_minute', "voting-votes:session:minute:{$sessionHash}"),
            $this->configuredLimit('session_hour', "voting-votes:session:hour:{$sessionHash}"),
            $this->configuredLimit('ip_minute', "voting-votes:ip:minute:{$ipHash}"),
        ];
    }

    private function tooManyRequests(string $key): Response
    {
        return response()->json(
            (new ErrorResponseData('Too many voting requests.'))->toArray(),
            Response::HTTP_TOO_MANY_REQUESTS,
            ['Retry-After' => (string) RateLimiter::availableIn($key)],
        );
    }

    /** @return array{key: string, max_attempts: int, decay_seconds: int} */
    private function configuredLimit(string $name, string $key): array
    {
        return [
            'key' => $key,
            'max_attempts' => (int) config("voting.rate_limits.{$name}.max_attempts"),
            'decay_seconds' => (int) config("voting.rate_limits.{$name}.decay_seconds"),
        ];
    }
}
