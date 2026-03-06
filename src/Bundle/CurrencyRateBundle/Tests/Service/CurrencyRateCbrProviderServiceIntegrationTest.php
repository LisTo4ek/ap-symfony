<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Tests\Service;

use App\Bundle\CurrencyRateBundle\Src\Config\CurrencyEnum;
use App\Bundle\CurrencyRateBundle\Src\Service\CurrencyRateProviderCbrService;
use App\Bundle\CurrencyRateBundle\Src\Service\CurrencyRateParserXmlService;
use App\Bundle\CurrencyRateBundle\Src\Service\CurrencyRateProviderLoggerService;
use App\Bundle\CurrencyRateBundle\Src\Service\CurrencyRateProviderLoggerServiceInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CurrencyRateCbrProviderServiceIntegrationTest extends TestCase
{
    /**
     * Test provider can be instantiated with dependencies
     */
    public function testProviderCanBeInstantiated(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $logger = $this->createMock(CurrencyRateProviderLoggerServiceInterface::class);
        $processor = new CurrencyRateParserXmlService($logger);

        $provider = new CurrencyRateProviderCbrService(
            $httpClient,
            $logger,
            $processor,
            'https://cbr.ru/scripts/XML_daily.asp',
            ['USD', 'EUR'],
            CurrencyEnum::RUB->value,
            30,
            4
        );

        $this->assertInstanceOf(CurrencyRateProviderCbrService::class, $provider);
    }

    /**
     * Test processor can be created with dependencies
     */
    public function testProcessorCanBeInstantiated(): void
    {
        $logger = $this->createMock(CurrencyRateProviderLoggerServiceInterface::class);

        $processor = new CurrencyRateParserXmlService(
            $logger
        );

        $this->assertInstanceOf(CurrencyRateParserXmlService::class, $processor);
    }

    /**
     * Test logger can be instantiated with PSR-3 logger
     */
    public function testLoggerCanBeInstantiated(): void
    {
        $psr3Logger = $this->createMock(LoggerInterface::class);
        $logger = new CurrencyRateProviderLoggerService($psr3Logger);

        $this->assertInstanceOf(CurrencyRateProviderLoggerServiceInterface::class, $logger);
    }

    /**
     * Test all dependencies can be created together
     */
    public function testAllDependenciesCanBeCreatedTogether(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $psr3Logger = $this->createMock(LoggerInterface::class);
        $logger = new CurrencyRateProviderLoggerService($psr3Logger);

        $processor = new CurrencyRateParserXmlService(
            $logger
        );

        $provider = new CurrencyRateProviderCbrService(
            $httpClient,
            $logger,
            $processor,
            'https://cbr.ru/scripts/XML_daily.asp',
            [CurrencyEnum::USD->value, CurrencyEnum::EUR->value],
            CurrencyEnum::RUB->value,
            30,
            4
        );

        // Verify all instances
        $this->assertInstanceOf(CurrencyRateProviderCbrService::class, $provider);
        $this->assertInstanceOf(CurrencyRateParserXmlService::class, $processor);
        $this->assertInstanceOf(CurrencyRateProviderLoggerServiceInterface::class, $logger);
    }
}
