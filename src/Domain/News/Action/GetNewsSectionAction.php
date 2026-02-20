<?php
namespace App\Domain\News\Action;

use App\Domain\News\Entity\NewsSection;

class GetNewsSectionAction
{
    public function __invoke(string $name): NewsSection
    {
        return new NewsSection(
            name: $name,
            commentsCount: \random_int(3, 10)
        );
    }
}
