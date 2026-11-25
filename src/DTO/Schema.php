<?php

namespace Tochka\OpenRpc\DTO;

final class Schema implements \JsonSerializable
{
    // schema description
    public ?string $schema;
    public ?string $ref;
    public ?string $comment;
    public ?string $title;
    public ?string $summary;
    public ?string $description;
    public mixed $default;
    /** @var array<string> */
    public array $required = [];
    /** @var array<string, self> */
    public ?array $properties;
    public array $enum;
    /** @var string[] */
    public array $type = ['mixed'];
    /** @var array<self> */
    public array $anyOf;
    public string $format;
    public self $items;
    /** @var array<mixed> */
    public array $examples;
    public array $oneOf;
    /** @var array<self> */
    public array $allOf;
    
    public function jsonSerialize(): object
    {
        $result = (array)$this;
        if (isset($this->ref)) {
            $result['$ref'] = $this->ref;
            unset($result['ref']);
        }
        $result['type'] = array_values(array_unique($result['type']));
        $result['type'] = self::convertType($result['type']);
        // если больше одно элемента, и там есть mixed, надо его удалить
        if (count($result['type']) > 1) {
            $key = array_search('mixed', $result['type']);
            if ($key !== false) {
                unset($result['type'][$key]);
                $result['type'] = array_values($result['type']);
            }
        }
        
        if (count($result['type']) === 1) {
            $result['type'] = $result['type'][0];
        }
        
        if ($result['type'] === 'void') {
            $result['type'] = 'null';
        }
        
        if ($result['type'] === 'mixed') {
            unset($result['type']);
        }
        
        if (empty($result['required'])) {
            unset($result['required']);
        }
        
        if (isset($result['properties'])) {
            $result['properties'] = (object)$result['properties'];
        }
        if (isset($result['$ref']) && count($result) > 1) {
            $reference = ['$ref' => $result['$ref']];
            unset($result['$ref']);
            $result['allOf'] = [$reference, ...($result['allOf'] ?? [])];
        }

        return (object)$result;
    }
    
    protected static function convertType(array $type): array
    {
        $result = [];
        foreach ($type as $value) {
            $result[] = match ($value) {
                'int' => 'integer',
                'bool' => 'boolean',
                'float' => 'number',
                // object/array/string/mixed/null/unknown
                default => $value,
            };
        }
        
        return $result;
    }
}
