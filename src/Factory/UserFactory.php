<?php
declare(strict_types=1);

namespace App\Factory;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

class UserFactory extends PersistentProxyObjectFactory
{
	public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
	{
		parent::__construct();
	}

	public static function class(): string
	{
		return User::class;
	}

	protected function defaults(): array|callable
	{
		return [
			'email' => self::faker()->email(),
			'password' => '123456',
			'roles' => ['ROLE_USER']
		];
	}

	public function admin(): static
	{
		return $this->with([
			'roles' => ['ROLE_ADMIN']
		]);
	}
	
	protected function initialize(): static
	{
		return $this->afterInstantiate(function(User $user): void {
			$user->setPassword(
				$this->passwordHasher->hashPassword($user, $user->getPassword())
			);
		});
	}
}