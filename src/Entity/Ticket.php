<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\TicketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
#[ApiResource(
	operations: [
		new Get(
			security: "is_granted('ROLE_USER')"
		),
		new GetCollection(
			security: "is_granted('ROLE_USER')"
		),
		new Post(
			denormalizationContext: [
				'groups' => [
					self::GROUP_CREATE
				]
			],
			security: "is_granted('ROLE_ADMIN')"
		),
		new Patch(
			denormalizationContext: [
				'groups' => [
					self::GROUP_UPDATE
				]
			],
			security: "is_granted('ROLE_ADMIN')"
		),
		new Delete(
			security: "is_granted('ROLE_ADMIN')"
		)
	],
	normalizationContext: [
		'groups' => [
			self::GROUP_READ
		]
	]
)]
class Ticket
{
	public const GROUP_READ = 'ticket:read';
	public const GROUP_CREATE = 'ticket:create';
	public const GROUP_UPDATE = 'ticket:update';

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
	private ?Event $event = null;

	#[ORM\Column]
	#[NotBlank]
	#[GreaterThanOrEqual(value: 1, message: 'Price cannot be lower than 1.')]
	#[Groups([
		self::GROUP_READ,
		self::GROUP_CREATE,
		self::GROUP_UPDATE
	])]
	private ?int $price = null;

	#[ORM\Column]
	#[NotBlank]
	#[GreaterThanOrEqual(value: 0, message: 'Quantity must be greater than or equal to 0.')]
	#[Groups([
		self::GROUP_READ,
		self::GROUP_CREATE,
		self::GROUP_UPDATE
	])]
	private ?int $quantity = null;

	#[ORM\Column(type: Types::TEXT)]
	#[NotBlank]
	#[Groups([
		self::GROUP_READ,
		self::GROUP_CREATE,
		self::GROUP_UPDATE
	])]
	private ?string $description = null;

	/**
	 * @var Collection<int, OrderTicket>
	 */
	#[ORM\OneToMany(targetEntity: OrderTicket::class, mappedBy: 'ticket')]
	private Collection $orders;

	public function __construct()
	{
		$this->orders = new ArrayCollection();
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getEvent(): ?Event
	{
		return $this->event;
	}

	public function setEvent(?Event $event): static
	{
		$this->event = $event;

		return $this;
	}

	public function getPrice(): ?int
	{
		return $this->price;
	}

	public function setPrice(int $price): static
	{
		$this->price = $price;

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

	public function getDescription(): ?string
	{
		return $this->description;
	}

	public function setDescription(string $description): static
	{
		$this->description = $description;

		return $this;
	}

	/**
	 * @return Collection<int, OrderTicket>
	 */
	public function getOrders(): Collection
	{
		return $this->orders;
	}

	public function addOrder(OrderTicket $order): static
	{
		if (!$this->orders->contains($order)) {
			$this->orders->add($order);
			$order->setTicket($this);
		}

		return $this;
	}

	public function removeOrder(OrderTicket $order): static
	{
		if ($this->orders->removeElement($order)) {
			// set the owning side to null (unless already changed)
			if ($order->getTicket() === $this) {
				$order->setTicket(null);
			}
		}

		return $this;
	}
}
