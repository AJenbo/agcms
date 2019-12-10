<?php namespace App\Models;

use App\Services\DbService;

class CustomPage extends AbstractEntity implements InterfaceRichText
{
    /** Table name in database. */
    const TABLE_NAME = 'special';

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
        /** @var DbService */
        $db = app(DbService::class);

        return [
            'id'        => $data['id'],
            'timestamp' => strtotime($data['dato']) + $db->getTimeOffset(),
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
     *
     * @return int
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
     *
     * @return string
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
     *
     * @return string
     */
    public function getHtml(): string
    {
        return $this->html;
    }

    // ORM related functions

    public function getDbArray(): array
    {
        $this->setTimeStamp(time());

        /** @var DbService */
        $db = app(DbService::class);

        return [
            'dato' => $db->getNowValue(),
            'navn' => $db->quote($this->title),
            'text' => $db->quote($this->html),
        ];
    }
}
