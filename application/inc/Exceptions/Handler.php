<?php namespace AGCMS\Exceptions;

use AGCMS\Request;
use Raven_Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler
{
    /** @var Raven_Client */
    private $ravenClient;

    /** @var string|null */
    private $lastLogId;

    /** @var string[] */
    private $dontReport = [
        InvalidInput::class,
    ];

    /**
     * Set error loggin.
     *
     * @param string $basePath
     */
    public function __construct()
    {
        $this->ravenClient = new Raven_Client(config('sentry'));
        $this->ravenClient->install();
    }

    /**
     * Repport the exception.
     *
     * @param Throwable $exception
     *
     * @throws Throwable
     *
     * @return Response
     */
    public function report(Throwable $exception): void
    {
        $this->lastLogId = null;
        if (!$this->shouldLog($exception)) {
            return;
        }

        if ('develop' === config('enviroment')) {
            http_response_code(Response::HTTP_INTERNAL_SERVER_ERROR);

            throw $exception;
        }

        /** @var Request */
        $request = app(Request::class);
        if ($request->getSession() && $request->user()) {
            $this->ravenClient->user_context(
                ['id' => $request->user()->getId(), 'name' => $request->user()->getFullName()]
            );
        }

        $this->lastLogId = $this->ravenClient->captureException($exception);
    }

    /**
     * Generate an error response.
     *
     * @param Request   $request
     * @param Throwable $exception
     *
     * @throws Throwable
     *
     * @return Response
     */
    public function render(Request $request, Throwable $exception): Response
    {
        $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        if ($exception->getCode() >= 400 && $exception->getCode() <= 599) {
            $status = $exception->getCode();
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
     *
     * @param Throwable $exception
     *
     * @return bool
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
