<?php
namespace App\Domain\Entity;

class NewsSection
{
    public function __construct(
        public readonly string $name,
        public readonly int $commentsCount
    ) {}
}
