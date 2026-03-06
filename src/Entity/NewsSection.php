<?php
namespace App\Entity;

class NewsSection
{
    public function __construct(
        public readonly string $name,
        public readonly int $commentsCount
    ) {}
}
