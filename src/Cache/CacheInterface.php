<?php

namespace Tochka\OpenRpc\Cache;

interface CacheInterface
{
    public function get(): ?string;
    public function set(object|array $data): void;
    public function clear(): void;
}
