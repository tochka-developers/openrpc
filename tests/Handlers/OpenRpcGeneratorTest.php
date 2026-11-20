<?php

namespace Tochka\OpenRpc\Tests\Handlers;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tochka\OpenRpc\Tests\Fixtures\Controller;
use Tochka\JsonRpc\Router\PropType;
use Tochka\JsonRpc\Router\Route;
use Tochka\JsonRpc\Router\RouteParam;
use Tochka\JsonRpc\Router\Router;
use Tochka\OpenRpc\Descriptors\Method\Pipes\MethodPipe;
use Tochka\OpenRpc\Descriptors\Method\Pipes\ParameterPipe;
use Tochka\OpenRpc\Descriptors\Method\Pipes\ResultPipe;
use Tochka\OpenRpc\Descriptors\Type\Handlers\ObjectHandler;
use Tochka\OpenRpc\DTO\Info;
use Tochka\OpenRpc\DTO\Server;
use Tochka\OpenRpc\Handlers\OpenRpcGenerator;
use Tochka\OpenRpc\Support\OpenRpcConfig;

#[CoversClass(OpenRpcGenerator::class)]
final class OpenRpcGeneratorTest extends TestCase
{
    /** Documents contain resolvable references and never retain prior components. */
    public function testDocumentReferencesAndIsolation(): void
    {
        $container = new Container();
        $container->instance('app', $container);
        Facade::setFacadeApplication($container);
        try {
            $route = new Route('lead_verify', Controller::class, 'verify');
            $route->addParam(new RouteParam('request', PropType::RequestObject, [], false));
            $secondRoute = new Route('lead_verify_again', Controller::class, 'verify');
            $secondRoute->addParam(new RouteParam('request', PropType::RequestObject, [], false));
            $router = $this->createMock(Router::class);
            $router->method('getAll')->willReturnOnConsecutiveCalls([$route, $secondRoute], []);
            $config = new OpenRpcConfig(
                [MethodPipe::class, ParameterPipe::class, ResultPipe::class],
                [ObjectHandler::class],
                new Server('test', 'http://localhost'),
                new Info('test', '1'),
                null,
                'test',
            );
            $generator = new OpenRpcGenerator($config, $router);
            $document = json_decode(json_encode($generator->handle(), JSON_THROW_ON_ERROR), true);
            self::assertCount(4, $document['components']['schemas']);
            self::assertSame($document['methods'][0]['params'], $document['methods'][1]['params']);
            self::assertCount(5, $document['components']['contentDescriptors']);
            self::assertSame('by-name', $document['methods'][0]['paramStructure']);
            $parameterNames = [];
            foreach ($document['methods'][0]['params'] as $param) {
                self::assertSame(['$ref'], array_keys($param));
                $key = substr($param['$ref'], strlen('#/components/contentDescriptors/'));
                self::assertArrayHasKey($key, $document['components']['contentDescriptors']);
                $param = $document['components']['contentDescriptors'][$key];
                $parameterNames[] = $param['name'];
                if (in_array($param['name'], ['list', 'first', 'second'], true)) {
                    self::assertArrayNotHasKey('description', $param);
                }
                self::assertSame(
                    ['$ref' => '#/components/schemas/Tochka.OpenRpc.Tests.Fixtures.ReferencedRequest/properties/' . $param['name']],
                    $param['schema'],
                );
            }
            self::assertSame(['phone', 'list', 'first', 'second', 'data'], $parameterNames);
            $properties = $document['components']['schemas']['Tochka.OpenRpc.Tests.Fixtures.ReferencedRequest']['properties'];
            self::assertSame(['first'], $properties['first']['examples']);
            self::assertNull($properties['data']['default']);
            self::assertSame('null', $properties['data']['anyOf'][1]['type']);
            $this->assertReferencesResolve($document, $document);
            self::assertArrayNotHasKey('components', $generator->handle());
        } finally {
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication(null);
        }
    }

    /** Walk references without expanding recursive definitions. */
    private function assertReferencesResolve(array $node, array $document): void
    {
        if (isset($node['$ref'])) {
            $target = $document;
            foreach (explode('/', substr($node['$ref'], 2)) as $segment) {
                $segment = str_replace(['~1', '~0'], ['/', '~'], $segment);
                self::assertArrayHasKey($segment, $target);
                $target = $target[$segment];
            }
        }
        foreach ($node as $value) {
            if (is_array($value)) {
                $this->assertReferencesResolve($value, $document);
            }
        }
    }
}
