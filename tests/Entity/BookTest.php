<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Book;
use PHPUnit\Framework\TestCase;

class BookTest extends TestCase
{
    private Book $book;

    protected function setUp(): void
    {
        $this->book = new Book();
    }

    public function testGetIdReturnsNull(): void
    {
        $this->assertNull($this->book->getId());
    }

    public function testSetAndGetName(): void
    {
        $name = 'The Great Gatsby';
        $this->book->setName($name);

        $this->assertEquals($name, $this->book->getName());
    }

    public function testSetNameReturnsSelf(): void
    {
        $result = $this->book->setName('Test Book');

        $this->assertSame($this->book, $result);
    }
}
