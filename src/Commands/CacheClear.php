<?php

namespace Tochka\OpenRpc\Commands;

use Illuminate\Console\Command;
use Tochka\OpenRpc\Facades\OpenRpcCache;

class CacheClear extends Command
{
    protected $signature = 'openrpc:clear';
    protected $description = 'Clear cached OpenRpc schema';
    
    public function handle(): void
    {
        OpenRpcCache::clear();
    
        $this->info('OpenRpc cache cleared!');
    }
}
