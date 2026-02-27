<?php

namespace App\Entity;

use App\Bundle\CurrencyRateProviderBundle\Src\Currency\CurrencyContract;
use App\Repository\RateHistoryRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RateHistoryRepository::class)]
//#[ORM\UniqueConstraint(name: 'idx_currency_pair_date', columns: ['base_currency', 'target_currency', 'date'])]
#[ORM\Index(name: 'idx_base_currency_date', columns: ['base_currency', 'date'])]
#[ORM\Index(name: 'idx_target_currency_date', columns: ['target_currency', 'date'])]
#[ORM\Index(name: 'idx_date', columns: ['date'])]
class RateHistory {
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'base_currency', type: 'currency_iso_4217', options: ['fixed' => true])]
    private CurrencyContract $baseCurrency;

    #[ORM\Column(name: 'target_currency', type: 'currency_iso_4217', options: ['fixed' => true])]
    private CurrencyContract $targetCurrency;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $value;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private DateTimeInterface $date;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    public function __construct(
        CurrencyContract $baseCurrency,
        CurrencyContract $targetCurrency,
        string $value,
        DateTimeInterface $date
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

    public function setBaseCurrency(CurrencyContract $baseCurrency): self
    {
        $this->baseCurrency = $baseCurrency;
        return $this;
    }

    public function setTargetCurrency(CurrencyContract $targetCurrency): self
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

    public function getBaseCurrency(): CurrencyContract
    {
        return $this->baseCurrency;
    }

    public function getTargetCurrency(): CurrencyContract
    {
        return $this->targetCurrency;
    }
}
