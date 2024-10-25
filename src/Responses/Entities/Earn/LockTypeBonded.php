<?php

namespace Butschster\Kraken\Responses\Entities\Earn;

use JMS\Serializer\Annotation\Type;

class LockTypeBonded extends LockType
{
    #[Type('int')]
    public int $payout_frequency;

    #[Type('int')]
    public int $bonding_period;

    #[Type('bool')]
    public bool $bonding_period_variable;

    #[Type('bool')]
    public bool $bonding_rewards;

    #[Type('int')]
    public int $exit_queue_period;

    #[Type('int')]
    public int $unbonding_period;

    #[Type('bool')]
    public bool $unbonding_period_variable;

    #[Type('bool')]
    public bool $unbonding_rewards;
}
