<?php

declare(strict_types=1);

namespace App\Domain\Validation\ArgumentResolver;

use App\Domain\Config\Pagination\PaginatorConfigDefault;
use App\Domain\Contracts\Pagination\PaginatorConfigContract;
use App\Domain\Dto\CurrencyRate\CurrentRateDto;
use App\Domain\Dto\CurrencyRate\PaginationDto;
use Generator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CurrentRateAbstractDtoValueResolver extends AbstractDtoResolver
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        #[Autowire(service: PaginatorConfigDefault::class)]
        private readonly PaginatorConfigContract $paginatorConfig,
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): Generator
    {
        if ($argument->getType() !== CurrentRateDto::class) {
            return;
        }

        $dto = new CurrentRateDto(
            baseCurrencyCode: $request->attributes->get('baseCurrencyCode'),
            pagination: new PaginationDto(
                $request->query->getInt('page', 1),
                $request->query->getInt('perPage', $this->paginatorConfig->getPerPageDefault())
            ),
        );

        $errors = $this->validator->validate($dto);

        $this->processErrors($errors);

        yield $dto;
    }
}
