<?php

namespace Tochka\OpenRpc\Cache;

use Illuminate\Support\Facades\App;

class FileCache implements CacheInterface
{
    private string $filePath;
    
    public function __construct(string $cacheName, ?string $cachePath = null)
    {
        $cacheName = 'jsonrpc_' . $cacheName;
        $cachePath = $cachePath ?? App::bootstrapPath('cache');
        $this->filePath = $cachePath . '/' . $cacheName . '.json';
    }
    
    public function get(): ?string
    {
        if (file_exists($this->filePath)) {
            return file_get_contents($this->filePath);
        }
        
        return null;
    }
    
    /**
     * @throws \JsonException
     */
    public function set(object|array $data): void
    {
        $data = json_encode($data, JSON_THROW_ON_ERROR);
        file_put_contents($this->filePath, $data);
    }
    
    public function clear(): void
    {
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }
    }
}
