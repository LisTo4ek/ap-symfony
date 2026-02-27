<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Author;
use PHPUnit\Framework\TestCase;

class AuthorTest extends TestCase
{
    private Author $author;

    protected function setUp(): void
    {
        $this->author = new Author();
    }

    public function testGetIdReturnsNull(): void
    {
        $this->assertNull($this->author->getId());
    }

    public function testSetAndGetName(): void
    {
        $name = 'F. Scott Fitzgerald';
        $this->author->setName($name);

        $this->assertEquals($name, $this->author->getName());
    }

    public function testSetNameReturnsSelf(): void
    {
        $result = $this->author->setName('Test Author');

        $this->assertSame($this->author, $result);
    }
}
