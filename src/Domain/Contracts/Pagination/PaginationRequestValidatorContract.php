<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Pagination;

use Symfony\Component\HttpFoundation\Request;

/**
 * Contract for validating pagination parameters from HTTP requests
 *
 * Allows different implementations of pagination request validation
 */
interface PaginationRequestValidatorContract
{
    /**
     * Validate and extract pagination parameters from request
     *
     * @return array{page: int, itemsPerPage: int}
     */
    public function validate(Request $request): array;

    /**
     * Get all available items per page options
     *
     * @return array<int>
     */
    public function getAvailableOptions(): array;
}

