<?php

declare(strict_types=1);

namespace App\Application\Service\Pagination;

use App\Domain\Contracts\Pagination\ItemsPerPageContract;
use App\Domain\Contracts\Pagination\PaginationRequestValidatorContract;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\HttpFoundation\Request;

/**
 * Validates pagination parameters from HTTP requests
 *
 * Extracts and validates page and itemsPerPage query parameters
 */
#[AsAlias(PaginationRequestValidatorContract::class)]
class PaginationRequestValidator implements PaginationRequestValidatorContract
{
    public function __construct(
        private readonly ItemsPerPageContract $itemsPerPage,
    ) {
    }

    /**
     * Validate and extract pagination parameters from request
     *
     * @return array{page: int, itemsPerPage: int}
     */
    public function validate(Request $request): array
    {
        return [
            'page' => $this->validatePage($request),
            'itemsPerPage' => $this->validateItemsPerPage($request),
        ];
    }

    /**
     * Validate and get the page number
     *
     * @return int A valid page number (minimum 1)
     */
    private function validatePage(Request $request): int
    {
        $page = $request->query->getInt('page', 1);

        // Ensure page is at least 1
        return max(1, $page);
    }

    /**
     * Validate and get the items per page value
     *
     * @return int A valid items per page value
     */
    private function validateItemsPerPage(Request $request): int
    {
        return $this->itemsPerPage->validate($request->query->getInt('itemsPerPage'));
    }

    /**
     * Get all available items per page options
     *
     * @return array<int>
     */
    public function getAvailableOptions(): array
    {
        return $this->itemsPerPage->getOptions();
    }
}


