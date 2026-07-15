<?php

namespace Tochka\OpenRpc\Descriptors\Type\Handlers;

use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlockFactory;
use Tochka\OpenRpc\Descriptors\Type\SingularTypeInfo;
use Tochka\OpenRpc\Descriptors\Type\TypeDescriptor;
use Tochka\OpenRpc\DTO\Schema;

class EnumHandler implements HandlerInterface
{
    public function shouldHandle(SingularTypeInfo $info): bool
    {
        return $info->className && enum_exists($info->className);
    }
    
    /**
     * @throws \ReflectionException
     */
    public function handle(SingularTypeInfo $info, Schema $schema, TypeDescriptor $typeDescriptor): Schema
    {
        /** @var \BackedEnum $className */
        $className = $info->className;
        $schema->enum = $className::cases();
        $ref = new \ReflectionEnum($className);
        $cases = $ref->getCases();
        $docFactory = DocBlockFactory::createInstance();
        foreach ($cases as $case) {
            $docText = $case->getDocComment();
            $descriptionText = '';
            if ($docText) {
                $docBlock = $docFactory->create($docText);
                /** @var Var_ $tag */
                $tag = $docBlock->getTagsByName('var')[0] ?? null;
                if ($tag) {
                    $descriptionText = $tag->getDescription()?->render() ?? '';
                }
            }
            // если не удалось достать из тега, возможно там просто текст
            if (!$descriptionText && $docText) {
                $descriptionText = $docBlock->getSummary();
            }
            $schema->oneOf[] = [
                'title' => $case->getName(),
                'const' => $case->getBackingValue(),
                'description' => $descriptionText,
            ];
        }
        
        $valueType = $ref->getBackingType();
        if ($valueType === null) {
            $schema->type = ['string', 'integer'];
        } else {
            $schema->type = [$valueType->getName()];
        }
        
        return $schema;
    }
}
