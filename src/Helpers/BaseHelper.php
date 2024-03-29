<?php

namespace Sirj3x\Websocket\Helpers;

class BaseHelper
{
    public static function sendError($conn, $message, $statusCode, $disconnect = false)
    {
        if ($conn) {
            $conn->send(ParserHelper::encode([
                'event' => 'error',
                'status' => $statusCode,
                'data' => [
                    'message' => [$message]
                ]
            ]));
            if ($disconnect) $conn->close();
            return false;
        } else {
            return [
                'event' => 'error',
                'status' => $statusCode,
                'data' => [
                    'message' => [$message]
                ]
            ];
        }
    }

    public static function routes(): array
    {
        $route = [];
        try {
            include base_path('routes/websocket.php');
            return $route->all();
        } catch (\Exception $exception) {
            return $route;
        }
    }

    public static function getRouteClass($event)
    {
        $routes = self::routes();
        return $routes[$event] ?? false;
    }

    public static function getUserGuardFromTableRow($row): string
    {
        return substr($row->getTable(), 0, strlen($row->getTable()) - 1);
    }

    public static function getGuardModel($guard)
    {
        $guardProvider = config('auth.guards')[$guard]["provider"] ?? null;
        if ($guardProvider === null) return null;
        $guardModel = config('auth.providers')["$guardProvider"]["model"] ?? null;
        if ($guardModel === null) return null;
        return $guardModel;
    }

    public static function getGuards(): array
    {
        $final = [];

        $guards = config('auth.guards');
        if (isset($guards["web"])) unset($guards["web"]);
        if (isset($guards["sanctum"])) unset($guards["sanctum"]);

        foreach ($guards as $guardName => $guardDetail) {
            $final[] = $guardName;
        }

        return $final;
    }
}
