<?php

namespace Tochka\OpenRpc\Support;

use Illuminate\Support\Facades\Config;
use Tochka\OpenRpc\DTO\Contact;
use Tochka\OpenRpc\DTO\Info;
use Tochka\OpenRpc\DTO\License;
use Tochka\OpenRpc\DTO\Server;

class OpenRpcConfig
{
    public function __construct(
        public readonly array $methodPipes,
        public readonly array $typeDescriptors,
        public readonly ?Server $server,
        public readonly ?Info $info,
        public readonly ?string $cacheHandler,
        public readonly string $serverName,
    ) {
    }

    public static function makeFromConfigFile(string $serverName, string $configName = 'openrpc'): self
    {
        $config = Config::get($configName . '.' . $serverName, []);

        return new self(
            methodPipes: data_get($config, 'methodPipes', []),
            typeDescriptors: data_get($config, 'typeDescriptors', []),
            server: self::makeServer(data_get($config, 'server', null)),
            info: self::makeInfo(data_get($config, 'info', null)),
            cacheHandler: data_get($config, 'cache', null),
            serverName: $serverName,
        );
    }

    protected static function makeServer(?array $data): ?Server
    {
        if ($data === null) {
            return null;
        }

        $server = new Server(
            data_get($data, 'name', ''),
            data_get($data, 'url', 'http://localhost')
        );
        $server->description = data_get($data, 'description');
        $server->summary = data_get($data, 'summary');

        return $server;
    }

    protected static function makeInfo(?array $data): ?Info
    {
        if ($data === null) {
            return null;
        }
        $info = new Info(
            data_get($data, 'title', ''),
            data_get($data, 'version', '1.0.0'),
        );
        $info->description = data_get($data, 'description', '');

        // contact
        $contactData = data_get($data, 'contact');
        if ($contactData) {
            $contact = new Contact();
            $contact->name = data_get($contactData, 'name');
            $contact->email = data_get($contactData, 'email');
            $contact->url = data_get($contactData, 'url');
            $info->contact = $contact;
        }

        // licence
        $licenseData = data_get($data, 'license');
        if ($licenseData) {
            $license = new License(data_get($licenseData, 'name'));
            $license->url = data_get($licenseData, 'url', '');
            $info->license = $license;
        }

        $info->termsOfService = data_get($data, 'termsOfService', null);

        return $info;
    }
}
