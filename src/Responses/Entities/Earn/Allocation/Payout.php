<?php

namespace Butschster\Kraken\Responses\Entities\Earn\Allocation;

use Carbon\Carbon;
use JMS\Serializer\Annotation\Type;

class Payout
{
    #[Type('Timestamp')]
    public Carbon $period_start;

    #[Type('Timestamp')]
    public Carbon $period_end;

    #[Type("Butschster\Kraken\Responses\Entities\Earn\Allocation\Reward")]
    public Reward $accumulated_reward;

    #[Type("Butschster\Kraken\Responses\Entities\Earn\Allocation\Reward")]
    public Reward $estimated_reward;
}
