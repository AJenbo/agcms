<?php

namespace App\Exceptions;

use App\Http\Request;
use Sentry\State\Scope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler
{
    private ?string $lastLogId;

    /** @var array<int, string> */
    private array $dontReport = [
        InvalidInput::class,
    ];

    /**
     * Set error loggin.
     */
    public function __construct()
    {
        if (app()->environment('production')) {
            \Sentry\init(['dsn' => config('sentry')]);
        }
    }

    /**
     * Repport the exception.
     */
    public function report(Throwable $exception): void
    {
        $this->lastLogId = null;
        if (!$this->shouldLog($exception)) {
            return;
        }

        if (app()->environment('develop')) {
            http_response_code(Response::HTTP_INTERNAL_SERVER_ERROR);

            throw $exception;
        }

        if (app()->environment('productino')) {
            $request = app(Request::class);
            if ($request->hasSession()) {
                $user = $request->user();
                if ($user) {
                    \Sentry\configureScope(function (Scope $scope) use ($user): void {
                        $scope->setUser(['id' => $user->getId(), 'name' => $user->getFullName()]);
                    });
                }
            }

            $this->lastLogId = \Sentry\captureException($exception);
        }
    }

    /**
     * Generate an error response.
     */
    public function render(Request $request, Throwable $exception): Response
    {
        if (app()->environment('test') && $this->shouldLog($exception)) {
            throw $exception;
        }

        $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        if ($exception->getCode() >= 400 && $exception->getCode() <= 599) {
            $status = (int)$exception->getCode();
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(
                ['error' => ['message' => $exception->getMessage(), 'sentry_id' => $this->lastLogId]],
                $status
            );
        }

        return new Response($exception->getMessage(), $status);
    }

    /**
     * Determin if the exception should be logged.
     */
    private function shouldLog(Throwable $exception): bool
    {
        foreach ($this->dontReport as $className) {
            if ($exception instanceof $className) {
                return false;
            }
        }

        return true;
    }
}
