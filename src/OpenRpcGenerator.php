<?php

namespace Tochka\OpenRpc;

use Illuminate\Support\Facades\Config;
use Tochka\OpenRpc\DTO\Components;
use Tochka\OpenRpc\DTO\Contact;
use Tochka\OpenRpc\DTO\ExternalDocumentation;
use Tochka\OpenRpc\DTO\Info;
use Tochka\OpenRpc\DTO\License;
use Tochka\OpenRpc\DTO\Method;
use Tochka\OpenRpc\DTO\OpenRpc;
use Tochka\OpenRpc\DTO\Server;
use Tochka\OpenRpc\Facades\MethodDescription;

class OpenRpcGenerator
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
        
        return $openRpc->toArray();
    }
    
    protected function getInfo(): Info
    {
        $info = new Info(
            data_get($this->openRpcConfig, 'title', Config::get('app.name', 'JsonRpc API')),
            data_get($this->openRpcConfig, 'version', '1.0.0')
        );
        
        set_field_if_not_empty(data_get($this->openRpcConfig, 'description'), $info, 'description');
        if (!empty($this->openRpcConfig['termsOfService'])) {
            $info->termsOfService = $this->openRpcConfig['termsOfService'];
        }
        if (!empty($this->openRpcConfig['contact'])) {
            $info->contact = new Contact();
            if (!empty($this->openRpcConfig['contact']['email'])) {
                $info->contact->email = $this->openRpcConfig['contact']['email'];
            }
            if (!empty($this->openRpcConfig['contact']['name'])) {
                $info->contact->name = $this->openRpcConfig['contact']['name'];
            }
            if (!empty($this->openRpcConfig['contact']['url'])) {
                $info->contact->url = $this->openRpcConfig['contact']['url'];
            }
        }
        if (!empty($this->openRpcConfig['license']['name'])) {
            $info->license = new License($this->openRpcConfig['license']['name']);
            if (!empty($this->openRpcConfig['license']['url'])) {
                $info->license->url = $this->openRpcConfig['license']['url'];
            }
        }
        
        return $info;
    }
    
    protected function getExternalDocumentation(): ?ExternalDocumentation
    {
        $instance = null;
        $url = data_get($this->openRpcConfig, 'externalDocumentation.url');
        
        if ($url) {
            $instance = new ExternalDocumentation($url);
            set_field_if_not_empty(
                data_get($this->openRpcConfig, 'externalDocumentation.description'),
                $instance,
                'description'
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
            if (isset($server['summary'])) {
                $openRpcServer->summary = $server['summary'];
            }
            if (isset($server['description'])) {
                set_field_if_not_empty($server['description'], $openRpcServer, 'description');
            }
            
            $this->servers[$name] = $openRpcServer;
        }
        
        return array_values($this->servers);
    }
}



