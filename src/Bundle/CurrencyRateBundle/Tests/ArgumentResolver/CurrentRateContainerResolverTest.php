<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\ArgumentResolver;

use App\Bundle\CurrencyRateBundle\Src\ArgumentResolver\CurrentRateContainerResolver;
use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigDefault;
use App\Bundle\CurrencyRateBundle\Src\Container\CurrentRateContainer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validation;

use function iterator_to_array;

class CurrentRateContainerResolverTest extends TestCase
{
    private CurrentRateContainerResolver $resolver;

    protected function setUp(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
        $this->resolver = new CurrentRateContainerResolver($validator, new PaginationConfigDefault());
    }

    public function testResolveYieldsCurrentRateContainer(): void
    {
        $request = new Request(query: ['page' => 1, 'perPage' => 10]);
        $request->attributes->set('baseCurrencyCode', 'RUB');
        $metadata = new ArgumentMetadata('container', CurrentRateContainer::class, false, false, null);
        $results = iterator_to_array($this->resolver->resolve($request, $metadata));
        $this->assertCount(1, $results);
        $this->assertInstanceOf(CurrentRateContainer::class, $results[0]);
        $this->assertSame('RUB', $results[0]->baseCurrencyCode);
        $this->assertSame(1, $results[0]->pagination->page);
        $this->assertSame(10, $results[0]->pagination->perPage);
    }

    public function testResolveUsesDefaultPageAndPerPage(): void
    {
        $request = new Request();
        $request->attributes->set('baseCurrencyCode', 'USD');
        $metadata = new ArgumentMetadata('container', CurrentRateContainer::class, false, false, null);
        $results = iterator_to_array($this->resolver->resolve($request, $metadata));
        $this->assertSame(1, $results[0]->pagination->page);
        $this->assertSame(10, $results[0]->pagination->perPage); // PaginationConfigDefault default
    }

    public function testResolveYieldsNothingForWrongType(): void
    {
        $request = new Request();
        $metadata = new ArgumentMetadata('container', 'stdClass', false, false, null);
        $results = iterator_to_array($this->resolver->resolve($request, $metadata));
        $this->assertEmpty($results);
    }

    public function testResolveThrowsBadRequestForInvalidCurrencyCode(): void
    {
        $request = new Request(query: ['page' => 1, 'perPage' => 10]);
        $request->attributes->set('baseCurrencyCode', 'XX'); // 2 chars, not valid ISO 4217
        $metadata = new ArgumentMetadata('container', CurrentRateContainer::class, false, false, null);
        $this->expectException(BadRequestHttpException::class);
        iterator_to_array($this->resolver->resolve($request, $metadata));
    }

    public function testResolveThrowsBadRequestForEmptyCurrencyCode(): void
    {
        $request = new Request(query: ['page' => 1, 'perPage' => 10]);
        $request->attributes->set('baseCurrencyCode', '');
        $metadata = new ArgumentMetadata('container', CurrentRateContainer::class, false, false, null);
        $this->expectException(BadRequestHttpException::class);
        iterator_to_array($this->resolver->resolve($request, $metadata));
    }
}
