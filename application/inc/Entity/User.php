<?php namespace AGCMS\Entity;

use DateTime;

class User extends AbstractEntity
{
    /** Table name */
    const TABLE_NAME = 'users';
    /** Number of secounds a user is assumed to be active. */
    const ONLINE_INTERVAL = 1800;

    /** Not approved user */
    const NO_ACCESS = 0;
    /** Full access user */
    const ADMINISTRATOR = 1;
    /** Can't edit other users */
    const MANAGER = 3;
    /** Can only handle orders */
    const CLERK = 4;

    /** @var string User's full name. */
    private $fullName = '';
    /** @var string User's nick name. */
    private $nickname = '';
    /** @var string User's Password hash. */
    private $passwordHash = '';
    /** @var int User's access level. */
    private $accessLevel = 0;
    /** @var int time of last login */
    private $lastLogin;

    /**
     * Create an entity.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->setFullName($data['full_name'])
            ->setNickname($data['nickname'])
            ->setPasswordHash($data['password_hash'])
            ->setAccessLevel($data['access_level'])
            ->setLastLogin($data['last_login'])
            ->setId($data['id'] ?? null);
    }

    /**
     * Map database content to enity format.
     *
     * @param array $data
     *
     * @return array
     */
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
     * Prepare data for injecting in to the database.
     *
     * @return string[]
     */
    public function getDbArray(): array
    {
        return [
            'fullname'  => db()->eandq($this->fullName),
            'name'      => db()->eandq($this->nickname),
            'password'  => db()->eandq($this->passwordHash),
            'access'    => (string) $this->accessLevel,
            'lastlogin' => 'FROM_UNIXTIME(' . $this->lastLogin . ')',
        ];
    }

    /**
     * Set user's full name.
     *
     * @param string $fullName
     *
     * @return self
     */
    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    /**
     * Get user's full name.
     *
     * @return string
     */
    public function getFullName(): string
    {
        return $this->fullName;
    }

    /**
     * Set user's nick name.
     *
     * @param string $nickname
     *
     * @return self
     */
    public function setNickname(string $nickname): self
    {
        $this->nickname = $nickname;

        return $this;
    }

    /**
     * Get nick name.
     *
     * @return string
     */
    public function getNickname(): string
    {
        return $this->nickname;
    }

    /**
     * Set users password.
     *
     * @param string $password
     *
     * @return self
     */
    public function setPassword(string $password): self
    {
        $this->passwordHash = password_hash($password, PASSWORD_BCRYPT);

        return $this;
    }

    /**
     * Set users password hash.
     *
     * @param string $passwordHash
     *
     * @return self
     */
    public function setPasswordHash(string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;

        return $this;
    }

    /**
     * Get password hash.
     *
     * @return string
     */
    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    /**
     * Set access level.
     *
     * @param int $accessLevel
     *
     * @return self
     */
    public function setAccessLevel(int $accessLevel): self
    {
        $this->accessLevel = $accessLevel;

        return $this;
    }

    /**
     * Get access level.
     *
     * @return int
     */
    public function getAccessLevel(): int
    {
        return $this->accessLevel;
    }

    /**
     * Set last activity time.
     *
     * @param int $lastLogin
     *
     * @return self
     */
    public function setLastLogin(int $lastLogin): self
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    /**
     * Get last activity time.
     *
     * @return int
     */
    public function getLastLogin(): int
    {
        return $this->lastLogin;
    }

    /**
     * Validate a password with this user.
     *
     * @param string $password
     *
     * @return bool
     */
    public function validatePassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }

    /**
     * Check if user has given access level (or higher).
     *
     * @param int $requestedLevel
     *
     * @return bool
     */
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

    /**
     * Generate a human frindly string showing time since last active.
     *
     * @return string
     */
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
