<?php

namespace Tochka\OpenRpc\Support;

use Tochka\OpenRpc\DTO\OpenRpc;

class ReferenceCleaner
{
    public static function clean(OpenRpc $schema): void
    {
        $arraySchema = $schema->toArray();
        
        $schema->components->contentDescriptors = self::check($schema->components->contentDescriptors, '#/components/contentDescriptors/', $arraySchema);
        $schema->components->tags = self::check($schema->components->tags, '#/components/tags/', $arraySchema);
        $schema->components->schemas = self::check($schema->components->schemas, '#/components/schemas/', $arraySchema);
        $schema->components->examples = self::check($schema->components->examples, '#/components/examples/', $arraySchema);
    }
    
    private static function check(array $references, string $path, array $schema): array
    {
        $touched = [];
        $touchedInternal = [];
        
        foreach ($schema as $key => $item) {
            if ($key === '$ref') {
                $name = self::parseReference($item, $path);
                if ($name !== null) {
                    $touched[$name] = $references[$name];
                }
                
                continue;
            }
            
            if (is_array($item)) {
                $touchedInternal[] = self::check($references, $path, $item);
            }
        }
    
        return array_merge($touched, ...$touchedInternal);
    }
    
    private static function parseReference(string $reference, string $path): ?string
    {
        if (str_starts_with($reference, $path)) {
            return str_replace($path, '', $reference);
        }
        
        return null;
    }
}
