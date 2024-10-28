<?php

namespace Butschster\Kraken\Requests;

use Brick\Math\BigDecimal;

class EarnAllocationRequest implements \Butschster\Kraken\Contracts\EarnAllocationRequest
{
    public function __construct(
        private ?BigDecimal $amount = null, private ?string $strategy_id = null) {}

    public function setAmount(?BigDecimal $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function setStrategyId(?string $strategy_id): self
    {
        $this->strategy_id = $strategy_id;

        return $this;
    }

    public function amount(): ?BigDecimal
    {
        return $this->amount;
    }

    public function strategy_id(): string
    {
        return $this->strategy_id;
    }

    public function toArray(): array
    {
        return [
            'amount' => (string) $this->amount(),
            'strategy_id' => $this->strategy_id(),
        ];
    }
}
