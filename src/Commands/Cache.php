<?php

namespace Tochka\OpenRpc\Commands;

use Illuminate\Console\Command;
use Tochka\OpenRpc\Facades\OpenRpc;
use Tochka\OpenRpc\Facades\OpenRpcCache;

class Cache extends Command
{
    protected $signature = 'openrpc:cache';
    protected $description = 'Make and cache OpenRpc schema';
    
    public function handle(): void
    {
        OpenRpcCache::clear();
        $this->info('OpenRpc cache cleared!');
        
        OpenRpcCache::save(OpenRpc::handle());
        $this->info('OpenRpc cached successfully!');
    }
}
