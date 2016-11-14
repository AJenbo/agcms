<?php

class Requirement
{
    const TABLE_NAME = 'krav';

    // Backed by DB
    private $id;
    private $title;
    private $html;

    public function __construct(array $data)
    {
        $this->setId($data['id'] ?? null)
            ->setTitle($data['title'])
            ->setHtml($data['html']);
    }

    public static function mapFromDB(array $data): array
    {
        return [
            'id'    => $data['id'] ?: null,
            'title' => $data['navn'] ?: '',
            'html'  => $data['text'] ?: '',
        ];
    }

    // Getters and setters
    private function setId(int $id = null): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        if (!$this->id) {
            $this->save();
        }

        return $this->id;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setHtml(string $html): self
    {
        $this->html = $html;

        return $this;
    }

    public function getHtml(): string
    {
        return $this->html;
    }

    // General methodes
    public function getSlug(): string
    {
        return 'krav/' . $this->getId() . '/' . clearFileName($this->getTitle()) . '.html';
    }

    // ORM related functions
    public function save()
    {
        if (!$this->id) {
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
