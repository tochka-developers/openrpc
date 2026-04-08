<?php

namespace Tochka\OpenRpc\Descriptors\Method\Pipes;

use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use Tochka\OpenRpc\Descriptors\Method\MethodContext;
use Tochka\OpenRpc\DTO\ResultDescriptor;

class ResultPipe implements PipeInterface
{
    public function handle(MethodContext $context, callable $next): MethodContext
    {
        if ($context->descriptor instanceof ResultDescriptor) {
            $docBlock = $context->docBlock;
            /** @var Return_|null $tag */
            $tag = $docBlock->getTagsByName('return')[0] ?? null;
            $description = $tag?->getDescription()->render();
            if ($description) {
                $context->descriptor->description = $description;
            }
            $context->descriptor->schema->default = null;
            //$context->descriptor->summary = 'dfdfdfdfdf';
            $context->descriptor->schema = $context->describeType(
                $context->reflectionMethod->getReturnType(),
                $context->descriptor->schema,
                $tag?->getType(),
            );
        }
        
        return $next($context);
    }
}
