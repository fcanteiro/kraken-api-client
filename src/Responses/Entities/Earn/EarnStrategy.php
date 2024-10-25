<?php

namespace Butschster\Kraken\Responses\Entities\Earn;

use JMS\Serializer\Annotation\Type;

class EarnStrategy
{
    #[Type('string')]
    public string $id;

    #[Type('string')]
    public string $asset;

    #[Type("EarnLockType")]
    public LockType $lock_type;

    #[Type("Butschster\Kraken\Responses\Entities\Earn\AprEstimate")]
    public AprEstimate $apr_estimate;

    #[Type('string')]
    public ?string $user_cap;

    #[Type('string')]
    public ?string $user_min_allocation;

    #[Type('string|int|float')]
    public string|int|float $allocation_fee;

    #[Type('string')]
    public string $deallocation_fee;

    #[Type("Butschster\Kraken\Responses\Entities\Earn\AutoCompound")]
    public AutoCompound $auto_compound;

    #[Type("Butschster\Kraken\Responses\Entities\Earn\YieldSource")]
    public YieldSource $yield_source;

    #[Type('bool')]
    public bool $can_allocate;

    #[Type('bool')]
    public bool $can_deallocate;

    #[Type('array')]
    public array $allocation_restriction_info;
}
