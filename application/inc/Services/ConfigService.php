<?php

namespace App\Services;

use App\DTO\EmailConfig;
use Exception;

class ConfigService
{
    /** @var array<string, mixed> Store the configurations. */
    private static array $config = [];

    /**
     * Load the site configurations.
     *
     * Will fallback to config_sample.php if config.php does not exist.
     */
    public static function load(string $basePath): void
    {
        $config = @include $basePath . '/inc/config.php';
        if ($config === false) {
            $config = include $basePath . '/inc/config_sample.php';
        }
        self::$config = $config;
    }

    /**
     * @param mixed $default What to return if key does not exists
     */
    public static function get(string $key, $default = null): mixed
    {
        return self::$config[$key] ?? $default;
    }

    /**
     * @return array<string, EmailConfig>
     *
     * @throws Exception
     */
    public static function getEmailConfigs(): array
    {
        $emails = [];

        $configs = self::getArray('emails');
        if (!$configs) {
            throw new Exception('Invalid email configuration.');
        }

        foreach ($configs as $key => $config) {
            if (!is_string($key) || !$config instanceof EmailConfig) {
                throw new Exception('Invalid email configuration.');
            }
            $emails[$key] = $config;
        }

        return $emails;
    }

    public static function getDefaultEmail(): string
    {
        $emails = first(self::getEmailConfigs());

        return $emails->address;
    }

    public static function getString(string $key, ?string $default = null): string
    {
        $value = self::get($key);
        if (!is_string($value)) {
            throw new Exception('Value for ' . $key . ' is a ' . gettype($value) . ' but should be a string.');
        }

        return $value;
    }

    public static function getInt(string $key, ?int $default = null): int
    {
        $value = self::get($key);
        if (!is_int($value)) {
            throw new Exception('Value for ' . $key . ' is a ' . gettype($value) . ' but should be an integer.');
        }

        return $value;
    }

    public static function getBool(string $key, ?bool $default = null): bool
    {
        $value = self::get($key);
        if (!is_bool($value)) {
            throw new Exception('Value for ' . $key . ' is a ' . gettype($value) . ' but should be a boolean.');
        }

        return $value;
    }

    /**
     * @return array<mixed>
     */
    public static function getArray(string $key): array
    {
        $value = self::get($key);
        if (!is_array($value)) {
            throw new Exception('Value for ' . $key . ' is a ' . gettype($value) . ' but should be an array.');
        }

        return $value;
    }
}
