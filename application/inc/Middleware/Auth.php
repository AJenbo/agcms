<?php namespace AGCMS\Middleware;

use AGCMS\Controller\Base;
use AGCMS\Entity\User;
use AGCMS\Exception\InvalidInput;
use AGCMS\Interfaces\Middleware;
use AGCMS\ORM;
use AGCMS\Render;
use AGCMS\Request;
use Closure;
use Symfony\Component\HttpFoundation\Response;

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
        $request->startSession();
        if ($request->user() || '/admin/users/new/' === $request->getPathInfo()) {
            return $next($request);
        }

        if (!$request->request->get('username') || !$request->request->get('password')) {
            sleep(1);

            if ($request->isXmlHttpRequest()) {
                throw new InvalidInput(
                    _('Your login has expired, please reload the page and login again.'),
                    Response::HTTP_UNAUTHORIZED
                );
            }

            return new Response(Render::render('admin/login'), Response::HTTP_UNAUTHORIZED);
        }

        /** @var User */
        $user = ORM::getOneByQuery(
            User::class,
            'SELECT * FROM `users` WHERE `name` = ' . db()->eandq($request->get('username', ''))
        );
        if ($user && $user->getAccessLevel() && $user->validatePassword($request->get('password', ''))) {
            $session = $request->getSession();
            $session->set('login_id', $user->getId());
            $session->set('login_hash', $user->getPasswordHash());
            $session->save();
        }

        return (new Base())->redirect($request, $request->getRequestUri(), Response::HTTP_MOVED_PERMANENTLY);
    }
}
