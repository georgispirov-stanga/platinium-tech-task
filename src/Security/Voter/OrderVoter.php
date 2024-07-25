<?php
declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Order;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class OrderVoter extends Voter
{
	public const CREATE = 'order:create';
	public const UPDATE = 'order:update';
	public const DELETE = 'order:delete';

	public const ACTIONS = [
		self::CREATE,
		self::UPDATE,
		self::DELETE
	];

	public function __construct(
		private readonly Security $security,
		private readonly UserRepository $userRepository
	) {}

	protected function supports(string $attribute, mixed $subject): bool
	{
		return in_array($attribute, self::ACTIONS, true) && $subject instanceof Order;
	}

	/* @param Order $subject */
	protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
	{
		if (!$this->security->isGranted('ROLE_USER')) {
			return false;
		}

		if ($attribute === self::CREATE) {
			return true;
		}

		$user = $token->getUser();

		if ($user instanceof JWTUserInterface) {
			$user = $this->userRepository->findByEmail($user->getUserIdentifier());
		}

		return $user === $subject->getUser() || $this->security->isGranted('ROLE_ADMIN');
	}
}