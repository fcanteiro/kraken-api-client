<?php

namespace Butschster\Kraken\Responses\Entities\Earn\Allocation;

use JMS\Serializer\Annotation\Type;

class Allocation
{
    #[Type('string')]
    public string $strategy_id;

    #[Type('string')]
    public string $native_asset;

    #[Type("Butschster\Kraken\Responses\Entities\Earn\Allocation\AmountAllocated")]
    public AmountAllocated $amount_allocated;

    #[Type("Butschster\Kraken\Responses\Entities\Earn\Allocation\TotalRewarded")]
    public TotalRewarded $total_rewarded;

    #[Type("Butschster\Kraken\Responses\Entities\Earn\Allocation\Payout")]
    public Payout $payout;
}
