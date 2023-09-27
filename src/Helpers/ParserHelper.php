<?php

namespace Sirj3x\Websocket\Helpers;

use App\Libraries\EncryptionLibrary;

class ParserHelper
{
    public static function encode($data)
    {
        if (config('websocket.io_encryption')) {
            return EncryptionLibrary::encrypt(json_encode($data), config('websocket.io_encryption_secret_key'));
        } else {
            return json_encode($data);
        }
    }

    public static function decode($data)
    {
        if (config('websocket.io_encryption')) {
            return json_decode(EncryptionLibrary::decrypt($data, config('websocket.io_encryption_secret_key')), true);
        } else {
            return json_decode($data, true);
        }
    }
}
