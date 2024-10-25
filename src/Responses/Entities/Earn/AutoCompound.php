<?php

namespace Butschster\Kraken\Responses\Entities\Earn;

use JMS\Serializer\Annotation\Type;

class AutoCompound
{
    #[Type('string')]
    public string $type;

    #[Type('bool|null')]
    public ?bool $default = null;
}
