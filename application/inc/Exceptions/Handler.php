<?php

namespace App\Exceptions;

use App\Http\Request;
use App\Application;
use Raven_Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Sentry\State\Scope;

class Handler
{
    /** @var null|string */
    private $lastLogId;

    /** @var array<int, string> */
    private $dontReport = [
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
     *
     * @throws Throwable
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

        $request = app(Request::class);
        if ($request->hasSession()) {
            $user = $request->user();
            if ($user && app()->environment('production')) {
                \Sentry\configureScope(function (Scope $scope) use ($user): void {
                    $scope->setUser(['id' => $user->getId(), 'name' => $user->getFullName()]);
                });
            }
        }

        if (app()->environment('production')) {
            $this->lastLogId = \Sentry\captureException($exception);
        }
    }

    /**
     * Generate an error response.
     *
     * @throws Throwable
     */
    public function render(Request $request, Throwable $exception): Response
    {
        $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        if ($exception->getCode() >= 400 && $exception->getCode() <= 599) {
            $status = (int) $exception->getCode();
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
