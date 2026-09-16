<?php

namespace App\Exceptions;

use App\Data\Responses\ErrorResponseData;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class ApiExceptionRenderer
{
    public function register(Exceptions $exceptions): void
    {
        $exceptions->render($this->renderValidationException(...));
        $exceptions->render($this->renderVoteReplayException(...));
        $exceptions->render($this->renderHttpException(...));
        $exceptions->render($this->renderUnhandledException(...));
    }

    private function renderValidationException(ValidationException $exception, Request $request): ?JsonResponse
    {
        if (!$this->isApiRequest($request)) {
            return null;
        }

        return $this->error('Validation failed.', 422, $exception->errors());
    }

    private function renderVoteReplayException(VoteReplayException $exception, Request $request): ?JsonResponse
    {
        if (!$this->isApiRequest($request)) {
            return null;
        }

        return $this->error($exception->getMessage(), 409);
    }

    private function renderHttpException(HttpExceptionInterface $exception, Request $request): ?JsonResponse
    {
        if (!$this->isApiRequest($request)) {
            return null;
        }

        return $this->error(
            match ($exception->getStatusCode()) {
                404 => 'Not found.',
                405 => 'Method not allowed.',
                419 => 'CSRF token mismatch.',
                default => 'Request failed.',
            },
            $exception->getStatusCode(),
            headers: $exception->getHeaders(),
        );
    }

    private function renderUnhandledException(Throwable $exception, Request $request): ?JsonResponse
    {
        if (!$this->isApiRequest($request)) {
            return null;
        }

        return $this->error('Server error.', 500);
    }

    /** @param array<string, list<string>> $errors */
    private function error(string $message, int $status, array $errors = [], array $headers = []): JsonResponse
    {
        return response()->json((new ErrorResponseData($message, $errors))->toArray(), $status, $headers);
    }

    private function isApiRequest(Request $request): bool
    {
        return $request->is('api/*');
    }
}
