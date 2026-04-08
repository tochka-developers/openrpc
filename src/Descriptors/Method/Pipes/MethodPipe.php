<?php

namespace Tochka\OpenRpc\Descriptors\Method\Pipes;

use Tochka\OpenRpc\Descriptors\Method\MethodContext;
use Tochka\OpenRpc\DTO\MethodDescriptor;
use Tochka\OpenRpc\DTO\Tag;

class MethodPipe implements PipeInterface
{
    public function handle(MethodContext $context, callable $next): MethodContext
    {
        if ($context->descriptor instanceof MethodDescriptor) {
            $context->descriptor->summary = $context->docBlock->getSummary();
            $context->descriptor->description = $context->docBlock->getDescription();
            $context->descriptor->deprecated = $context->docBlock->hasTag('deprecated');
            $group = explode('_', $context->route->name)[0] ?? null;
            if ($group) {
                $context->descriptor->tags[] = new Tag($group);
            }
        }
        
        return $next($context);
    }
}
