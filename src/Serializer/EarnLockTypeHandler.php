<?php

declare(strict_types=1);

namespace Butschster\Kraken\Serializer;

use Butschster\Kraken\Responses\Entities\Earn as Earn;
use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;

class EarnLockTypeHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => 'EarnLockType',
                'method' => 'deserialize',
            ],
        ];
    }

    public function deserialize(JsonDeserializationVisitor $visitor, $data, array $type, Context $context)
    {
        $navigator = $context->getNavigator();
        switch ($data['type']) {
            case 'instant':
                return $navigator->accept($data, ['name' => Earn\LockTypeInstant::class], $context);
            case 'flex':
                return $navigator->accept($data, ['name' => Earn\LockTypeFlex::class], $context);
            case 'bonded':
                return $navigator->accept($data, ['name' => Earn\LockTypeBonded::class], $context);
            default:
                throw new \RuntimeException('Unknown lock type: '.$data['type']);
        }
    }
}
