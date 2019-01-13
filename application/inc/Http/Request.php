<?php namespace App\Http;

use App\Models\User;
use App\Services\DbService;
use App\Services\OrmService;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

class Request extends SymfonyRequest
{
    /** @var ?User */
    private $user;

    /**
     * @param array           $query      The GET parameters
     * @param array           $request    The POST parameters
     * @param array           $attributes The request attributes (parameters parsed from the PATH_INFO, ...)
     * @param array           $cookies    The COOKIE parameters
     * @param array           $files      The FILES parameters
     * @param array           $server     The SERVER parameters
     * @param resource|string $content    The raw body data
     */
    public function __construct(
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $content = null
    ) {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);

        $methode = mb_strtoupper($this->server->get('REQUEST_METHOD', 'GET'));
        /** @var string */
        $contentType = $this->headers->get('CONTENT_TYPE', 'text/plain');
        if (0 === mb_strpos($contentType, 'application/json')
            && in_array($methode, ['POST', 'PUT', 'DELETE', 'PATCH'], true)
        ) {
            /** @var string */
            $content = $this->getContent();
            $data = json_decode($content, true) ?? [];
            $data = is_array($data) ? $data : ['json' => $data];
            $this->request = new ParameterBag($data);
        }
    }

    /**
     * Make sure we have a session and that it has been started.
     *
     * @return void
     */
    public function startSession(): void
    {
        if (!$this->hasSession()) {
            $storage = new NativeSessionStorage([
                'cookie_httponly' => 1,
                'cookie_path'     => '/admin/',
            ]);
            $session = new Session($storage);
            $this->setSession($session);
        } else {
            /** @var SessionInterface */
            $session = $this->getSession();
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

        /** @var DbService */
        $db = app(DbService::class);

        /** @var OrmService */
        $orm = app(OrmService::class);

        /** @var ?User */
        $user = $orm->getOneByQuery(
            User::class,
            'SELECT * FROM `users` WHERE `id` = ' . $id . ' AND access != 0 AND password = ' . $db->quote($hash)
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
