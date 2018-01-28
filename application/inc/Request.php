<?php namespace AGCMS;

use AGCMS\Entity\User;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

class Request extends SymfonyRequest
{
    /** @var ?User */
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

        $methode = mb_strtoupper($request->server->get('REQUEST_METHOD', 'GET'));
        if (0 === mb_strpos($request->headers->get('CONTENT_TYPE'), 'application/json')
            && in_array($methode, ['POST', 'PUT', 'DELETE', 'PATCH'], true)
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
            $storage = new NativeSessionStorage([
                'cookie_httponly' => 1,
                'cookie_path'     => '/admin/',
            ]);
            $session = new Session($storage);
            $this->setSession($session);
        }
        $session->start();
    }

    /**
     * Set the user making the request.
     *
     * @param User $user
     *
     * @return void
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return ?User
     */
    public function user(): ?User
    {
        if ($this->user) {
            return $this->user;
        }

        $this->startSession();
        $id = $this->session->get('login_id');
        $hash = $this->session->get('login_hash');
        $this->session->save();

        if (!$id || !$hash) {
            return null;
        }

        /** @var ?User */
        $user = ORM::getOneByQuery(
            User::class,
            'SELECT * FROM `users` WHERE `id` = ' . $id . ' AND access != 0 AND password = ' . db()->quote($hash)
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
        $this->startSession();
        $this->session->remove('login_id');
        $this->session->remove('login_hash');
        $this->session->save();
        $this->user = null;
    }
}
