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

#[ORM\Entity(repositoryClass: RateHistoryRepository::class)]
#[ORM\Index(name: 'idx_rate_history_base_target_date', columns: ['base_currency', 'target_currency', 'date'])]
#[ORM\Index(name: 'idx_rate_history_date', columns: ['date'])]
class RateHistory
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    /** @phpstan-ignore-next-line property.unusedType */
    private ?int $id = null;

    #[ORM\Column(name: 'base_currency', type: MoneyCurrencyType::NAME, options: ['fixed' => true])]
    private Currency $baseCurrency;

    #[ORM\Column(name: 'target_currency', type: MoneyCurrencyType::NAME, options: ['fixed' => true])]
    private Currency $targetCurrency;

    #[ORM\Column(type: BigDecimalStringType::NAME, length: 255)]
    private BigDecimal $value;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private DateTimeInterface $date;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $updatedAt;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): BigDecimal
    {
        return $this->value;
    }

    public function getDate(): DateTimeInterface
    {
        return $this->date;
    }

    public function getBaseCurrency(): Currency
    {
        return $this->baseCurrency;
    }

    public function getTargetCurrency(): Currency
    {
        return $this->targetCurrency;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
