<?php

declare(strict_types=1);

namespace App\Bundle\CurrencyRateBundle\Src\Entity;

use App\Bundle\CurrencyRateBundle\Src\Repository\CurrentRateRepository;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;

/**
 * Doctrine entity representing the latest exchange rate for a unique currency pair.
 *
 * Stored in the current_rate table with a unique constraint on (base_currency, target_currency).
 * When a new rate is imported for an existing pair, the row is updated (upsert) rather than duplicated.
 */
#[ORM\Entity(repositoryClass: CurrentRateRepository::class)]
#[ORM\UniqueConstraint(name: 'idx_base_target_currency', columns: ['base_currency', 'target_currency'])]
#[ORM\Index(name: 'idx_current_rate_date', columns: ['date'])]
#[ORM\Index(name: 'idx_current_rate_date_base_target', columns: ['date', 'base_currency', 'target_currency'])]
class CurrentRate
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
     * Updates the exchange rate value and refreshes the updatedAt timestamp.
     *
     * @param BigDecimal $value The new exchange rate value
     *
     * @return self For method chaining
     */
    public function setValue(BigDecimal $value): self
    {
        $this->value = $value;
        $this->updatedAt = new DateTimeImmutable();

        return $this;
    }

    /**
     * Returns the timestamp of the last update to this record.
     *
     * @return DateTimeImmutable The last-updated timestamp
     */
    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
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
     * Returns the date the rate is effective for.
     *
     * @return DateTimeInterface The rate date
     */
    public function getDate(): DateTimeInterface
    {
        return $this->date;
    }

    /**
     * Updates the date the rate applies to.
     *
     * @param DateTimeInterface $date The new rate date
     *
     * @return self For method chaining
     */
    public function setDate(DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }
}
