<?php

namespace App\Http\Middleware;

use App\Contracts\Middleware;
use App\Exceptions\InvalidInput;
use App\Http\Request;
use App\Models\User;
use App\Render;
use App\Services\DbService;
use App\Services\OrmService;
use App\Services\RenderService;
use Symfony\Component\HttpFoundation\Response;

class Auth implements Middleware
{
    /**
     * Assert that the user is logged in.
     */
    public function handle(Request $request, callable $next): Response
    {
        $requestUrl = $request->getPathInfo();
        if (0 !== mb_strpos($requestUrl, '/admin/') || '/admin/users/new/' === $requestUrl) {
            return $next($request);
        }

        $user = $request->user();
        if ($user && $user->getAccessLevel()) {
            return $next($request);
        }

        if (!$request->request->get('username') || !$request->request->get('password')) {
            return $this->showLoginPage($request);
        }

        $this->authenticate($request);

        return redirect($request->getRequestUri(), Response::HTTP_MOVED_PERMANENTLY);
    }

    /**
     * Render the login page.
     *
     * @throws InvalidInput If the request is an AJAX call
     */
    private function showLoginPage(Request $request): Response
    {
        sleep(1); // Prevent brute force

        if ($request->isXmlHttpRequest()) {
            throw new InvalidInput(
                _('Your login has expired. Please reload the page and login again.'),
                Response::HTTP_UNAUTHORIZED
            );
        }

        return new Response(app(RenderService::class)->render('admin/login'), Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Authenticate and attach the user to a session.
     */
    private function authenticate(Request $request): void
    {
        $user = app(OrmService::class)->getOneByQuery(
            User::class,
            'SELECT * FROM `users` WHERE `name` = ' . app(DbService::class)->quote($request->get('username', ''))
        );
        if ($user && $user->getAccessLevel() && $user->validatePassword($request->get('password', ''))) {
            $request->startSession();
            $session = $request->getSession();
            $session->set('login_id', $user->getId());
            $session->set('login_hash', $user->getPasswordHash());
            $session->save();
        }
    }
}
