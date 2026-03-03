<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Tests\Integration;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyManagerContract;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Logger\CurrencyRateProviderLogger;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Logger\CurrencyRateProviderLoggerContract;
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CbrProvider\CbrProvider;
use App\Bundle\CurrencyRateProviderBundle\Src\Providers\CbrProvider\Processor\XmlProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CbrProviderIntegrationTest extends TestCase
{
    /**
     * Test provider can be instantiated with dependencies
     */
    public function testProviderCanBeInstantiated(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $logger = $this->createMock(CurrencyRateProviderLoggerContract::class);
        $currencyManager = $this->createMock(CurrencyManagerContract::class);

        $processor = new XmlProcessor(
            $currencyManager,
            $logger
        );

        $provider = new CbrProvider(
            $httpClient,
            $logger,
            $processor,
            'https://cbr.ru/scripts/XML_daily.asp',
            ['USD', 'EUR'],
            'RUB',
            30,
            4
        );

        $this->assertInstanceOf(CbrProvider::class, $provider);
    }

    /**
     * Test processor can be created with dependencies
     */
    public function testProcessorCanBeInstantiated(): void
    {
        $currencyManager = $this->createMock(CurrencyManagerContract::class);
        $logger = $this->createMock(CurrencyRateProviderLoggerContract::class);

        $processor = new XmlProcessor(
            $currencyManager,
            $logger
        );

        $this->assertInstanceOf(XmlProcessor::class, $processor);
    }

    /**
     * Test logger can be instantiated with PSR-3 logger
     */
    public function testLoggerCanBeInstantiated(): void
    {
        $psr3Logger = $this->createMock(LoggerInterface::class);
        $logger = new CurrencyRateProviderLogger($psr3Logger);

        $this->assertInstanceOf(CurrencyRateProviderLoggerContract::class, $logger);
    }

    /**
     * Test all dependencies can be created together
     */
    public function testAllDependenciesCanBeCreatedTogether(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $psr3Logger = $this->createMock(LoggerInterface::class);
        $logger = new CurrencyRateProviderLogger($psr3Logger);
        $currencyManager = $this->createMock(CurrencyManagerContract::class);

        $processor = new XmlProcessor(
            $currencyManager,
            $logger
        );

        $provider = new CbrProvider(
            $httpClient,
            $logger,
            $processor,
            'https://cbr.ru/scripts/XML_daily.asp',
            ['USD', 'EUR'],
            'RUB',
            30,
            4
        );

        // Verify all instances
        $this->assertInstanceOf(CbrProvider::class, $provider);
        $this->assertInstanceOf(XmlProcessor::class, $processor);
        $this->assertInstanceOf(CurrencyRateProviderLoggerContract::class, $logger);
    }
}
