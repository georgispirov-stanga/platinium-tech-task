<?php

namespace App\Factory;

use App\Entity\Ticket;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

final class TicketFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Ticket::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'description' => self::faker()->text(),
            'event' => EventFactory::new(),
            'price' => self::faker()->numberBetween(500, 20000),
            'quantity' => self::faker()->numberBetween(500, 3000),
        ];
    }
}
