<?php
namespace App\Domain\Action;

use App\Domain\Entity\NewsSection;
use Random\RandomException;
use function random_int;

class GetNewsSectionAction
{
    /**
     * @throws RandomException
     */
    public function __invoke(string $name): NewsSection
    {
        return new NewsSection(
            name: $name,
            commentsCount: random_int(3, 10)
        );
    }
}
