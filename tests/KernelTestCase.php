<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase as BaseKernelTestCase;

class KernelTestCase extends BaseKernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function fromContainer(string $class)
    {
        return static::getContainer()->get($class);
    }
}
