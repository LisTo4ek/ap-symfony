<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyIso4217;

use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyContract;
use App\Bundle\CurrencyRateProviderBundle\Src\Base\Currency\CurrencyManagerContract;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Psr\Container\ContainerInterface;

class CurrencyIso4217Type extends StringType
{
    public const string NAME = 'currency_iso_4217';

    private static ?ContainerInterface $container = null;
    private ?CurrencyManagerContract $currencyManager = null;

    public static function setContainer(ContainerInterface $container): void
    {
        self::$container = $container;
    }

    /**
     * Get the currency manager instance lazily from the container
     */
    private function getCurrencyManager(): CurrencyManagerContract
    {
        if ($this->currencyManager === null) {
            if (self::$container === null) {
                throw new \RuntimeException(
                    'CurrencyType requires a container to be set. ' .
                    'Call CurrencyType::setContainer() or inject it via dependency injection.'
                );
            }

            $this->currencyManager = self::$container->get(CurrencyManagerContract::class);
        }

        return $this->currencyManager;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?CurrencyContract
    {
        if ($value === null) {
            return null;
        }

        return $this->getCurrencyManager()::create((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof CurrencyContract) {
            return $value->getCode();
        }

        return (string) $value;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
