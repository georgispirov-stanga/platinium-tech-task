<?php
declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\EventRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints\NotBlank;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ApiResource(
	operations: [
		new Get(
			security: "is_granted('ROLE_USER')"
		),
		new GetCollection(
			security: "is_granted('ROLE_USER')"
		),
		new Post(
			security: "is_granted('ROLE_ADMIN')"
		),
		new Patch(
			security: "is_granted('ROLE_ADMIN')"
		),
		new Put(
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
	],
	denormalizationContext: [
		'groups' => [
			self::GROUP_WRITE
		]
	]
)]
#[UniqueEntity('name', 'Event with this name already exists.')]
class Event
{
	public const GROUP_READ = 'event:read';
	public const GROUP_WRITE = 'event:write';

	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	#[Groups([
		self::GROUP_READ
	])]
	private ?int $id = null;

	#[ORM\Column(length: 255)]
	#[NotBlank]
	#[Groups([
		self::GROUP_READ,
		self::GROUP_WRITE
	])]
	private ?string $name = null;

	#[ORM\Column]
	#[NotBlank]
	#[Groups([
		self::GROUP_READ,
		self::GROUP_WRITE
	])]
	private ?DateTimeImmutable $date = null;

	#[ORM\Column(type: Types::TEXT)]
	#[NotBlank]
	#[Groups([
		self::GROUP_READ,
		self::GROUP_WRITE
	])]
	private ?string $description = null;

	#[ORM\Column(length: 255)]
	#[NotBlank]
	#[Groups([
		self::GROUP_READ,
		self::GROUP_WRITE
	])]
	private ?string $location = null;

	/**
	 * @var Collection<int, Ticket>
	 */
	#[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'event', cascade: ['remove'])]
	private Collection $tickets;

	public function __construct()
	{
		$this->tickets = new ArrayCollection();
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getName(): ?string
	{
		return $this->name;
	}

	public function setName(string $name): static
	{
		$this->name = $name;

		return $this;
	}

	public function getDate(): ?DateTimeImmutable
	{
		return $this->date;
	}

	public function setDate(DateTimeImmutable $date): static
	{
		$this->date = $date;

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

	public function getLocation(): ?string
	{
		return $this->location;
	}

	public function setLocation(string $location): static
	{
		$this->location = $location;

		return $this;
	}

	/**
	 * @return Collection<int, Ticket>
	 */
	public function getTickets(): Collection
	{
		return $this->tickets;
	}

	public function addTicket(Ticket $ticket): static
	{
		if (!$this->tickets->contains($ticket)) {
			$this->tickets->add($ticket);
			$ticket->setEvent($this);
		}

		return $this;
	}

	public function removeTicket(Ticket $ticket): static
	{
		if ($this->tickets->removeElement($ticket)) {
			// set the owning side to null (unless already changed)
			if ($ticket->getEvent() === $this) {
				$ticket->setEvent(null);
			}
		}

		return $this;
	}
}
