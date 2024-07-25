<?php
declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\OrderTicket;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class OrderTicketVoter extends Voter
{
	public const CREATE = 'order-ticket:create';
	public const UPDATE = 'order-ticket:update';
	public const DELETE = 'order-ticket:delete';

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
		return in_array($attribute, self::ACTIONS, true) && $subject instanceof OrderTicket;
	}

	/* @param OrderTicket $subject */
	protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
	{
		if (!$this->security->isGranted('ROLE_USER')) {
			return false;
		}

		$order = $subject->getOrder();

		if (null === $order) {
			return false;
		}

		$user = $token->getUser();

		if ($user instanceof JWTUserInterface) {
			$user = $this->userRepository->findByEmail($user->getUserIdentifier());
		}

		return $user === $order->getUser();
	}
}