<?php

namespace Tochka\OpenRpc\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Tochka\OpenRpc\Facades\OpenRpc;

class Cache extends Command
{
    protected $signature = 'openrpc:cache';
    protected $description = 'Make and cache OpenRpc schema';
    
    /**
     * @throws InvalidArgumentException
     */
    public function handle(): void
    {
        /** @var CacheInterface $cache */
        $cache = App::make('OpenRpcCache');
    
        $cache->clear();
        $this->info('OpenRpc cache cleared!');
    
        $cache->set('schema', OpenRpc::handle());
        $this->info('OpenRpc cached successfully!');
    }
}
