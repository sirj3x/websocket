<?php

namespace Sirj3x\Websocket\Helpers;

use Sirj3x\Jxt\JxtEncryption;

trait ResponseHelper
{
    public function success($data = []): array
    {
        if (config('websocket.data_encryption')) {
            $data = JxtEncryption::encode(json_encode($data), config('websocket.data_encryption_secret_key'));
        }

        return [
            'status' => 200,
            'data' => $data
        ];
    }

    public function error($messages, $statusCode): array
    {
        $data = [
            'message' => [$messages]
        ];

        if (config('websocket.data_encryption')) {
            $data = JxtEncryption::encode($data, config('websocket.data_encryption_secret_key'));
        }

        return [
            'status' => $statusCode,
            'data' => $data
        ];
    }
}
