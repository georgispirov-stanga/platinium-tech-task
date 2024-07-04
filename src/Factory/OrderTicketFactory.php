<?php

namespace App\Factory;

use App\Entity\OrderTicket;
use DateTimeImmutable;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<OrderTicket>
 */
class OrderTicketFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return OrderTicket::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'createdAt' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'order' => null,
            'quantity' => self::faker()->randomNumber(),
            'ticket' => TicketFactory::new()
        ];
    }

    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(OrderTicket $orderTicket): void {})
        ;
    }
}
