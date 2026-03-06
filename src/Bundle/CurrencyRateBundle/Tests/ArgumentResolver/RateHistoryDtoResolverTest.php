<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\ArgumentResolver;

use App\Bundle\CurrencyRateBundle\Src\ArgumentResolver\RateHistoryDtoResolver;
use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigDefault;
use App\Bundle\CurrencyRateBundle\Src\Container\RateHistoryContainer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validation;

class RateHistoryDtoResolverTest extends TestCase
{
    private RateHistoryDtoResolver $resolver;
    protected function setUp(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
        $this->resolver = new RateHistoryDtoResolver($validator, new PaginationConfigDefault());
    }
    public function testResolveYieldsRateHistoryContainer(): void
    {
        $request = new Request(query: ['page' => 2, 'perPage' => 25]);
        $request->attributes->set('baseCurrencyCode', 'RUB');
        $request->attributes->set('targetCurrencyCode', 'USD');
        $metadata = new ArgumentMetadata('dto', RateHistoryContainer::class, false, false, null);
        $results = iterator_to_array($this->resolver->resolve($request, $metadata));
        $this->assertCount(1, $results);
        $this->assertInstanceOf(RateHistoryContainer::class, $results[0]);
        $this->assertSame('RUB', $results[0]->baseCurrencyCode);
        $this->assertSame('USD', $results[0]->targetCurrencyCode);
        $this->assertSame(2, $results[0]->pagination->page);
        $this->assertSame(25, $results[0]->pagination->perPage);
    }
    public function testResolveYieldsNothingForWrongType(): void
    {
        $request = new Request();
        $metadata = new ArgumentMetadata('dto', 'stdClass', false, false, null);
        $results = iterator_to_array($this->resolver->resolve($request, $metadata));
        $this->assertEmpty($results);
    }
    public function testResolveThrowsBadRequestWhenTargetCurrencyMissing(): void
    {
        $request = new Request(query: ['page' => 1, 'perPage' => 10]);
        $request->attributes->set('baseCurrencyCode', 'RUB');
        $request->attributes->set('targetCurrencyCode', '');
        $metadata = new ArgumentMetadata('dto', RateHistoryContainer::class, false, false, null);
        $this->expectException(BadRequestHttpException::class);
        iterator_to_array($this->resolver->resolve($request, $metadata));
    }
    public function testResolveThrowsBadRequestWhenBaseCurrencyInvalid(): void
    {
        $request = new Request(query: ['page' => 1, 'perPage' => 10]);
        $request->attributes->set('baseCurrencyCode', 'XXXXX'); // too long
        $request->attributes->set('targetCurrencyCode', 'USD');
        $metadata = new ArgumentMetadata('dto', RateHistoryContainer::class, false, false, null);
        $this->expectException(BadRequestHttpException::class);
        iterator_to_array($this->resolver->resolve($request, $metadata));
    }
    public function testResolveThrowsBadRequestWhenBothCurrenciesEmpty(): void
    {
        $request = new Request(query: ['page' => 1, 'perPage' => 10]);
        $request->attributes->set('baseCurrencyCode', '');
        $request->attributes->set('targetCurrencyCode', '');
        $metadata = new ArgumentMetadata('dto', RateHistoryContainer::class, false, false, null);
        $this->expectException(BadRequestHttpException::class);
        iterator_to_array($this->resolver->resolve($request, $metadata));
    }
    public function testResolveUsesDefaultPagination(): void
    {
        $request = new Request();
        $request->attributes->set('baseCurrencyCode', 'RUB');
        $request->attributes->set('targetCurrencyCode', 'EUR');
        $metadata = new ArgumentMetadata('dto', RateHistoryContainer::class, false, false, null);
        $results = iterator_to_array($this->resolver->resolve($request, $metadata));
        $this->assertSame(1, $results[0]->pagination->page);
        $this->assertSame(10, $results[0]->pagination->perPage);
    }
}
