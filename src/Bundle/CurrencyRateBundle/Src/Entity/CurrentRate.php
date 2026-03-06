<?php

namespace App\Bundle\CurrencyRateBundle\Src\Entity;

use App\Bundle\CurrencyRateBundle\Src\Repository\CurrentRateRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;

#[ORM\Entity(repositoryClass: CurrentRateRepository::class)]
#[ORM\UniqueConstraint(name: 'idx_base_target_currency', columns: ['base_currency', 'target_currency'])]
#[ORM\Index(name: 'idx_updated_at', columns: ['updated_at'])]
class CurrentRate
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'base_currency', type: MoneyCurrencyType::NAME, options: ['fixed' => true])]
    private Currency $baseCurrency;

    #[ORM\Column(name: 'target_currency', type: MoneyCurrencyType::NAME, options: ['fixed' => true])]
    private Currency $targetCurrency;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 8)]
    private string $value;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private DateTimeInterface $date;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $updatedAt;

    public function __construct(
        Currency $baseCurrency,
        Currency $targetCurrency,
        string $value,
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

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;
        $this->updatedAt = new DateTimeImmutable();

        return $this;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getBaseCurrency(): Currency
    {
        return $this->baseCurrency;
    }

    public function getTargetCurrency(): Currency
    {
        return $this->targetCurrency;
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
}
