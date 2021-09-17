<?php

namespace Tochka\OpenRpc\Support;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;

class StrSupport
{
    use Macroable;
    
    public static function resolveRef(?string $value): ?string
    {
        if ($value !== null && Str::startsWith($value, '$')) {
            $filePath = App::resourcePath(Str::after($value, '$'));
            if (file_exists($filePath)) {
                return file_get_contents($filePath);
            }
        }
        
        return $value;
    }
    
    public static function fullyQualifiedClassName(string $className): string
    {
        return '\\' . trim($className, '\\');
    }
}
