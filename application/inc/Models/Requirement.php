<?php

namespace App\Models;

use App\Services\DbService;

class Requirement extends AbstractRenderable implements InterfaceRichText
{
    /** Table name in database. */
    public const TABLE_NAME = 'krav';

    // Backed by DB

    /** @var string The body HTML. */
    private $html = '';

    public function __construct(array $data = [])
    {
        $this->setHtml($data['html'])
            ->setTitle($data['title'])
            ->setId($data['id'] ?? null);
    }

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
     * Set HTML body.
     *
     * @param string $html The HTML body
     *
     * @return $this
     */
    public function setHtml(string $html): InterfaceRichText
    {
        $this->html = $html;

        return $this;
    }

    /**
     * Get the HTML body.
     */
    public function getHtml(): string
    {
        return $this->html;
    }

    // General methods

    /**
     * Get the url slug.
     */
    public function getSlug(): string
    {
        return 'krav/' . $this->getId() . '/' . cleanFileName($this->getTitle()) . '.html';
    }

    // ORM related functions

    public function getDbArray(): array
    {
        $db = app(DbService::class);

        return [
            'navn' => $db->quote($this->title),
            'text' => $db->quote($this->html),
        ];
    }
}
