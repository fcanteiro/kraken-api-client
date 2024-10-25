<?php

namespace Butschster\Kraken\Responses;

use Butschster\Kraken\Responses\AbstractResponse;
use Butschster\Kraken\Responses\Entities\Earn\EarnStrategies;
use JMS\Serializer\Annotation\Type;

class EarnStrategiesResponse extends AbstractResponse
{
    #[Type("Butschster\Kraken\Responses\Entities\Earn\EarnStrategies")]
    public ?EarnStrategies $result = null;
}
