<?php

namespace Butschster\Kraken\Responses\Entities\Earn\Allocation;

use JMS\Serializer\Annotation\Type;

class AmountAllocated
{
    #[Type("Butschster\Kraken\Responses\Entities\Earn\Allocation\Total")]
    public Total $total;
}
