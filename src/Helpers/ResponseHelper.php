<?php

namespace Sirj3x\Websocket\Helpers;

use App\Libraries\EncryptionLibrary;

trait ResponseHelper
{
    public function success($data = []): array
    {
        if (config('websocket.io_encryption')) {
            $data = EncryptionLibrary::encrypt(json_encode($data), config('websocket.io_encryption_secret_key'));
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

        if (config('websocket.io_encryption')) {
            $data = EncryptionLibrary::encrypt(json_encode($data), config('websocket.io_encryption_secret_key'));
        }

        return [
            'status' => $statusCode,
            'data' => $data
        ];
    }
}
