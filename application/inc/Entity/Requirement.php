<?php

class Requirement extends AbstractRenderable
{
    /**
     * Table name in database
     */
    const TABLE_NAME = 'krav';

    // Backed by DB
    /**
     * The body HTML
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
            'id'    => $data['id'],
            'title' => $data['navn'],
            'html'  => $data['text'],
        ];
    }

    // Getters and setters
    /**
     * Set title
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

    // General methodes
    /**
     * Get the url slug
     *
     * @return string
     */
    public function getSlug(): string
    {
        return 'krav/' . $this->getId() . '/' . clearFileName($this->getTitle()) . '.html';
    }

    // ORM related functions
    /**
     * Save entity to database
     */
    public function save()
    {
        if ($this->id === null) {
            db()->query(
                "
                INSERT INTO `" . self::TABLE_NAME . "` (
                    `navn`,
                    `text`
                ) VALUES (
                    NOW(),
                    '" . db()->esc($this->name) . "',
                    '" . db()->esc($this->html) . "'
                )
                "
            );
            $this->setId(db()->insert_id);
        } else {
            db()->query(
                "
                UPDATE `" . self::TABLE_NAME . "` SET
                    `navn` = '" . db()->esc($this->title) . "',
                    `text` = '" . db()->esc($this->html) . "'
                WHERE `id` = " . $this->id
            );
        }
        Render::addLoadedTable(self::TABLE_NAME);
    }
}
