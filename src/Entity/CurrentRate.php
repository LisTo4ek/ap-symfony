<?php

namespace App\Entity;

use App\Domain\CurrencyRateProvider\Base\CurrencyEnum;
use App\Repository\CurrentRateRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CurrentRateRepository::class)]
#[ORM\UniqueConstraint(name: 'idx_base_target_currency', columns: ['base_currency', 'target_currency'])]
#[ORM\Index(name: 'idx_updated_at', columns: ['updated_at'])]
class CurrentRate
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'base_currency', type: Types::STRING, length: 3, enumType: CurrencyEnum::class, options: ['fixed' => true])]
    private CurrencyEnum $baseCurrency;

    #[ORM\Column(name: 'target_currency', type: Types::STRING, length: 3, enumType: CurrencyEnum::class, options: ['fixed' => true])]
    private CurrencyEnum $targetCurrency;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 8)]
    private string $value;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    public function __construct(
        CurrencyEnum $baseCurrency,
        CurrencyEnum $targetCurrency,
        string $value,
    ) {
        $this->baseCurrency = $baseCurrency;
        $this->targetCurrency = $targetCurrency;
        $this->value = $value;
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

    public function getBaseCurrency(): CurrencyEnum
    {
        return $this->baseCurrency;
    }

    public function getTargetCurrency(): CurrencyEnum
    {
        return $this->targetCurrency;
    }
}

