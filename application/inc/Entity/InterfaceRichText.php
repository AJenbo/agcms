<?php namespace AGCMS\Entity;

interface InterfaceRichText extends InterfaceEntity
{
    /**
     * Set the HTML body.
     *
     * @param string $html HTML body
     *
     * @return $this
     */
    public function setHtml(string $html): self;

    /**
     * Set the HTML body.
     *
     * @return string
     */
    public function getHtml(): string;
}
