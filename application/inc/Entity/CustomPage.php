<?php namespace AGCMS\Entity;

use AGCMS\ORM;
use AGCMS\Render;

class CustomPage extends AbstractEntity
{
    /**
     * Table name in database
     */
    const TABLE_NAME = 'special';

    // Backed by DB
    /**
     * The title
     */
    private $title;

    /**
     * The time of last save
     */
    private $timeStamp;

    /**
     * HTML body
     */
    private $html;

    /**
     * Construct the entity
     *
     * @param array $data The entity data
     */
    public function __construct(array $data)
    {
        $this->setId($data['id'] ?? null)
            ->setTimeStamp($data['timestamp'])
            ->setTitle($data['title'])
            ->setHtml($data['html']);
    }

    /**
     * Map data from DB table to entity
     *
     * @param array The data from the database
     *
     * @return array
     */
    public static function mapFromDB(array $data): array
    {
        return [
            'id'        => $data['id'],
            'timestamp' => strtotime($data['dato']) + db()->getTimeOffset(),
            'title'     => $data['navn'],
            'html'      => $data['text'],
        ];
    }

    // Getters and setters
    /**
     * Set last update time
     *
     * @param int $timeStamp UnixTimeStamp
     *
     * @return self
     */
    public function setTimeStamp(int $timeStamp): self
    {
        $this->timeStamp = $timeStamp;

        return $this;
    }

    /**
     * Get last update time
     *
     * @return int
     */
    public function getTimeStamp(): int
    {
        return $this->timeStamp;
    }

    /**
     * Set the title
     *
     * @param string $title The title
     *
     * @return self
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set the HTML body
     *
     * @param string $html HTML body
     *
     * @return self
     */
    public function setHtml(string $html): self
    {
        $this->html = $html;

        return $this;
    }

    /**
     * Set the HTML body
     *
     * @return string
     */
    public function getHtml(): string
    {
        return $this->html;
    }

    // ORM related functions
    /**
     * Save entity to database
     *
     * @return self
     */
    public function save(): InterfaceEntity
    {
        if ($this->id === null) {
            db()->query(
                "
                INSERT INTO `" . self::TABLE_NAME . "` (
                    `dato`,
                    `navn`,
                    `text`
                ) VALUES (
                    NOW(),
                    '" . db()->esc($this->title) . "',
                    '" . db()->esc($this->html) . "'
                )
                "
            );
            $this->setId(db()->insert_id);
        } else {
            db()->query(
                "
                UPDATE `" . self::TABLE_NAME . "` SET
                    `dato` = NOW(),
                    `navn` = '" . db()->esc($this->title) . "',
                    `text` = '" . db()->esc($this->html) . "'
                WHERE `id` = " . $this->id
            );
        }
        $this->setTimeStamp(time());
        Render::addLoadedTable(self::TABLE_NAME);

        return $this;
    }
}
