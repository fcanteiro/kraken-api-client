<?php

namespace Butschster\Kraken\Responses\Entities\Earn;

use JMS\Serializer\Annotation\Type;

class AprEstimate
{
    #[Type('string')]
    public string $low;

    #[Type('string')]
    public string $high;
}
