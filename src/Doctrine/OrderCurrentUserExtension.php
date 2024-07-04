<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Order;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class OrderCurrentUserExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
	public function __construct(
		private Security $security,
		private UserRepository $userRepository
	) {}

	public function applyToItem(
		QueryBuilder $queryBuilder,
		QueryNameGeneratorInterface $queryNameGenerator,
		string $resourceClass,
		array $identifiers,
		?Operation $operation = null,
		array $context = []
	): void {
		$this->addWhere($queryBuilder, $resourceClass);
	}

	public function applyToCollection(
		QueryBuilder $queryBuilder,
		QueryNameGeneratorInterface $queryNameGenerator,
		string $resourceClass,
		?Operation $operation = null,
		array $context = []
	): void {
		$this->addWhere($queryBuilder, $resourceClass);
	}

	private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
	{
		if (
			Order::class !== $resourceClass ||
			$this->security->isGranted('ROLE_ADMIN') ||
			null === $user = $this->security->getUser()
		) {
			return;
		}

		$user = $this->userRepository->findByEmail($user->getUserIdentifier());

		if (!$user instanceof User) {
			throw new AccessDeniedException('User cannot be found.');
		}

		$rootAlias = $queryBuilder->getRootAliases()[0];
		$queryBuilder->andWhere(sprintf('%s.user = :current_user', $rootAlias));
		$queryBuilder->setParameter('current_user', $user);
	}
}