<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\ArgumentResolver;

use App\Bundle\CurrencyRateBundle\Src\ArgumentResolver\RateHistoryContainerResolver;
use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigDefault;
use App\Bundle\CurrencyRateBundle\Src\Container\RateHistoryContainer;
use Generator;
use stdClass;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validation;

class RateHistoryContainerResolverTest extends AbstractContainerResolverTestCase
{
    protected function getResolver(): ValueResolverInterface
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
        return new RateHistoryContainerResolver($validator, new PaginationConfigDefault());
    }

    /**
     * @inheritDoc
     */
    public static function paramsProvider(): Generator
    {
        yield 'testResolveYieldsRateHistoryContainer' => [
            'params' => [
                'page' => [
                    'type' => 'query',
                    'value' => 2,
                    'expected' => [
                        'path' => 'pagination.page',
                        'value' => 2,
                    ],
                ],
                'perPage' => [
                    'type' => 'query',
                    'value' => 25,
                    'expected' => [
                        'path' => 'pagination.perPage',
                        'value' => 25,
                    ],
                ],
                'baseCurrencyCode' => [
                    'type' => 'attr',
                    'value' => self::getRub()->getCode(),
                    'expected' => [
                        'path' => 'baseCurrencyCode',
                        'value' => self::getRub()->getCode(),
                    ],
                ],
                'targetCurrencyCode' => [
                    'type' => 'attr',
                    'value' => self::getUsd()->getCode(),
                    'expected' => [
                        'path' => 'targetCurrencyCode',
                        'value' => self::getUsd()->getCode(),
                    ],
                ],
            ],
            'metadataClass' => RateHistoryContainer::class,
            'exception' => null,
            'resultCount' => 1,
        ];

        yield 'testResolveUsesDefaultPagination' => [
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
                    'value' => self::getRub(),
                    'expected' => [
                        'path' => 'baseCurrencyCode',
                        'value' => self::getRub()->getCode(),
                    ],
                ],
                'targetCurrencyCode' => [
                    'type' => 'attr',
                    'value' => self::getUsd()->getCode(),
                    'expected' => [
                        'path' => 'targetCurrencyCode',
                        'value' => self::getUsd()->getCode(),
                    ],
                ],
            ],
            'metadataClass' => RateHistoryContainer::class,
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
            'metadataClass' => RateHistoryContainer::class,
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
            'metadataClass' => RateHistoryContainer::class,
            'exception' => BadRequestException::class,
        ];

        yield 'testResolveYieldsNothingForWrongType' => [
            'params' => null,
            'metadataClass' => stdClass::class,
            'exception' => null,
            'resultCount' => 0,
        ];

        yield 'testResolveThrowsBadRequestWhenBaseCurrencyInvalid' => [
            'params' => [
                'baseCurrencyCode' => [
                    'type' => 'attr',
                    'value' => 'XX',
                    'expected' => [],
                ],
                'targetCurrencyCode' => [
                    'type' => 'attr',
                    'value' => self::getUsd()->getCode(),
                    'expected' => [],
                ],
            ],
            'metadataClass' => RateHistoryContainer::class,
            'exception' => BadRequestHttpException::class,
        ];

        yield 'testResolveThrowsBadRequestWhenTargetCurrencyInvalid' => [
            'params' => [
                'baseCurrencyCode' => [
                    'type' => 'attr',
                    'value' => self::getRub()->getCode(),
                    'expected' => [],
                ],
                'targetCurrencyCode' => [
                    'type' => 'attr',
                    'value' => 'XX',
                    'expected' => [],
                ],
            ],
            'metadataClass' => RateHistoryContainer::class,
            'exception' => BadRequestHttpException::class,
        ];

        yield 'testResolveThrowsBadRequestWhenTargetCurrencyMissing' => [
            'params' => [
                'baseCurrencyCode' => [
                    'type' => 'attr',
                    'value' => self::getRub(),
                    'expected' => [],
                ],
                'targetCurrencyCode' => [
                    'type' => 'attr',
                    'value' => '',
                    'expected' => [],
                ],
            ],
            'metadataClass' => RateHistoryContainer::class,
            'exception' => BadRequestHttpException::class,
        ];

        yield 'testResolveThrowsBadRequestWhenBothCurrenciesEmpty' => [
            'params' => [
                'baseCurrencyCode' => [
                    'type' => 'attr',
                    'value' => '',
                    'expected' => [],
                ],
                'targetCurrencyCode' => [
                    'type' => 'attr',
                    'value' => '',
                    'expected' => [],
                ],
            ],
            'metadataClass' => RateHistoryContainer::class,
            'exception' => BadRequestHttpException::class,
        ];
    }
}
