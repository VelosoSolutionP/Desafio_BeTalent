<?php

namespace App\Services\Gateways;

use RuntimeException;

class GatewayRegistry
{
    
    private static array $map = [
        'Gateway1' => Gateway1::class,
        'Gateway2' => Gateway2::class,
    ];

    public static function get(string $name): GatewayInterface
    {
        if (!isset(self::$map[$name])) {
            throw new RuntimeException("No adapter registered for gateway: {$name}");
        }

        return app(self::$map[$name]);
    }
}
