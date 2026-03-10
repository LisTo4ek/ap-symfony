<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\ArgumentResolver;

use App\Bundle\CurrencyRateBundle\Src\ArgumentResolver\CurrentRateContainerResolver;
use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigDefault;
use App\Bundle\CurrencyRateBundle\Src\Container\CurrentRateContainer;
use Generator;
use stdClass;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validation;

class CurrentRateContainerResolverTest extends AbstractContainerResolverTest
{
    protected function getResolver(): ValueResolverInterface
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
        return new CurrentRateContainerResolver($validator, new PaginationConfigDefault());
    }

    /**
     * @inheritDoc
     */
    public static function paramsProvider(): Generator
    {
        yield 'testResolveYieldsCurrentRateContainer' => [
            'params' => [
                'page' => [
                    'type' => 'query',
                    'value' => 1,
                    'expected' => [
                        'path' => 'pagination.page',
                        'value' => 1,
                    ],
                ],
                'perPage' => [
                    'type' => 'query',
                    'value' => 10,
                    'expected' => [
                        'path' => 'pagination.perPage',
                        'value' => 10,
                    ],
                ],
                'baseCurrencyCode' => [
                    'type' => 'attr',
                    'value' => 'USD',
                    'expected' => [
                        'path' => 'baseCurrencyCode',
                        'value' => 'USD',
                    ],
                ],
            ],
            'metadataClass' => CurrentRateContainer::class,
            'exception' => null,
            'resultCount' => 1,
        ];

        yield 'testResolveUsesDefaultPageAndPerPage' => [
            'params' => [
                'page' => [
                    'type' => 'query',
                    'value' => null,
                    'expected' => [
                        'path' => 'pagination.page',
                        'value' => 1,
                    ],
                ],
                'perPage' => [
                    'type' => 'query',
                    'value' => null,
                    'expected' => [
                        'path' => 'pagination.perPage',
                        'value' => 10,
                    ],
                ],
                'baseCurrencyCode' => [
                    'type' => 'attr',
                    'value' => 'USD',
                    'expected' => [
                        'path' => 'baseCurrencyCode',
                        'value' => 'USD',
                    ],
                ],
            ],
            'metadataClass' => CurrentRateContainer::class,
            'exception' => null,
            'resultCount' => 1,
        ];

        yield 'testResolveInvalidPage' => [
            'params' => [
                'page' => [
                    'type' => 'query',
                    'value' => 'INVALID',
                    'expected' => [
                        'path' => 'pagination.page',
                        'value' => 1,
                    ],
                ],
            ],
            'metadataClass' => CurrentRateContainer::class,
            'exception' => BadRequestException::class,
        ];

        yield 'testResolveEmptyPage' => [
            'params' => [
                'page' => [
                    'type' => 'query',
                    'value' => '',
                    'expected' => [
                        'path' => 'pagination.page',
                        'value' => 1,
                    ],
                ],
            ],
            'metadataClass' => CurrentRateContainer::class,
            'exception' => BadRequestException::class,
        ];

        yield 'testResolveYieldsNothingForWrongType' => [
            'params' => null,
            'metadataClass' => stdClass::class,
            'exception' => null,
            'resultCount' => 0,
        ];

        yield 'testResolveThrowsBadRequestForInvalidCurrencyCode' => [
            'params' => [
                'baseCurrencyCode' => [
                    'type' => 'attr',
                    'value' => 'XX',
                    'expected' => [],
                ],
            ],
            'metadataClass' => CurrentRateContainer::class,
            'exception' => BadRequestHttpException::class,
        ];

        yield 'testResolveThrowsBadRequestForEmptyCurrencyCode' => [
            'params' => [
                'baseCurrencyCode' => [
                    'type' => 'attr',
                    'value' => '',
                    'expected' => [],
                ],
            ],
            'metadataClass' => CurrentRateContainer::class,
            'exception' => BadRequestHttpException::class,
        ];
    }
}
