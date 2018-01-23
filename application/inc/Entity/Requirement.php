<?php namespace AGCMS\Entity;

class Requirement extends AbstractRenderable implements InterfaceRichText
{
    /** Table name in database. */
    const TABLE_NAME = 'krav';

    // Backed by DB

    /** @var string The body HTML. */
    private $html = '';

    /**
     * Construct the entity.
     *
     * @param array $data The entity data
     */
    public function __construct(array $data = [])
    {
        $this->setHtml($data['html'])
            ->setTitle($data['title'])
            ->setId($data['id'] ?? null);
    }

    /**
     * Map data from DB table to entity.
     *
     * @param array $data The data from the database
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
     *
     * @return string
     */
    public function getHtml(): string
    {
        return $this->html;
    }

    // General methods

    /**
     * Get the url slug.
     *
     * @return string
     */
    public function getSlug(): string
    {
        return 'krav/' . $this->getId() . '/' . clearFileName($this->getTitle()) . '.html';
    }

    // ORM related functions

    /**
     * Get data in array format for the database.
     *
     * @return string[]
     */
    public function getDbArray(): array
    {
        return [
            'navn' => db()->quote($this->title),
            'text' => db()->quote($this->html),
        ];
    }
}
