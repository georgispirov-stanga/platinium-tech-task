<?php
declare(strict_types=1);

namespace App\Validator;

use App\Entity\OrderTicket;
use App\Repository\OrderTicketRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class OrderTicketQuantityValidator extends ConstraintValidator
{
	public function __construct(private readonly OrderTicketRepository $orderTicketRepository) {}

	public function validate(mixed $value, Constraint $constraint)
	{
		if (!$constraint instanceof OrderTicketQuantity) {
			throw new UnexpectedTypeException($constraint, OrderTicketQuantity::class);
		}

		// custom constraints should ignore null and empty values to allow
		// other constraints (NotBlank, NotNull, etc.) to take care of that
		if (null === $value || '' === $value) {
			return;
		}

		$orderTicket = $this->context->getObject();

		if (!$orderTicket instanceof OrderTicket || null === ($ticket = $orderTicket->getTicket())) {
			return;
		}

		if (!empty($orderTicket->getId())) {
			$currentOrderTicketQuantity = (int) $this->orderTicketRepository
											         ->createQueryBuilder('ot')
													 ->select('ot.quantity')
													 ->andWhere('ot = :order_ticket')
													 ->setParameter('order_ticket', $orderTicket->getId())
											         ->getQuery()
											         ->getSingleScalarResult() ?? 0;

			$ticketQuantity = $ticket->getQuantity() + $currentOrderTicketQuantity - $value;
		} else {
			$ticketQuantity = $ticket->getQuantity() - $value;
		}

		if ($ticketQuantity < 0) {
			$this->context->buildViolation($constraint->message)->addViolation();
		}
	}
}