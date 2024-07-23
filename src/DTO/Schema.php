<?php

namespace Tochka\OpenRpc\DTO;

use Tochka\OpenRpc\Contracts\SchemaReferenceInterface;
use Tochka\OpenRpc\Support\DataTransferObject;

final class Schema extends DataTransferObject implements SchemaReferenceInterface
{
    protected array $nullableKeys = [
        'default',
        'const'
    ];
    
    protected array $onlyNotEmptyKeys = [
        'title',
        'description',
        'required',
        'properties',
    ];
    
    public ?string $id;
    public ?string $schema;
    public ?string $ref;
    public ?string $comment;
    public ?string $title;
    public ?string $description;
    /** @var mixed */
    public $default;
    public ?bool $readOnly;
    /** @var array */
    public array $examples;
    public int $multipleOf;
    public int $maximum;
    public int $exclusiveMaximum;
    public int $minimum;
    public int $exclusiveMinimum;
    public int $maxLength;
    public int $minLength;
    public string $pattern;
    public self $additionalItems;
    public SchemaReferenceInterface $items;
    public int $maxItems;
    public int $minItems;
    public bool $uniqueItems;
    public self $contains;
    public int $maxProperties;
    public int $minProperties;
    /** @var array<string> */
    public array $required = [];
    public self $additionalProperties;
    /** @var array<string, mixed> */
    public array $definitions;
    /** @var array<string, self> */
    public array $properties = [];
    /** @var array<string, mixed> */
    public array $patternProperties;
    /** @var array<string, mixed> */
    public array $dependencies;
    public self $propertyNames;
    /** @var mixed */
    public $const;
    /** @var array */
    public array $enum;
    /** @var array|string */
    public $type;
    public string $format;
    public string $contentMediaType;
    public string $contentEncoding;
    public self $if;
    public self $then;
    public self $else;
    /** @var array<self> */
    public array $allOf;
    /** @var array<self> */
    public array $anyOf;
    /** @var array<self> */
    public array $oneOf;
    public self $not;

    public function __construct()
    {
        /**
         * Чтобы при сериализации в массив не выводились эти поля, если не были явно установлены
         * Так как проверка на вывод поле идет с помощью ReflectionProperty::isInitialized, который
         * возвращает false для неинициализированных свойств, для которых явно задан тип, и для полей, к которым
         * применили unset
         */
        unset($this->default, $this->const, $this->type);
    }
    
    public function getSchema(): Schema
    {
        return $this;
    }
}
