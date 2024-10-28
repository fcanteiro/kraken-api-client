<?php

namespace Butschster\Kraken\Contracts;

use Brick\Math\BigDecimal;
use Illuminate\Contracts\Support\Arrayable;

interface EarnAllocationRequest extends Arrayable
{
    /**
     * The amount to allocate.
     */
    public function amount(): ?BigDecimal;

    /**
     * A unique identifier of the chosen earn strategy, as returned from `/0/private/Earn/Strategies`.
     */
    public function strategy_id(): string;
}
