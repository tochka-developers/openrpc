<?php

namespace Tochka\OpenRpc;

use Tochka\OpenRpc\Facades\OpenRpc;

class AutoDiscoverController
{
    public function discover(): array
    {
        return [
            'name' => 'OpenRPC Schema',
            'schema' => OpenRpc::handle(),
        ];
    }
}
