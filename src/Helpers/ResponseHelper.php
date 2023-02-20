<?php

namespace Sirj3x\Websocket\Helpers;

trait ResponseHelper
{
    public function success($data = []): array
    {
        return [
            'status' => 200,
            'data' => $data
        ];
    }

    public function error($messages, $statusCode): array
    {
        return [
            'status' => $statusCode,
            'data' => [
                'message' => [$messages]
            ]
        ];
    }
}
