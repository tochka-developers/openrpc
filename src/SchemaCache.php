<?php

namespace Tochka\OpenRpc;

class SchemaCache
{
    private string $cachePath;
    
    public function __construct(string $cachePath)
    {
        $this->cachePath = $cachePath;
    }
    
    public function get(): ?array
    {
        $filePath = $this->getCacheFilePath();
        if (file_exists($filePath)) {
            return require $filePath;
        }
        
        return null;
    }
    
    public function save(array $values): void
    {
        if (!mkdir($this->cachePath) && !is_dir($this->cachePath)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->cachePath));
        }
        
        file_put_contents($this->getCacheFilePath(), '<?php return ' . var_export($values, true) . ';' . PHP_EOL);
    }
    
    public function clear(): void
    {
        unlink($this->getCacheFilePath());
    }
    
    private function getCacheFilePath(): string
    {
        return trim($this->cachePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'openrpc.php';
    }
}
