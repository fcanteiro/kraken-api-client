<?php

namespace Butschster\Kraken\Responses;

use Butschster\Kraken\Responses\Entities\Earn\Allocation\Allocations;
use JMS\Serializer\Annotation\Type;

class EarnAllocationsResponse extends AbstractResponse
{
    #[Type("Butschster\Kraken\Responses\Entities\Earn\Allocation\Allocations")]
    public ?Allocations $result = null;
}
