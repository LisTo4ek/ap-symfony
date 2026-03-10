<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\ArgumentResolver;

use App\Bundle\CurrencyRateBundle\Tests\Trait\CurrencyTrait;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use function iterator_to_array;

abstract class AbstractContainerResolverTestCase extends TestCase
{
    use CurrencyTrait;

    protected ValueResolverInterface $resolver;
    protected PropertyAccessorInterface $propertyAccessor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = $this->getResolver();
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    abstract protected function getResolver(): ValueResolverInterface;

    /**
     * @return Generator<string, array{
     *     params: null|array<string, array{type: string, value: mixed, expected: array{path: string, value: mixed}}>,
     *     attributeParams: null|array<string, mixed>,
     *     metadataClass: null|class-string,
     *     expected: null|array<string, mixed>,
     *     exception: null|class-string
     * }
     */
    abstract public static function paramsProvider(): Generator;

    /**
     * @param null|array<string, array{type: string, value: mixed, expected: array{path: string, value: mixed}}> $params
     * @param null|class-string $metadataClass
     * @param null|class-string $exception
     * @param null|int $resultCount
     */
    #[DataProvider('paramsProvider')]
    public function testParams(
        ?array $params,
        ?string $metadataClass,
        ?string $exception,
        ?int $resultCount = null,
    ): void {

        $params = $params ?? [];
        $request = new Request($actualQuery ?? []);

        foreach ($params as $name => $config) {
            if ($config['value'] === null) {
                continue;
            }

            if ($config['type'] === 'query') {
                $request->query->set($name, $config['value']);
            } elseif ($config['type'] === 'attr') {
                $request->attributes->set($name, $config['value']);
            }
        }

        $metadata = new ArgumentMetadata('container', $metadataClass, false, false, null);
        if ($exception) {
            $this->expectException($exception);
        }

        $results = iterator_to_array($this->resolver->resolve($request, $metadata));

        if (empty($params)) {
            $this->assertEmpty($results);
            return;
        }

        $this->assertInstanceOf($metadataClass, $results[0]);

        if ($resultCount !== null) {
            $this->assertCount($resultCount, $results);
        }

        foreach ($params as $config) {
            $this->assertSame(
                $config['expected']['value'],
                $this->propertyAccessor->getValue($results[0], $config['expected']['path'])
            );
        }
    }
}

