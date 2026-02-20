<?php
namespace App\Domain\News\Entity;

class NewsSection
{
    public function __construct(
        public readonly string $name,
        public readonly int $commentsCount
    ) {}
}
