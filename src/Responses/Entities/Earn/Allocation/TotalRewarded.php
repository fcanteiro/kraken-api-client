<?php

namespace Butschster\Kraken\Responses\Entities\Earn\Allocation;

use Brick\Math\BigDecimal;
use JMS\Serializer\Annotation\Type;

class TotalRewarded
{
    #[Type(\Brick\Math\BigDecimal::class)]
    public BigDecimal $native;

    #[Type(\Brick\Math\BigDecimal::class)]
    public BigDecimal $converted;
}
