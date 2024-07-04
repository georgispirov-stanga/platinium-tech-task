<?php

namespace App\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Order;
use App\Entity\User;
use App\Enum\OrderStateEnum;
use App\Repository\UserRepository;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

readonly class OrderStateProcessor implements ProcessorInterface
{
	public const VALID_CARDS = [
		'4111111111111111' => [
			'exp' => [
				'month' => '02',
				'year' => '30'
			],
			'cvv' => '111'
		]
	];

	public function __construct(
		private ProcessorInterface $persistProcessor,
		private ProcessorInterface $removeProcessor,
		private Security $security,
		private UserRepository $userRepository
	) {}

	/**
	 * @throws Exception
	 */
	public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
	{
		if (!$data instanceof Order) {
			return $data;
		}

		if (
			$operation instanceof Patch &&
			array_key_exists('previous_data', $context) &&
			$context['previous_data'] instanceof Order &&
			$context['previous_data']->getState() !== OrderStateEnum::CREATED
		) {
			throw new Exception(sprintf(
				'Cannot change order with state: %s.',
				$data->getState()->value
			));
		}

		$user = $this->security->getUser();

		if ($user instanceof JWTUserInterface) {
			$user = $this->userRepository->findByEmail($user->getUserIdentifier());
		}

		if (!$user instanceof User) {
			throw new AccessDeniedException('You need to be logged in order to create order.');
		}

		if ($operation instanceof Post) { // creation
			$data->setUser($user);
		} else if ($operation instanceof Patch) { // update
			if ($data->getState() === OrderStateEnum::COMPLETED && !$this->security->isGranted('ROLE_ADMIN')) {
				throw new AccessDeniedException('Only admins can mark orders as completed.');
			}

			if (
				$data->getState() === OrderStateEnum::CREATED &&
				null !== $data->getPaymentMethod() &&
				!empty($data->getPaymentAttributes())
			) {
				$this->processOrderPayment($data);
			} else {
				$data->setPaymentMethod(null)
					 ->setPaymentAttributes(null);
			}
		}

		if ($operation instanceof Delete) {
			return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
		}

		return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
	}

	/**
	 * @throws Exception
	 */
	private function processOrderPayment(Order $order): void
	{
		if ($order->getAmount() < 1) {
			throw new BadRequestException('Cannot paid order with amount lower than 1.');
		}

		$attributes = $order->getPaymentAttributes();

		switch ($order->getPaymentMethod()) {
			case 'card':
				$this->processCardPayment($attributes);
				break;
			default:
				throw new BadRequestException('Unsupported payment method.');
		}

		$order->setState(OrderStateEnum::PAID);
	}

	/**
	 * @throws Exception
	 */
	private function processCardPayment(array $attributes): void
	{
		$cardNumber = $attributes['cardNumber'] ?? null;

		if (!$cardNumber) {
			throw new BadRequestException('Card number must be provided.');
		}

		if (!array_key_exists($cardNumber, self::VALID_CARDS)) {
			throw new BadRequestException('Card number is invalid.');
		}

		$card = self::VALID_CARDS[$cardNumber];

		$cvv = $attributes['cvv'] ?? null;

		if (!$cvv) {
			throw new BadRequestException('CVV must be provided.');
		}

		if ($card['cvv'] !== $cvv) {
			throw new BadRequestException('CVV is invalid.');
		}

		$exp = $attributes['exp'] ?? null;

		if (!is_iterable($exp) || !array_key_exists('month', $exp) || !array_key_exists('year', $exp)) {
			throw new BadRequestException('Expiration date with month and year must be provided.');
		}

		if ($exp['month'] !== $card['exp']['month'] || $exp['year'] !== $card['exp']['year']) {
			throw new BadRequestException('Expiration date is invalid.');
		}
	}
}