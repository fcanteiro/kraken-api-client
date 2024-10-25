<?php

namespace Butschster\Kraken\Responses\Entities\Earn;

use JMS\Serializer\Annotation\Type;

class LockTypeInstant extends LockType
{
    #[Type('int')]
    public int $payout_frequency;
}
