<?php

namespace App\DataFixtures\Processor;

use App\Entity\User;
use Fidry\AliceDataFixtures\ProcessorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserProcessor implements ProcessorInterface
{
	public function __construct(private UserPasswordHasherInterface $passwordHasher) {}

	public function preProcess(string $id, object $object): void
	{
		if (!$object instanceof User) {
			return;
		}

		$object->setPassword(
			$this->passwordHasher->hashPassword($object, $object->getPassword())
		);
	}

	public function postProcess(string $id, object $object): void {}
}