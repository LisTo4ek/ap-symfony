<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\ArgumentResolver;

use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigDefault;
use App\Bundle\CurrencyRateBundle\Src\Config\PaginationConfigInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function count;
use function implode;

/**
 * Base class for controller argument resolvers that validate request data into container DTOs.
 *
 * Provides shared validation error handling for concrete resolver implementations.
 */
abstract class AbstractContainerResolver implements ValueResolverInterface
{
    /**
     * @param ValidatorInterface        $validator       Symfony validator for DTO constraint checking
     * @param PaginationConfigInterface $paginatorConfig Default pagination settings (per-page default, etc.)
     */
    public function __construct(
        protected readonly ValidatorInterface $validator,
        #[Autowire(service: PaginationConfigDefault::class)]
        protected readonly PaginationConfigInterface $paginatorConfig,
    ) {
    }

    /**
     * Resolves the request into a CurrentRateContainer argument if the argument type matches.
     *
     * Builds the DTO from query parameters (page, perPage) and route attribute (baseCurrencyCode),
     * validates it, and yields the result. Skips resolution if the argument type does not match.
     *
     * @param Request          $request  The current HTTP request
     * @param ArgumentMetadata $argument Metadata about the controller argument being resolved
     *
     * @return iterable<int, object>
     */
    abstract public function resolve(Request $request, ArgumentMetadata $argument): iterable;

    /**
     * Processes constraint validation errors and throws a BadRequestHttpException if any exist.
     *
     * Collects all violation messages and joins them into a single comma-separated string.
     *
     * @param ConstraintViolationListInterface $errors The list of constraint violations to process
     *
     * @throws BadRequestHttpException When one or more validation errors are present
     */
    protected function processErrors(ConstraintViolationListInterface $errors): void
    {
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }
            throw new BadRequestHttpException(implode(', ', $messages));
        }
    }
}
