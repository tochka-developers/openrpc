<?php

return [
    'default' => [
        'server'         => [
            'name'        => 'server',
            'url'         => 'https://127.0.0.1/api/v1/openrpc',
            'summary'     => 'summary',
            'description' => 'description',
        ],
        'info'           => [
            'version'     => '1.0.0',
            'title'       => 'service',
            'description' => 'description',
            'contact'     => [
                'name'  => 'name',
                'url'   => 'https://127.0.0.1/contact',
                'email' => 'name@example.com',
            ],
            'license'     => [
                'name' => 'MIT',
                'url'  => 'http://mit.com',
            ],
            'termsOfService' => 'https://127.0.0.1/termsOfService',
        ],
        'methodPipes' => [
            \Tochka\OpenRpc\Descriptors\Method\Pipes\MethodPipe::class,
            \Tochka\OpenRpc\Descriptors\Method\Pipes\ParameterPipe::class,
            \Tochka\OpenRpc\Descriptors\Method\Pipes\ResultPipe::class,
        ],
        'typeDescriptors' => [
            \Tochka\OpenRpc\Descriptors\Type\Handlers\EnumHandler::class,
            \Tochka\OpenRpc\Descriptors\Type\Handlers\DateTimeHandler::class,
            \Tochka\OpenRpc\Descriptors\Type\Handlers\ModelHandler::class,
            \Tochka\OpenRpc\Descriptors\Type\Handlers\TimeZoneHandler::class,
            \Tochka\OpenRpc\Descriptors\Type\Handlers\CollectionHandler::class,
            \Tochka\OpenRpc\Descriptors\Type\Handlers\ObjectHandler::class,
        ],
        'cache' => \Tochka\OpenRpc\Cache\FileCache::class,
    ],
];
