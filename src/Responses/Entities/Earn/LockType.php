<?php

namespace Butschster\Kraken\Responses\Entities\Earn;

use JMS\Serializer\Annotation\Type;

class LockType
{
    #[Type('string')]
    public string $type;

    public function getType(): string
    {
        return $this->type;
    }
}
