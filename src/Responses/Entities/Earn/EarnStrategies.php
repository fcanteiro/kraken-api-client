<?php

namespace Butschster\Kraken\Responses\Entities\Earn;

use JMS\Serializer\Annotation\Type;

class EarnStrategies
{
    #[Type("array<Butschster\Kraken\Responses\Entities\Earn\EarnStrategy>")]
    public array $items = [];

    #[Type('string')]
    public ?string $next_cursor = null;
}
