<?php

namespace App\Models;

use App\Services\DbService;

class CustomPage extends AbstractEntity implements InterfaceRichText
{
    /** Table name in database. */
    public const TABLE_NAME = 'special';

    // Backed by DB

    /** @var string The title. */
    private $title = '';

    /** @var int The time of last save. */
    private $timeStamp;

    /** @var string HTML body. */
    private $html = '';

    public function __construct(array $data = [])
    {
        $this->setTimeStamp($data['timestamp'])
            ->setTitle($data['title'])
            ->setHtml($data['html'])
            ->setId($data['id'] ?? null);
    }

    public static function mapFromDB(array $data): array
    {
        return [
            'id'        => $data['id'],
            'timestamp' => strtotime($data['dato']) + app(DbService::class)->getTimeOffset(),
            'title'     => $data['navn'],
            'html'      => $data['text'],
        ];
    }

    // Getters and setters

    /**
     * Set last update time.
     *
     * @param int $timeStamp UnixTimeStamp
     *
     * @return $this
     */
    public function setTimeStamp(int $timeStamp): self
    {
        $this->timeStamp = $timeStamp;

        return $this;
    }

    /**
     * Get last update time.
     */
    public function getTimeStamp(): int
    {
        return $this->timeStamp;
    }

    /**
     * Set the title.
     *
     * @param string $title The title
     *
     * @return $this
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set the HTML body.
     *
     * @param string $html HTML body
     *
     * @return $this
     */
    public function setHtml(string $html): InterfaceRichText
    {
        $this->html = $html;

        return $this;
    }

    /**
     * Set the HTML body.
     */
    public function getHtml(): string
    {
        return $this->html;
    }

    // ORM related functions

    public function getDbArray(): array
    {
        $this->setTimeStamp(time());

        $db = app(DbService::class);

        return [
            'dato' => $db->getNowValue(),
            'navn' => $db->quote($this->title),
            'text' => $db->quote($this->html),
        ];
    }
}
