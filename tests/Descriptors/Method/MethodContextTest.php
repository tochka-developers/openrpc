<?php

namespace Tochka\OpenRpc\Tests\Descriptors\Method;

use Illuminate\Container\Container;
use Illuminate\Pipeline\Pipeline;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tochka\OpenRpc\Tests\Fixtures\Controller;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\Route;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\OpenRpc\Descriptors\Method\MethodContext;
use Tochka\OpenRpc\Descriptors\Method\Pipes\MethodPipe;
use Tochka\OpenRpc\Descriptors\Method\Pipes\ParameterPipe;
use Tochka\OpenRpc\Descriptors\Method\Pipes\ResultPipe;
use Tochka\OpenRpc\Descriptors\Type\Handlers\ObjectHandler;
use Tochka\OpenRpc\Descriptors\Type\TypeDescriptor;
use Tochka\OpenRpc\DTO\ContentDescriptor;
use Tochka\OpenRpc\DTO\ContentDescriptorReference;

#[CoversClass(MethodContext::class)]
final class MethodContextTest extends TestCase
{
    /** Referenced request DTOs flatten with required flags and descriptions intact. */
    public function testReferencedParametersAndResult(): void
    {
        $route = new Route('lead_verify', Controller::class, 'verify');
        $route->addParam(new RouteParam('service', PropType::DI, [], false));
        $route->addParam(new RouteParam('request', PropType::RequestObject, [], false));
        $pipeline = (new Pipeline(new Container()))->through([new MethodPipe(), new ParameterPipe(), new ResultPipe()]);
        $descriptor = new TypeDescriptor(new ObjectHandler());
        $context = new MethodContext($pipeline, $route, $descriptor);
        $context->describeMethod();
        $context->describeParameters();
        $context->describeResult();
        $json = json_decode(json_encode($context->getResult(), JSON_THROW_ON_ERROR), true);
        $params = array_column(json_decode(json_encode($descriptor->getContentDescriptors(), JSON_THROW_ON_ERROR), true), null, 'name');
        self::assertCount(count($params), $json['params']);
        foreach ($json['params'] as $index => $reference) {
            $name = ['phone', 'list', 'first', 'second', 'data'][$index];
            self::assertSame(['$ref' => '#/components/contentDescriptors/Tochka.OpenRpc.Tests.Fixtures.ReferencedRequest.' . $name], $reference);
        }
        self::assertTrue($params['phone']['required']);
        self::assertFalse($params['data']['required']);
        self::assertSame('Phone', $params['phone']['summary']);
        self::assertSame('Phone description.', $params['phone']['description']);
        self::assertArrayNotHasKey('service', $params);
        foreach ($params as $name => $param) {
            self::assertSame(
                ['$ref' => '#/components/schemas/Tochka.OpenRpc.Tests.Fixtures.ReferencedRequest/properties/' . $name],
                $param['schema'],
            );
        }
        self::assertSame('#/components/schemas/Tochka.OpenRpc.Tests.Fixtures.ResponseDto', $json['result']['schema']['allOf'][0]['$ref']);
    }

    /** Inline and nullable request roots both expose their DTO properties. */
    public function testInlineAndNullableRequestObjects(): void
    {
        foreach (['inline', 'nullable'] as $method) {
            $route = new Route('test_' . $method, Controller::class, $method);
            $route->addParam(new RouteParam('request', PropType::RequestObject, [], $method === 'nullable'));
            $pipeline = (new Pipeline(new Container()))->through([new ParameterPipe()]);
            $context = new MethodContext($pipeline, $route, new TypeDescriptor(new ObjectHandler()));
            $context->describeParameters();
            $params = $this->resolveParameters($context->getResult()->params);
            self::assertNotEmpty($params);
            self::assertContains('data', array_column($params, 'name'));
            $json = json_decode(json_encode($params, JSON_THROW_ON_ERROR), true);
            $data = array_column($json, null, 'name')['data']['schema'];
            if ($method === 'nullable') {
                self::assertSame(['$ref' => '#/components/schemas/Tochka.OpenRpc.Tests.Fixtures.ReferencedRequest/properties/data'], $data);
            } else {
                self::assertArrayNotHasKey('$ref', $data);
                self::assertSame('integer', $data['anyOf'][0]['properties']['value']['type']);
            }
        }
    }

