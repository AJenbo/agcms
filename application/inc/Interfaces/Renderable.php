<?php namespace AGCMS\Interfaces;

interface Renderable
{
    public function getTitle(): string;
    public function getCanonicalLink(): string;
}
