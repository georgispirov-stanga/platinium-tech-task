<?php

namespace App\Factory;

use App\Entity\Event;
use DateTimeImmutable;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

class EventFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Event::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'date' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'description' => self::faker()->sentence(),
            'location' => self::faker()->city(),
            'name' => sprintf('%s - %s', self::faker()->team, self::faker()->team),
        ];
    }
}
