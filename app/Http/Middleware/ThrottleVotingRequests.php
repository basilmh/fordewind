<?php

namespace App\Http\Middleware;

use App\Data\Responses\ErrorResponseData;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

abstract class ThrottleVotingRequests
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

    abstract protected function group(): string;

    /** @return list<string> */
    abstract protected function limitNames(): array;

    abstract protected function errorMessage(): string;

    /** @return list<array{key: string, max_attempts: int, decay_seconds: int}> */
    private function limits(Request $request): array
    {
        $sessionHash = hash_hmac('sha256', $request->session()->getId(), (string) config('app.key'));
        $ipHash = hash('sha256', (string) $request->ip());

        return array_map(
            fn (string $name): array => $this->configuredLimit(
                $name,
                $name === 'ip_minute' ? $ipHash : $sessionHash,
            ),
            $this->limitNames(),
        );
    }

    private function tooManyRequests(string $key): Response
    {
        return response()->json(
            (new ErrorResponseData($this->errorMessage()))->toArray(),
            Response::HTTP_TOO_MANY_REQUESTS,
            ['Retry-After' => (string) RateLimiter::availableIn($key)],
        );
    }

    /** @return array{key: string, max_attempts: int, decay_seconds: int} */
    private function configuredLimit(string $name, string $identityHash): array
    {
        $group = $this->group();

        return [
            'key' => "voting-{$group}:{$name}:{$identityHash}",
            'max_attempts' => (int) config("voting.rate_limits.{$group}.{$name}.max_attempts"),
            'decay_seconds' => (int) config("voting.rate_limits.{$group}.{$name}.decay_seconds"),
        ];
    }
}
