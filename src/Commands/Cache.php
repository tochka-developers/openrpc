<?php

namespace Tochka\OpenRpc\Commands;

use Illuminate\Console\Command;

class Cache extends Command
{
    protected $signature = 'openrpc:cache';
    protected $description = 'Make and cache OpenRpc schema';
    
    public function handle(): void
    {
    
    }
}
