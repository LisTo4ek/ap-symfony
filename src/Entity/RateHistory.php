<?php

namespace App\Entity;

use App\Domain\CurrencyRateProvider\Base\CurrencyEnum;
use App\Repository\RateHistoryRepository;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RateHistoryRepository::class)]
#[ORM\UniqueConstraint(name: 'idx_currency_pair_date', columns: ['base_currency', 'target_currency', 'date'])]
#[ORM\Index(name: 'idx_base_currency_date', columns: ['base_currency', 'date'])]
#[ORM\Index(name: 'idx_target_currency_date', columns: ['target_currency', 'date'])]
#[ORM\Index(name: 'idx_date', columns: ['date'])]
class RateHistory {
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'base_currency', type: Types::STRING, length: 3, enumType: CurrencyEnum::class, options: ['fixed' => true])]
    private CurrencyEnum $baseCurrency;

    #[ORM\Column(name: 'target_currency', type: Types::STRING, length: 3, enumType: CurrencyEnum::class, options: ['fixed' => true])]
    private CurrencyEnum $targetCurrency;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 8)]
    private string $value;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private DateTimeInterface $date;

    public function __construct(
        CurrencyEnum $baseCurrency,
        CurrencyEnum $targetCurrency,
        string $value,
        DateTimeInterface $date
    ) {
        $this->baseCurrency = $baseCurrency;
        $this->targetCurrency = $targetCurrency;
        $this->value = $value;
        $this->date = $date;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setBaseCurrency(CurrencyEnum $baseCurrency): self
    {
        $this->baseCurrency = $baseCurrency;
        return $this;
    }

    public function setTargetCurrency(CurrencyEnum $targetCurrency): self
    {
        $this->targetCurrency = $targetCurrency;
        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getDate(): DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getBaseCurrency(): CurrencyEnum
    {
        return $this->baseCurrency;
    }

    public function getTargetCurrency(): CurrencyEnum
    {
        return $this->targetCurrency;
    }
}
