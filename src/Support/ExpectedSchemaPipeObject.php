<?php

namespace Tochka\OpenRpc\Support;

use Tochka\JsonRpc\Route\Parameters\Parameter;
use Tochka\OpenRpc\Contracts\SchemaReferenceInterface;

class ExpectedSchemaPipeObject
{
    public SchemaReferenceInterface $schema;
    /** @var AliasedDictionary<SchemaReferenceInterface> */
    public AliasedDictionary $schemasDictionary;
    public Parameter $parameter;
    public Context $context;
    public bool $isResult = false;
    
    public function __construct(
        SchemaReferenceInterface $schema,
        AliasedDictionary $schemas,
        Parameter $parameter,
        Context $context,
        bool $isResult = false
    ) {
        $this->schema = $schema;
        $this->schemasDictionary = $schemas;
        $this->parameter = $parameter;
        $this->context = $context;
        $this->isResult = $isResult;
    }
}
