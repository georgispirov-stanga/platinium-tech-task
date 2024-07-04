<?php

namespace App\EventSubscriber\Persistence;

use App\Entity\Order;
use App\Entity\OrderTicket;
use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ObjectManager;
use Exception;

#[AsDoctrineListener(event: Events::onFlush)]
class OrderTicketEventListener
{
	private ?ClassMetadata $ticketClassMetadata = null;

	private ?ClassMetadata $orderClassMetadata = null;

	/**
	 * @throws Exception
	 */
	public function onFlush(OnFlushEventArgs $args): void
	{
		$em = $args->getObjectManager();
		$uow = $em->getUnitOfWork();

		foreach ($uow->getScheduledEntityInsertions() as $entity) {
			if (!$entity instanceof OrderTicket) {
				continue;
			}

			$this->processOrderTicketInsertionOrDeletion($em, $entity, 'insertion');
		}

		foreach ($uow->getScheduledEntityUpdates() as $entity) {
			if (!$entity instanceof OrderTicket) {
				continue;
			}

			if (null === ($order = $entity->getOrder())) {
				throw new Exception('Cannot process order ticket without attached order.');
			}

			if (null === ($ticket = $entity->getTicket())) {
				throw new Exception('Cannot process order ticket without attached ticket.');
			}

			$changeSet = $uow->getEntityChangeSet($entity);

			if (!array_key_exists('quantity', $changeSet)) {
				continue;
			}

			list($oldQuantity, $newQuantity) = $changeSet['quantity'];
			$ticket->setQuantity($ticket->getQuantity() + $oldQuantity - $newQuantity);
			$this->computeTicketEntityChangeSet($em, $ticket);

			$order->setAmount(
				$order->getAmount() - ($ticket->getPrice() * $oldQuantity) + ($ticket->getPrice() * $newQuantity)
			);
			$this->computeOrderEntityChangeSet($em, $order);
		}

		foreach ($uow->getScheduledEntityDeletions() as $entity) {
			if (!$entity instanceof OrderTicket) {
				continue;
			}

			$this->processOrderTicketInsertionOrDeletion($em, $entity, 'deletion');
		}
	}

	/**
	 * @throws Exception
	 */
	private function processOrderTicketInsertionOrDeletion(
		ObjectManager $manager,
		OrderTicket $orderTicket,
		string $operation
	): void {
		if (!in_array($operation, ['insertion', 'deletion'], true)) {
			throw new Exception(sprintf('Invalid operation type "%s".', $operation));
		}

		if (null === ($ticket = $orderTicket->getTicket())) {
			throw new Exception('Cannot process order ticket without attached order.');
		}

		if (null === ($order = $orderTicket->getOrder())) {
			throw new Exception('Cannot process order ticket without attached ticket.');
		}

		$ticket->setQuantity(
			$operation === 'insertion'
			? $ticket->getQuantity() - $orderTicket->getQuantity()
			: $ticket->getQuantity() + $orderTicket->getQuantity()
		);

		$this->computeTicketEntityChangeSet($manager, $ticket);

		$order->setAmount(
			$operation === 'insertion'
			? $order->getAmount() + ($ticket->getPrice() * $orderTicket->getQuantity())
			: $order->getAmount() - ($ticket->getPrice() * $orderTicket->getQuantity())
		);

		$this->computeOrderEntityChangeSet($manager, $order);
	}

	private function computeTicketEntityChangeSet(ObjectManager $manager, Ticket $ticket): void
	{
		/* @var UnitOfWork $uow */
		$uow = $manager->getUnitOfWork();

		if (null === $this->ticketClassMetadata) {
			$this->ticketClassMetadata = $manager->getClassMetadata(Ticket::class);
		}

		$uow->computeChangeSet($this->ticketClassMetadata, $ticket);
	}

	private function computeOrderEntityChangeSet(ObjectManager $manager, Order $order): void
	{
		/* @var UnitOfWork $uow */
		$uow = $manager->getUnitOfWork();

		if (null === $this->orderClassMetadata) {
			$this->orderClassMetadata = $manager->getClassMetadata(Order::class);
		}

		$uow->computeChangeSet($this->orderClassMetadata, $order);
	}
}