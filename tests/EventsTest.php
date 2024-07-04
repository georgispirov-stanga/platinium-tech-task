<?php

namespace App\Tests;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class EventsTest extends AbstractTest
{
	/**
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 */
	public function testGetCollection()
	{
		$response = $this->createClientWithCredentials()->request('GET', '/events');

		$this->assertResponseIsSuccessful();
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

		$this->assertJsonContains([
			'@context' => '/contexts/Event',
			'@id' => '/events',
			'@type' => 'hydra:Collection',
			'hydra:totalItems' => 100,
			'hydra:view' => [
				'@id' => '/events?page=1',
				'@type' => 'hydra:PartialCollectionView',
				'hydra:first' => '/events?page=1',
				'hydra:last' => '/events?page=4',
				'hydra:next' => '/events?page=2'
			]
		]);

		$this->assertCount(30, $response->toArray()['hydra:member']);
		$this->assertMatchesResourceCollectionJsonSchema(Event::class);
	}

	/**
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 */
	public function testCreateTicket()
	{
		$response = $this->createClientWithCredentials([
			'email' => 'admin@mail.com',
			'password' => '123456'
		])->request('POST', '/events', [
			'json' => [
				'name' => 'Chelsea - Barcelona',
				'date' => '2024-01-03 20:00',
				'description' => 'Come and see the match.',
				'location' => 'Stamford Bridge'
			]
		]);

		$this->assertResponseStatusCodeSame(201);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
		$this->assertJsonContains([
			'@context' => '/contexts/Event',
			'@type' => 'Event',
			'name' => 'Chelsea - Barcelona',
			'date' => '2024-01-03T20:00:00+00:00',
			'description' => 'Come and see the match.',
			'location' => 'Stamford Bridge'
		]);
		$this->assertMatchesRegularExpression('~^/events/\d+$~', $response->toArray()['@id']);
		$this->assertMatchesResourceItemJsonSchema(Event::class);
	}

	/**
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 */
	public function testUpdateEvent(): void
	{
		$client = static::createClientWithCredentials([
			'email' => 'admin@mail.com',
			'password' => '123456'
		]);

		$iri = $this->findIriBy(Event::class, ['id' => 1]);

		$client->request('PATCH', $iri, [
			'json' => [
				'name' => 'Chelsea - Barcelona',
				'date' => '2024-01-03 20:00:00',
				'description' => 'Come and see the match.',
				'location' => 'Stamford Bridge'
			],
			'headers' => [
				'Content-Type' => 'application/merge-patch+json',
			]
		]);

		$this->assertResponseIsSuccessful();
		$this->assertJsonContains([
			'@id' => $iri,
			'name' => 'Chelsea - Barcelona',
			'date' => '2024-01-03T20:00:00+00:00',
			'description' => 'Come and see the match.',
			'location' => 'Stamford Bridge'
		]);
	}

	/**
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 */
	public function testCreateInvalidEvent(): void
	{
		/* @var Registry $registry */
		$registry = static::getContainer()->get('doctrine');
		$event = $registry->getRepository(Event::class)->find(1);

		$this->createClientWithCredentials([
			'email' => 'admin@mail.com',
			'password' => '123456'
		])->request('POST', '/events', [
			'headers' => [
				'accept' => 'application/ld+json'
			],
			'json' => [
				'name' => $event->getName(),
				'date' => '2024-01-03T20:00:00+00:00',
				'description' => 'Come and see the match.',
				'location' => 'Stamford Bridge'
			]
		]);

		$this->assertResponseStatusCodeSame(422);
		$this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');

		$this->assertJsonContains([
			'@type' => 'ConstraintViolationList',
			'hydra:title' => 'An error occurred',
			'hydra:description' => 'name: Event with this name already exists.'
		]);
	}

	/**
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 */
	public function testDeleteEvent()
	{
		$client = static::createClientWithCredentials([
			'email' => 'admin@mail.com',
			'password' => '123456'
		]);

		$iri = $this->findIriBy(Event::class, ['id' => 1]);

		$client->request('DELETE', $iri);

		/* @var EntityManagerInterface $registry */
		$registry = static::getContainer()->get('doctrine');

		$this->assertResponseStatusCodeSame(204);
		$this->assertNull($registry->getRepository(Event::class)->find(1));
	}
}