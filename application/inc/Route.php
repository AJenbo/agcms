<?php

namespace App;

use App\Http\Controllers\AbstractController;

class Route
{
    private string $uri;
    /** @var class-string<AbstractController> */
    private string $controller;
    private string $action;

    /**
     * @param class-string<AbstractController> $controller
     */
    public function __construct(string $uri, string $controller, string $action)
    {
        $this->uri = $uri;
        $this->controller = $controller;
        $this->action = $action;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return class-string<AbstractController>
     */
    public function getController(): string
    {
        return $this->controller;
    }

    public function getAction(): string
    {
        return $this->action;
    }
}
