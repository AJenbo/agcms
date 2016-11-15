<?php

class CustomPage
{
    const TABLE_NAME = 'special';

    // Backed by DB
    private $id;
    private $timeStamp;
    private $title;
    private $html;

    public function __construct(array $data)
    {
        $this->setId($data['id'] ?? null)
            ->setTimeStamp($data['timestamp'])
            ->setTitle($data['title'])
            ->setHtml($data['html']);
    }

    public static function mapFromDB(array $data): array
    {
        return [
            'id'        => $data['id'] ?: null,
            'timestamp' => $data['dato'] ? strtotime($data['dato']) + db()->getTimeOffset() : 0,
            'title'     => $data['navn'] ?: '',
            'html'      => $data['text'] ?: '',
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

    public function setTimeStamp(int $timeStamp): self
    {
        $this->timeStamp = $timeStamp;

        return $this;
    }

    public function getTimeStamp(): int
    {
        return $this->timeStamp;
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

    // ORM related functions
    public function save()
    {
        if (!$this->id) {
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
        Render::addLoadedTable(self::TABLE_NAME);
    }
}
