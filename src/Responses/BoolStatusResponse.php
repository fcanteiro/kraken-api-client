<?php

namespace Butschster\Kraken\Responses;

use JMS\Serializer\Annotation\Type;

class BoolStatusResponse extends AbstractResponse
{
    #[Type('bool')]
    public bool $result;
}
