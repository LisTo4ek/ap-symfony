<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\ArgumentResolver;

use App\Bundle\CurrencyRateBundle\Src\Container\CurrentRateContainer;
use App\Bundle\CurrencyRateBundle\Src\Container\PaginationContainer;
use Generator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Resolves HTTP request data into a validated CurrentRateContainer for current-rate controller actions.
 *
 * Extracts pagination query parameters and the base currency code from route attributes,
 * validates the assembled DTO, and yields it for controller injection.
 */
class CurrentRateContainerResolver extends AbstractContainerResolver
{
    /**
     * @inheritDoc
     * @return Generator<int, CurrentRateContainer> Yields the validated CurrentRateContainer
     * @throws BadRequestHttpException When validation fails
     */
    public function resolve(Request $request, ArgumentMetadata $argument): Generator
    {
        if ($argument->getType() !== CurrentRateContainer::class) {
            return;
        }

        $dto = new CurrentRateContainer(
            pagination: new PaginationContainer(
                $request->query->getInt('page', 1),
                $request->query->getInt('perPage', $this->paginatorConfig->getPerPageDefault())
            ),
            baseCurrencyCode: $request->attributes->getString('baseCurrencyCode'),
        );

        $errors = $this->validator->validate($dto);

        $this->processErrors($errors);

        yield $dto;
    }
}
