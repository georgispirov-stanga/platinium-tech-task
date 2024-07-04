<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Doctrine\OrderTicketCurrentUserExtension;
use App\Repository\OrderTicketRepository;
use App\Security\Voter\OrderTicketVoter;
use App\Validator\OrderTicketQuantity;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

#[ORM\Entity(repositoryClass: OrderTicketRepository::class)]
#[ApiResource(
	operations: [
		/* @see OrderTicketCurrentUserExtension */
		new Get,
		/* @see OrderTicketCurrentUserExtension */
		new GetCollection,
		new Post(
			denormalizationContext: [
				'groups' => [
					self::GROUP_CREATE
				]
			],
			security: "is_granted('ROLE_USER')",
		),
		new Patch(
			denormalizationContext: [
				'groups' => [
					self::GROUP_UPDATE
				]
			],
			security: "is_granted('" . OrderTicketVoter::UPDATE . "', object)",
		),
		new Delete(
			security: "is_granted('" . OrderTicketVoter::DELETE . "', object)"
		)
	]
)]
#[UniqueEntity(fields: ['order', 'ticket'], message: 'Order has already this ticket.')]
class OrderTicket
{
	public const GROUP_CREATE = 'order-ticket:write';
	public const GROUP_READ = 'order-ticket:read';
	public const GROUP_UPDATE = 'order-ticket:update';

	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	#[Groups([
		self::GROUP_READ
	])]
	private ?int $id = null;

	#[ORM\ManyToOne(inversedBy: 'tickets')]
	#[ORM\JoinColumn(nullable: false)]
	#[NotBlank]
	#[Groups([
		self::GROUP_READ,
		self::GROUP_CREATE
	])]
	private ?Order $order = null;

	#[ORM\ManyToOne(inversedBy: 'orders')]
	#[ORM\JoinColumn(nullable: false)]
	#[NotBlank]
	#[Groups([
		self::GROUP_READ,
		self::GROUP_CREATE
	])]
	private ?Ticket $ticket = null;

	#[ORM\Column]
	#[NotBlank]
	#[GreaterThanOrEqual(1)]
	#[OrderTicketQuantity]
	#[Groups([
		self::GROUP_READ,
		self::GROUP_CREATE,
		self::GROUP_UPDATE
	])]
	private ?int $quantity = null;

	#[ORM\Column]
	#[Groups([
		self::GROUP_READ
	])]
	private DateTimeImmutable $createdAt;

	public function __construct()
	{
		$this->createdAt = new DateTimeImmutable;
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getOrder(): ?Order
	{
		return $this->order;
	}

	public function setOrder(?Order $order): static
	{
		$this->order = $order;

		return $this;
	}

	public function getTicket(): ?Ticket
	{
		return $this->ticket;
	}

	public function setTicket(?Ticket $ticket): static
	{
		$this->ticket = $ticket;

		return $this;
	}

	public function getQuantity(): ?int
	{
		return $this->quantity;
	}

	public function setQuantity(int $quantity): static
	{
		$this->quantity = $quantity;

		return $this;
	}

	public function getCreatedAt(): ?DateTimeImmutable
	{
		return $this->createdAt;
	}

	public function setCreatedAt(DateTimeImmutable $createdAt): static
	{
		$this->createdAt = $createdAt;

		return $this;
	}
}
