<?php namespace AGCMS\Entity;

use DateTime;

class User extends AbstractEntity
{
    const TABLE_NAME = 'users';
    const ONLINE_INTERVAL = 1800;

    const NO_ACCESS = 0;
    const ADMINISTRATOR = 1;
    const MANAGER = 3;
    const CLERK = 4;

    private $fullName = '';
    private $nickname = '';
    private $passwordHash = '';
    private $accessLevel = 0;
    /** @var int */
    private $lastLogin;

    public function __construct(array $data)
    {
        $this->setId($data['id'] ?? null)
            ->setFullName($data['full_name'])
            ->setNickname($data['nickname'])
            ->setPasswordHash($data['password_hash'])
            ->setAccessLevel($data['access_level'])
            ->setLastLogin($data['last_login']);
    }

    public static function mapFromDB(array $data): array
    {
        return [
            'id'            => $data['id'],
            'full_name'     => $data['fullname'],
            'nickname'      => $data['name'],
            'password_hash' => $data['password'],
            'access_level'  => $data['access'],
            'last_login'    => strtotime($data['lastlogin']) + db()->getTimeOffset(),
        ];
    }

    /**
     * @return string[]
     */
    public function getDbArray(): array
    {
        return [
            'fullname'  => db()->eandq($this->fullName),
            'name'      => db()->eandq($this->nickname),
            'password'  => db()->eandq($this->passwordHash),
            'access'    => (string) $this->accessLevel,
            'lastlogin' => 'UNIX_TIMESTAMP(' . $this->lastLogin . ')',
        ];
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setNickname(string $nickname): self
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function setPassword(string $password): self
    {
        $this->passwordHash = crypt($password);

        return $this;
    }

    public function setPasswordHash(string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;

        return $this;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setAccessLevel(int $accessLevel): self
    {
        $this->accessLevel = $accessLevel;

        return $this;
    }

    public function getAccessLevel(): int
    {
        return $this->accessLevel;
    }

    public function setLastLogin(int $lastLogin): self
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getLastLogin(): int
    {
        return $this->lastLogin;
    }

    public function validatePassword(string $password): bool
    {
        if (mb_substr($this->passwordHash, 0, 13) === mb_substr(crypt($password, $this->passwordHash), 0, 13)) {
            return true;
        }

        return false;
    }

    public function hasAccess(int $requestedLevel): bool
    {
        if (!$this->accessLevel) {
            return false;
        }

        if ($this->accessLevel <= $requestedLevel) {
            return true;
        }

        return false;
    }

    public function getLastLoginText(): string
    {
        if (!$this->lastLogin) {
            return _('Never');
        }

        if (time() < $this->lastLogin + self::ONLINE_INTERVAL) {
            return _('Online');
        }

        $lastLoginDate = new DateTime('@' . $this->lastLogin);
        $interval = $lastLoginDate->diff(new DateTime('now'));

        if ($interval->y) {
            return sprintf(_('%s years and %s months ago'), $interval->y, $interval->m);
        }

        if ($interval->m) {
            return sprintf(_('%s months and %s dayes ago'), $interval->m, $interval->d);
        }

        if ($interval->d) {
            return sprintf(_('%s dayes and %s houres ago'), $interval->d, $interval->h);
        }

        if ($interval->h) {
            return sprintf(_('%s houres and %s minuts ago'), $interval->h, $interval->i);
        }

        return sprintf(_('%s minuts and %s secounds ago'), $interval->i, $interval->s);
    }
}
