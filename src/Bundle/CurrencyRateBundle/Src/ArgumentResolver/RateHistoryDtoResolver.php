<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\ArgumentResolver;

use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigInterface;
use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigDefault;
use App\Bundle\CurrencyRateBundle\Src\Container\PaginationContainer;
use App\Bundle\CurrencyRateBundle\Src\Container\RateHistoryContainer;
use Generator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RateHistoryDtoResolver extends AbstractDtoResolver
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        #[Autowire(service: PaginationConfigDefault::class)]
        private readonly PaginationConfigInterface $paginatorConfig,
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): Generator
    {
        if ($argument->getType() !== RateHistoryContainer::class) {
            return;
        }

        $dto = new RateHistoryContainer(
            baseCurrencyCode: $request->attributes->getString('baseCurrencyCode'),
            targetCurrencyCode: $request->attributes->getString('targetCurrencyCode'),
            pagination: new PaginationContainer(
                $request->query->getInt('page', 1),
                $request->query->getInt('perPage', $this->paginatorConfig->getPerPageDefault())
            ),
        );

        $errors = $this->validator->validate($dto);

        $this->processErrors($errors);

        yield $dto;
    }
}
