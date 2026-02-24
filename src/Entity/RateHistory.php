<?php

namespace App\Entity;

use App\RateProvider\Domain\ValueObject\Currency;
use App\Repository\RateHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RateHistoryRepository::class)]
#[ORM\UniqueConstraint(name: 'idx_currency_pair_date', columns: ['base_currency', 'target_currency', 'date'])]
#[ORM\Index(name: 'idx_base_currency_date', columns: ['base_currency', 'date'])]
#[ORM\Index(name: 'idx_target_currency_date', columns: ['target_currency', 'date'])]
#[ORM\Index(name: 'idx_date', columns: ['date'])]
class RateHistory {
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'base_currency', type: 'string', enumType: Currency::class)]
    private Currency $baseCurrency;

    #[ORM\Column(name: 'target_currency', type: 'string', enumType: Currency::class)]
    private Currency $targetCurrency;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 8)]
    private string $value;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $date;

    public function __construct(
        Currency           $baseCurrency,
        Currency           $targetCurrency,
        string             $value,
        \DateTimeImmutable $date
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

    public function getBaseCurrency(): Currency
    {
        return $this->baseCurrency;
    }

    public function setBaseCurrency(Currency $baseCurrency): self
    {
        $this->baseCurrency = $baseCurrency;
        return $this;
    }

    public function getTargetCurrency(): Currency
    {
        return $this->targetCurrency;
    }

    public function setTargetCurrency(Currency $targetCurrency): self
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

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): self
    {
        $this->date = $date;
        return $this;
    }
}