    /** Optional applies to flattened parameters and result DTOs in both schema modes. */
    public function testOptionalRequestAndResultProperties(): void
    {
        foreach (['optional', 'optionalReference'] as $method) {
            $route = new Route('test_' . $method, Controller::class, $method);
            $route->addParam(new RouteParam('request', PropType::RequestObject, [], false));
            $pipeline = (new Pipeline(new Container()))->through([new ParameterPipe(), new ResultPipe()]);
            $descriptor = new TypeDescriptor(new ObjectHandler());
            $context = new MethodContext($pipeline, $route, $descriptor);
            $context->describeParameters();
            $context->describeResult();
            $params = array_column($this->resolveParameters($context->getResult()->params), null, 'name');
            foreach (['note', 'count', 'nested', 'shared', 'enabled', 'values', 'union', 'label'] as $name) {
                self::assertFalse($params[$name]->required);
            }
            self::assertTrue($params['requiredNullable']->required);
            self::assertTrue($params['requiredCount']->required);
            $result = $descriptor->resolveSchema($context->getResult()->result->schema);
            self::assertSame(['requiredNullable', 'requiredCount'], $result->required);
            self::assertSame(['string'], $result->properties['note']->type);
            self::assertArrayNotHasKey('default', get_object_vars($result->properties['note']));
            foreach (['mixed', 'untyped', 'documentedMixed', 'mixedDefault', 'promotedMixed'] as $name) {
                self::assertFalse($params[$name]->required);
                self::assertSame('{}', json_encode($result->properties[$name], JSON_THROW_ON_ERROR));
                if ($method === 'optionalReference') {
                    self::assertSame('#/components/schemas/Tochka.OpenRpc.Tests.Fixtures.ReferencedOptionalDto/properties/' . $name, $params[$name]->schema->ref);
                } else {
                    self::assertSame('{}', json_encode($params[$name]->schema, JSON_THROW_ON_ERROR));
                }
            }
            if ($method === 'optionalReference') {
                self::assertSame('#/components/schemas/Tochka.OpenRpc.Tests.Fixtures.ReferencedOptionalDto/properties/note', $params['note']->schema->ref);
            } else {
                self::assertSame(['string'], $params['note']->schema->type);
            }
        }
    }
    /** Missing descriptions stay absent for inline and referenced request properties. */
    public function testMissingDescriptionsAreOmitted(): void
    {
        foreach (['optional', 'optionalReference'] as $method) {
            $route = new Route('test_' . $method, Controller::class, $method);
            $route->addParam(new RouteParam('request', PropType::RequestObject, [], false));
            $descriptor = new TypeDescriptor(new ObjectHandler());
            $context = new MethodContext((new Pipeline(new Container()))->through([new ParameterPipe()]), $route, $descriptor);
            $context->describeParameters();
            $params = array_column(json_decode(json_encode($this->resolveParameters($context->getResult()->params), JSON_THROW_ON_ERROR), true), null, 'name');
            self::assertArrayNotHasKey('description', $params['note']);
            self::assertArrayNotHasKey('description', $params['enabled']);
            self::assertSame('Enabled', $params['enabled']['summary']);
            self::assertSame('Optional shared description.', $params['shared']['description']);
        }
    }

    /** Request parameters are stably grouped by requiredness, including inherited properties. */
    public function testRequiredParametersPrecedeOptionalParameters(): void
    {
        foreach (['verify', 'optional', 'optionalReference'] as $method) {
            $route = new Route('test_' . $method, Controller::class, $method);
            $route->addParam(new RouteParam('request', PropType::RequestObject, [], false));
            $descriptor = new TypeDescriptor(new ObjectHandler());
            $context = new MethodContext((new Pipeline(new Container()))->through([new ParameterPipe()]), $route, $descriptor);
            $context->describeParameters();
            $params = $this->resolveParameters($context->getResult()->params);
            $schema = $descriptor->resolveSchema($context->descriptor->schema);
            $required = array_values(array_filter(array_keys($schema->properties), static fn (string $name): bool => in_array($name, $schema->required, true)));
            $optional = array_values(array_diff(array_keys($schema->properties), $required));
            self::assertSame([...$required, ...$optional], array_column($params, 'name'));
            self::assertSame('by-name', $context->getResult()->paramStructure);
        }
    }

    /** Ordinary parameters retain their declared order and existing parameter structure. */
    public function testOrdinaryParameterOrderIsPreserved(): void
    {
        $route = new Route('ordinary', Controller::class, 'ordinary');
        $route->addParam(new RouteParam('second', PropType::Primitive, [], false));
        $route->addParam(new RouteParam('first', PropType::Primitive, [], false));
        $context = new MethodContext(
            (new Pipeline(new Container()))->through([new ParameterPipe()]),
            $route,
            new TypeDescriptor(new ObjectHandler()),
        );
        $context->describeParameters();
        self::assertSame(['second', 'first'], array_column($context->getResult()->params, 'name'));
        self::assertSame('either', $context->getResult()->paramStructure);
    }

    /** Read metadata through references as consumers would after resolving components.
     * @param array<ContentDescriptor|ContentDescriptorReference> $parameters
     * @return array<ContentDescriptor>
     */
    private function resolveParameters(array $parameters): array
    {
        return array_map(
            static fn (ContentDescriptor|ContentDescriptorReference $parameter): ContentDescriptor => $parameter instanceof ContentDescriptorReference
                ? $parameter->getContentDescriptor()
                : $parameter,
            $parameters,
        );
    }
}
