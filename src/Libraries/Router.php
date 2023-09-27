<?php

namespace Sirj3x\Websocket\Libraries;

class Router
{
    public array $routes = [];

    public function register($name, $class): void
    {
        $this->routes[$name] = [
            'name' => $name,
            'class' => $class
        ];
    }

    public function all(): array
    {
        return $this->routes;
    }
}
