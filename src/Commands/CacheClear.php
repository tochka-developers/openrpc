<?php

namespace Tochka\OpenRpc\Commands;

use Illuminate\Console\Command;

class CacheClear extends Command
{
    protected $signature = 'openrpc:clear';
    protected $description = 'Clear cached OpenRpc schema';
    
    public function handle(): void
    {
    
    }
}
