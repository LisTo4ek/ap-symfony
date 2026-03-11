<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Entity;

use App\Bundle\CurrencyRateBundle\Src\Repository\RateHistoryRepository;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;

/**
 * Doctrine entity representing a historical exchange rate record for a currency pair.
 *
 * Stored in the rate_history table. Multiple rows can exist for the same currency pair
 * across different dates, providing a full history of rate changes.
 */
#[ORM\Entity(repositoryClass: RateHistoryRepository::class)]
#[ORM\Index(name: 'idx_rate_history_base_target_date', columns: ['base_currency', 'target_currency', 'date'])]
#[ORM\Index(name: 'idx_rate_history_date', columns: ['date'])]
class RateHistory
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    /** @phpstan-ignore-next-line property.unusedType */
    private ?int $id = null;

    #[ORM\Column(name: 'base_currency', type: MoneyCurrencyType::NAME, length: 3, options: ['fixed' => true])]
    private Currency $baseCurrency;

    #[ORM\Column(name: 'target_currency', type: MoneyCurrencyType::NAME, length: 3, options: ['fixed' => true])]
    private Currency $targetCurrency;

    #[ORM\Column(type: BigDecimalStringType::NAME, length: 255)]
    private BigDecimal $value;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private DateTimeInterface $date;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $updatedAt;

    /**
     * @param Currency $baseCurrency The base (source) currency
     * @param Currency $targetCurrency The target (destination) currency
     * @param BigDecimal $value The exchange rate value
     * @param DateTimeInterface $date The date the rate applies to
     */
    public function __construct(
        Currency $baseCurrency,
        Currency $targetCurrency,
        BigDecimal $value,
        DateTimeInterface $date,
    ) {
        $this->baseCurrency = $baseCurrency;
        $this->targetCurrency = $targetCurrency;
        $this->value = $value;
        $this->date = $date;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Returns the entity's auto-generated primary key.
     *
     * @return int|null The ID, or null if not yet persisted
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Returns the exchange rate value.
     *
     * @return BigDecimal The rate as a high-precision decimal
     */
    public function getValue(): BigDecimal
    {
        return $this->value;
    }

    /**
     * Returns the date the rate is effective for.
     *
     * @return DateTimeInterface The rate date
     */
    public function getDate(): DateTimeInterface
    {
        return $this->date;
    }

    /**
     * Returns the base (source) currency of this rate.
     *
     * @return Currency The base currency
     */
    public function getBaseCurrency(): Currency
    {
        return $this->baseCurrency;
    }

    /**
     * Returns the target (destination) currency of this rate.
     *
     * @return Currency The target currency
     */
    public function getTargetCurrency(): Currency
    {
        return $this->targetCurrency;
    }

    /**
     * Returns the timestamp of when this record was created.
     *
     * @return DateTimeImmutable The creation timestamp
     */
    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
