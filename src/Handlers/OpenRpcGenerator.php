<?php

namespace Tochka\OpenRpc\Handlers;

use Illuminate\Support\Facades\Config;
use Tochka\OpenRpc\Contracts\OpenRpcHandlerInterface;
use Tochka\OpenRpc\DTO\Components;
use Tochka\OpenRpc\DTO\Contact;
use Tochka\OpenRpc\DTO\ExternalDocumentation;
use Tochka\OpenRpc\DTO\Info;
use Tochka\OpenRpc\DTO\License;
use Tochka\OpenRpc\DTO\Method;
use Tochka\OpenRpc\DTO\OpenRpc;
use Tochka\OpenRpc\DTO\Server;
use Tochka\OpenRpc\Facades\MethodDescription;
use Tochka\OpenRpc\Support\ReferenceCleaner;
use Tochka\OpenRpc\Support\StrSupport;

class OpenRpcGenerator implements OpenRpcHandlerInterface
{
    public const OPEN_RPC_VERSION = '1.2.6';
    
    private array $openRpcConfig;
    private array $jsonRpcConfig;
    
    /** @var array<Server> */
    private array $servers = [];
    
    public function __construct(array $openRpcConfig, array $jsonRpcConfig)
    {
        $this->openRpcConfig = $openRpcConfig;
        $this->jsonRpcConfig = $jsonRpcConfig;
    }
    
    public function handle(): array
    {
        $openRpc = new OpenRpc(self::OPEN_RPC_VERSION, $this->getInfo(), []);
        
        $externalDocumentation = $this->getExternalDocumentation();
        if ($externalDocumentation) {
            $openRpc->externalDocumentation = $externalDocumentation;
        }
        
        $openRpc->servers = $this->getServers();
        $openRpc->methods = $this->getMethods();
        $openRpc->components = $this->getComponents();
    
        ReferenceCleaner::clean($openRpc);
        
        return $openRpc->toArray();
    }
    
    protected function getInfo(): Info
    {
        $info = new Info(
            data_get($this->openRpcConfig, 'title', Config::get('app.name', 'JsonRpc API')),
            data_get($this->openRpcConfig, 'version', '1.0.0')
        );
        
        $info->description = StrSupport::resolveRef(data_get($this->openRpcConfig, 'description'));
        $info->termsOfService = data_get($this->openRpcConfig, 'termsOfService');
        
        if (!empty($this->openRpcConfig['contact'])) {
            $info->contact = new Contact();
            $info->contact->email = data_get($this->openRpcConfig, 'contact.email');
            $info->contact->name = data_get($this->openRpcConfig, 'contact.name');
            $info->contact->url = data_get($this->openRpcConfig, 'contact.url');
        }
        
        if (!empty($this->openRpcConfig['license']['name'])) {
            $info->license = new License($this->openRpcConfig['license']['name']);
            $info->license->url = data_get($this->openRpcConfig, 'license.url');
        }
        
        return $info;
    }
    
    protected function getExternalDocumentation(): ?ExternalDocumentation
    {
        $instance = null;
        $url = data_get($this->openRpcConfig, 'externalDocumentation.url');
        
        if ($url) {
            $instance = new ExternalDocumentation($url);
            $instance->description = StrSupport::resolveRef(
                data_get($this->openRpcConfig, 'externalDocumentation.description')
            );
        }
        
        return $instance;
    }
    
    /**
     * @return array<Method>
     */
    private function getMethods(): array
    {
        return MethodDescription::generate($this->jsonRpcConfig, $this->servers);
    }
    
    private function getComponents(): Components
    {
        return MethodDescription::getComponents();
    }
    
    /**
     * @return array<Method>
     */
    private function getServers(): array
    {
        foreach ($this->jsonRpcConfig as $name => $server) {
            $url = trim(Config::get('app.url'), '/') . '/' . trim($server['url'] ?? '', '/');
            $openRpcServer = new Server($name, $url);
            $openRpcServer->summary = data_get($server, 'summary');
            $openRpcServer->description = StrSupport::resolveRef(data_get($server, 'description'));
            
            $this->servers[$name] = $openRpcServer;
        }
        
        return array_values($this->servers);
    }
}



