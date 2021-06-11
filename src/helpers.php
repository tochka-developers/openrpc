<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\ContextFactory;
use Tochka\OpenRpc\DTO\Schema;

if (!function_exists('set_field_if_not_empty')) {
    function set_field_if_not_empty(?string $value, object $instance, string $fieldName): void
    {
        if (!empty($value)) {
            if (Str::startsWith($value, '$')) {
                $file = Str::after($value, '$');
                $value = file_get_contents(App::resourcePath($file));
            }
            
            $instance->$fieldName = $value;
        }
    }
}

if (!function_exists('set_title_and_description_from_class')) {
    /**
     * @throws \ReflectionException
     */
    function set_title_and_description_from_class(Schema $schema, string $className, DocBlockFactory $docBlockFactory): void
    {
        $reflectionClass = new \ReflectionClass($className);
        $classDocComment = $reflectionClass->getDocComment();
        $phpDocContext = (new ContextFactory())->createFromReflector($reflectionClass);
        $classDocBlock = $classDocComment !== false
            ? $docBlockFactory->create($classDocComment, $phpDocContext)
            : null;
        
        if ($classDocBlock !== null) {
            $description = implode(
                PHP_EOL . PHP_EOL,
                array_filter(
                    [
                        $classDocBlock->getSummary(),
                        $classDocBlock->getDescription()->getBodyTemplate(),
                    ]
                )
            );
            if (!empty($description)) {
                set_field_if_not_empty($description, $schema, 'description');
            }
        }
    }
}
