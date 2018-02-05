<?php namespace App\Http\Middleware;

use App\Contracts\Middleware;
use App\Http\Controllers\Base;
use App\Models\User;
use App\Exceptions\InvalidInput;
use App\Render;
use App\Http\Request;
use Closure;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Auth implements Middleware
{
    /**
     * Assert that the user is logged in.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (($request->user() && $request->user()->getAccessLevel())
            || '/admin/users/new/' === $request->getPathInfo()
        ) {
            return $next($request);
        }

        if (!$request->request->get('username') || !$request->request->get('password')) {
            return $this->showLoginPage($request);
        }

        $this->authenticate($request);

        return (new Base())->redirect($request, $request->getRequestUri(), Response::HTTP_MOVED_PERMANENTLY);
    }

    /**
     * Render the login page.
     *
     * @param Request $request
     *
     * @throws InvalidInput If the request is an AJAX call
     *
     * @return Response
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

        return new Response(app('render')->render('admin/login'), Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Authenticate and attach the user to a session.
     *
     * @param Request $request
     *
     * @return void
     */
    private function authenticate(Request $request): void
    {
        /** @var ?User */
        $user = app('orm')->getOneByQuery(
            User::class,
            'SELECT * FROM `users` WHERE `name` = ' . app('db')->quote($request->get('username', ''))
        );
        if ($user && $user->getAccessLevel() && $user->validatePassword($request->get('password', ''))) {
            $request->startSession();
            /** @var SessionInterface */
            $session = $request->getSession();
            $session->set('login_id', $user->getId());
            $session->set('login_hash', $user->getPasswordHash());
            $session->save();
        }
    }
}
