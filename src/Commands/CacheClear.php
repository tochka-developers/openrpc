<?php

namespace Tochka\OpenRpc\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Psr\SimpleCache\CacheInterface;

class CacheClear extends Command
{
    protected $signature = 'openrpc:clear';
    protected $description = 'Clear cached OpenRpc schema';
    
    public function handle(): void
    {
        /** @var CacheInterface $cache */
        $cache = App::make('OpenRpcCache');
    
        $cache->clear();
        $this->info('OpenRpc cache cleared!');
    }
}
