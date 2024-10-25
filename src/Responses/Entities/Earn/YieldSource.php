<?php

namespace Butschster\Kraken\Responses\Entities\Earn;

use JMS\Serializer\Annotation\Type;

class YieldSource
{
    #[Type('string')]
    public string $type;
}
