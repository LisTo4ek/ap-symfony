<?php

namespace App\Entity;

use App\RateProvider\Domain\ValueObject\Currency;
use App\Repository\CurrentRateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CurrentRateRepository::class)]
#[ORM\UniqueConstraint(name: 'idx_base_target_currency', columns: ['base_currency', 'target_currency'])]
#[ORM\Index(name: 'idx_updated_at', columns: ['updated_at'])]
class CurrentRate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'base_currency', type: 'string', enumType: Currency::class)]
    private Currency $baseCurrency;

    #[ORM\Column(name: 'target_currency', type: 'string', enumType: Currency::class)]
    private Currency $targetCurrency;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 8)]
    private string $value;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        Currency $baseCurrency,
        Currency $targetCurrency,
        string   $value,
    ) {
        $this->baseCurrency = $baseCurrency;
        $this->targetCurrency = $targetCurrency;
        $this->value = $value;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getBaseCurrency(): Currency
    {
        return $this->baseCurrency;
    }

    public function getId(): ?int
    {
        return $this->id;
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
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}

