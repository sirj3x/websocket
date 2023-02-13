<?php

namespace Sirj3x\Websocket\Libraries;

class Router
{
    public array $routes = [];

    public function publish($name, $class): void
    {
        $this->routes[$name] = [
            'name' => $name,
            'type' => 'publish',
            'class' => $class
        ];
    }

    public function subscribe($name, $class): void
    {
        $this->routes[$name] = [
            'name' => $name,
            'type' => 'subscribe',
            'class' => $class
        ];
    }

    public function all(): array
    {
        return $this->routes;
    }
}
