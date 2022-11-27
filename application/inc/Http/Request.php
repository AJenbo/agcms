<?php

namespace App\Http;

use App\Exceptions\InvalidInput;
use App\Models\User;
use App\Services\DbService;
use App\Services\OrmService;
use Exception;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

class Request extends SymfonyRequest
{
    private ?User $user = null;

    /**
     * @param array<mixed>    $query      The GET parameters
     * @param array<mixed>    $request    The POST parameters
     * @param array<mixed>    $attributes The request attributes (parameters parsed from the PATH_INFO, ...)
     * @param array<mixed>    $cookies    The COOKIE parameters
     * @param array<mixed>    $files      The FILES parameters
     * @param array<mixed>    $server     The SERVER parameters
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

        $methode = $this->server->get('REQUEST_METHOD', 'GET');

        /** @var string */
        $contentType = $this->headers->get('CONTENT_TYPE', 'text/plain');
        if (0 === mb_strpos($contentType, 'application/json')
            && is_string($methode)
            && in_array(mb_strtoupper($methode), ['POST', 'PUT', 'DELETE', 'PATCH'], true)
        ) {
            /** @var string */
            $content = $this->getContent();
            $data = json_decode($content, true) ?? [];
            $data = is_array($data) ? $data : ['json' => $data];
            $this->request = new InputBag($data);
        }
    }

    /**
     * Make sure we have a session and that it has been started.
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
            $session = $this->getSession();
        }

        $session->start();
    }

    /**
     * Set the user making the request.
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * Get the currently authenticated user.
     *
     * @throws Exception
     *
     * @return ?User
     */
    public function user(): ?User
    {
        if ($this->user) {
            return $this->user;
        }

        $this->startSession();
        if (!$this->session instanceof SessionInterface) {
            throw new Exception('Failed to start session.');
        }
        $id = $this->session->get('login_id');
        $hash = $this->session->get('login_hash');
        $this->session->save();

        if (!$id || !$hash || !is_string($hash)) {
            return null;
        }

        $user = app(OrmService::class)->getOneByQuery(
            User::class,
            'SELECT * FROM `users` WHERE `id` = ' . $id . ' AND access != 0 AND password = ' . app(DbService::class)->quote($hash)
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
     * @throws Exception
     */
    public function logout(): void
    {
        $this->startSession();
        if (!$this->session instanceof SessionInterface) {
            throw new Exception('Failed to start session.');
        }

        $this->session->remove('login_id');
        $this->session->remove('login_hash');
        $this->session->save();
        $this->user = null;
    }

    /**
     * @throws Exception
     */
    public function getRequestString(string $key): ?string
    {
        $value = $this->request->get($key);
        if ($value === null) {
            return null;
        }
        if (!is_string($value)) {
            throw new InvalidInput(_('Invalid input.'));
        }

        return $value;
    }

    /**
     * @throws Exception
     */
    public function getRequestInt(string $key): ?int
    {
        $value = $this->request->get($key);
        if ($value === null) {
            return null;
        }
        if (!ctype_digit($value) && !is_int($value)) {
            throw new InvalidInput(_('Invalid input.'));
        }

        return (int)$value;
    }
}
