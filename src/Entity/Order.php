<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Doctrine\OrderCurrentUserExtension;
use App\Enum\OrderStateEnum;
use App\Repository\OrderRepository;
use App\Security\Voter\OrderVoter;
use App\State\OrderStateProcessor;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints\NotBlank;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[ApiResource(
	operations: [
		/* @see OrderCurrentUserExtension */
		new Get,
		/* @see OrderCurrentUserExtension */
		new GetCollection,
		new Post(
			denormalizationContext: [
				'groups' => [
					self::GROUP_CREATE
				]
			],
			security: "is_granted('ROLE_USER')"
		),
		new Patch(
			denormalizationContext: [
				'groups' => [
					self::GROUP_UPDATE
				]
			],
			security: "is_granted('" . OrderVoter::UPDATE . "', object)",
		),
		new Delete(
			security: "is_granted('" . OrderVoter::DELETE . "', object)"
		)
	],
	normalizationContext: [
		'groups' => [
			self::GROUP_READ
		]
	],
	processor: OrderStateProcessor::class
)]
class Order
{
	public const GROUP_CREATE = 'order:create';
	public const GROUP_READ = 'order:read';
	public const GROUP_UPDATE = 'order:update';

	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	#[Groups([
		self::GROUP_READ
	])]
	private ?int $id = null;

	#[ORM\ManyToOne(inversedBy: 'orders')]
	#[ORM\JoinColumn(nullable: false)]
	#[Groups([
		self::GROUP_READ
	])]
	private ?User $user = null;

	#[ORM\Column(type: Types::STRING, length: 25, enumType: OrderStateEnum::class)]
	#[Groups([
		self::GROUP_READ,
		self::GROUP_UPDATE
	])]
	private OrderStateEnum $state = OrderStateEnum::CREATED;

	#[ORM\Column]
	#[NotBlank]
	#[Groups([
		self::GROUP_READ
	])]
	private int $amount;

	#[ORM\Column(length: 55, nullable: true)]
	#[Groups([
		self::GROUP_READ,
		self::GROUP_UPDATE
	])]
	private ?string $paymentMethod = null;

	#[ORM\Column(nullable: true)]
	#[Groups([
		self::GROUP_READ,
		self::GROUP_UPDATE
	])]
	private ?array $paymentAttributes = null;

	#[ORM\Column]
	#[Groups([
		self::GROUP_READ
	])]
	private DateTimeImmutable $createdAt;

	#[ORM\OneToMany(targetEntity: OrderTicket::class, mappedBy: 'order')]
	#[Groups([
		self::GROUP_READ
	])]
	private Collection $tickets;

	public function __construct()
	{
		$this->amount = 0;
		$this->createdAt = new DateTimeImmutable;
		$this->tickets = new ArrayCollection;
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getUser(): ?User
	{
		return $this->user;
	}

	public function setUser(?User $user): static
	{
		$this->user = $user;

		return $this;
	}

	public function getState(): ?OrderStateEnum
	{
		return $this->state;
	}

	public function setState(OrderStateEnum $state): static
	{
		$this->state = $state;

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

	public function getAmount(): ?int
	{
		return $this->amount;
	}

	public function setAmount(int $amount): static
	{
		$this->amount = $amount;

		return $this;
	}

	/**
	 * @return Collection<int, OrderTicket>
	 */
	public function getTickets(): Collection
	{
		return $this->tickets;
	}

	public function addTicket(OrderTicket $ticket): static
	{
		if (!$this->tickets->contains($ticket)) {
			$this->tickets->add($ticket);
			$ticket->setOrder($this);
		}

		return $this;
	}

	public function removeTicket(OrderTicket $ticket): static
	{
		if ($this->tickets->removeElement($ticket)) {
			// set the owning side to null (unless already changed)
			if ($ticket->getOrder() === $this) {
				$ticket->setOrder(null);
			}
		}

		return $this;
	}

	public function getPaymentMethod(): ?string
	{
		return $this->paymentMethod;
	}

	public function setPaymentMethod(?string $paymentMethod): static
	{
		$this->paymentMethod = $paymentMethod;

		return $this;
	}

	public function getPaymentAttributes(): ?array
	{
		return $this->paymentAttributes;
	}

	public function setPaymentAttributes(?array $paymentAttributes): static
	{
		$this->paymentAttributes = $paymentAttributes;

		return $this;
	}
}
