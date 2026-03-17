<?php

namespace Tochka\OpenRpc\Descriptors\Method\Pipes;

use phpDocumentor\Reflection\DocBlock\Tags\Property;
use Tochka\OpenRpc\Descriptors\Method\MethodContext;
use Tochka\OpenRpc\DTO\ParameterDescriptor;

class ParameterPipe implements PipeInterface
{
    /**
     * @throws \ReflectionException
     */
    public function handle(MethodContext $context, callable $next): MethodContext
    {
        if ($context->descriptor instanceof ParameterDescriptor) {
            $dockBlockType = null;
            /** @var Property|null $tag */
            foreach ($context->docBlock->getTagsByName('param') as $tag) {
                if ($tag->getVariableName() === $context->descriptor->name) {
                    $context->descriptor->schema->title = $tag->getDescription()->render();
                    $dockBlockType = $tag->getType();
                    break;
                }
            }
            $reflector = $context->getParameterReflector($context->descriptor->name);
            if ($reflector) {
                if ($reflector->isDefaultValueAvailable()) {
                    $context->descriptor->schema->default = $reflector->getDefaultValue();
                }
                $context->descriptor->required = !$reflector->isOptional();
                $context->descriptor->schema = $context->describeType(
                    $reflector->getType(),
                    $context->descriptor->schema,
                    $dockBlockType,
                );
            }
        }
        
        return $next($context);
    }
}
