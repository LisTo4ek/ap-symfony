<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\ArgumentResolver;

use App\Bundle\CurrencyRateBundle\Src\Container\PaginationContainer;
use App\Bundle\CurrencyRateBundle\Src\Container\RateHistoryContainer;
use Generator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Resolves HTTP request data into a validated RateHistoryContainer for rate-history controller actions.
 *
 * Extracts pagination query parameters and currency pair codes from route attributes,
 * validates the assembled DTO, and yields it for controller injection.
 */
class RateHistoryContainerResolver extends AbstractContainerResolver
{
    /**
     * @inheritDoc
     * @return Generator<int, RateHistoryContainer> Yields the validated CurrentRateContainer
     * @throws BadRequestHttpException When validation fails
     */
    public function resolve(Request $request, ArgumentMetadata $argument): Generator
    {
        if ($argument->getType() !== RateHistoryContainer::class) {
            return;
        }

        $dto = new RateHistoryContainer(
            pagination: new PaginationContainer(
                $request->query->getInt('page', 1),
                $request->query->getInt('perPage', $this->paginatorConfig->getPerPageDefault())
            ),
            baseCurrencyCode: $request->attributes->getString('baseCurrencyCode'),
            targetCurrencyCode: $request->attributes->getString('targetCurrencyCode'),
        );

        $errors = $this->validator->validate($dto);

        $this->processErrors($errors);

        yield $dto;
    }
}
