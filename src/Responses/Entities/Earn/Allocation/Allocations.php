<?php

namespace Butschster\Kraken\Responses\Entities\Earn\Allocation;

use Brick\Math\BigDecimal;
use JMS\Serializer\Annotation\Type;

class Allocations
{
    #[Type('string')]
    public string $converted_asset;

    #[Type(\Brick\Math\BigDecimal::class)]
    public BigDecimal $total_allocated;

    #[Type(\Brick\Math\BigDecimal::class)]
    public BigDecimal $total_rewarded;

    #[Type('string')]
    public ?string $next_cursor = null;

    #[Type("array<Butschster\Kraken\Responses\Entities\Earn\Allocation\Allocation>")]
    public array $items = [];
}
