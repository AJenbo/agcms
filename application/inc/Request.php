<?php namespace AGCMS;

use AGCMS\Entity\User;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Session\Session;

class Request extends SymfonyRequest
{
    /** @var User */
    private $user;

    /**
     * Creates a new request with values from PHP's super globals.
     *
     * Also decode json in content data
     *
     * @return static
     */
    public static function createFromGlobals()
    {
        $request = parent::createFromGlobals();

        if (0 === strpos($request->headers->get('CONTENT_TYPE'), 'application/json')
            && in_array(strtoupper($request->server->get('REQUEST_METHOD', 'GET')), ['POST', 'PUT', 'DELETE', 'PATCH'])
        ) {
            $data = json_decode($request->getContent(), true) ?? [];
            $data = is_array($data) ? $data : ['json' => $data];
            $request->request = new ParameterBag($data);
        }

        return $request;
    }

    /**
     * Make sure we have a session and that it has been started.
     *
     * @return void
     */
    public function startSession(): void
    {
        $session = $this->getSession();
        if (!$session) {
            $session = new Session();
            $this->setSession($session);
        }
        $session->start();
    }

    /**
     * Get the currently authenticated user.
     *
     * @return ?User
     */
    public function user(): ?User
    {
        if ($this->user || !$this->session) {
            return $this->user;
        }

        $id = $this->session->get('login_id');
        if (!$id) {
            return null;
        }

        $hash = $this->session->get('login_hash');

        $user = ORM::getOneByQuery(
            User::class,
            'SELECT * FROM `users` WHERE `id` = ' . $id . ' AND access != 0 AND password = ' . db()->eandq($hash)
        );
        if ($user) {
            $user->setLastLogin(time())->save();
            $this->user = $user;
        }

        return $this->user;
    }

    /**
     * Remove the user data from the session.
     *
     * @return void
     */
    public function logout(): void
    {
        $this->getSession()->clear();
        $this->session->save();
        $this->user = null;
    }
}
