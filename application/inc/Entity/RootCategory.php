<?php namespace AGCMS\Entity;

/**
 * Dummy entity for handeling the two root entities.
 */
class RootCategory extends Category
{
    public function __construct(array $data)
    {
        $this->setId($data['id'])
            ->setTitle($data['title'])
            ->setWeightedChildren(true);
    }

    public function save(): InterfaceEntity
    {
        return $this;
    }

    public function delete(): bool
    {
        return true;
    }

    public function getSlug(): string
    {
        return '';
    }
}
