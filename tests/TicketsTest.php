<?php
declare(strict_types=1);

namespace App\Tests;

use App\Entity\Ticket;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class TicketsTest extends AbstractTest
{
	/**
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 */
	public function testGetCollection()
	{
		$response = $this->createClientWithCredentials()->request('GET', '/tickets');

		$this->assertResponseIsSuccessful();
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

		$this->assertJsonContains([
			'@context' => '/contexts/Ticket',
			'@id' => '/tickets',
			'@type' => 'hydra:Collection',
			'hydra:totalItems' => 110,
			'hydra:view' => [
				'@id' => '/tickets?page=1',
				'@type' => 'hydra:PartialCollectionView',
				'hydra:first' => '/tickets?page=1',
				'hydra:last' => '/tickets?page=4',
				'hydra:next' => '/tickets?page=2'
			]
		]);

		$this->assertCount(30, $response->toArray()['hydra:member']);
		$this->assertMatchesResourceCollectionJsonSchema(Ticket::class);
	}

	/**
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 */
	public function testCreateTicket()
	{
		$response = $this->createClientWithCredentials([
			'email' => 'admin@mail.com',
			'password' => '123456'
		])->request('POST', '/tickets', [
			'json' => [
				'event' => '/events/1',
                'price' => 2000,
                'quantity' => 3000,
                'description' => 'Great derby match.'
			]
		]);

		$this->assertResponseStatusCodeSame(201);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
		$this->assertJsonContains([
			'@context' => '/contexts/Ticket',
			'@type' => 'Ticket',
			'event' => '/events/1',
			'price' => 2000,
			'quantity' => 3000,
			'description' => 'Great derby match.'
		]);
		$this->assertMatchesRegularExpression('~^/tickets/\d+$~', $response->toArray()['@id']);
		$this->assertMatchesResourceItemJsonSchema(Ticket::class);
	}

	/**
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 */
	public function testUpdateBook(): void
	{
		$client = static::createClientWithCredentials([
			'email' => 'admin@mail.com',
			'password' => '123456'
		]);

		$iri = $this->findIriBy(Ticket::class, ['id' => 1]);

		$client->request('PATCH', $iri, [
			'json' => [
				'price' => 500,
				'quantity' => 2000,
				'description' => 'The newly updated match description.'
			],
			'headers' => [
				'Content-Type' => 'application/merge-patch+json',
			]
		]);

		$this->assertResponseIsSuccessful();
		$this->assertJsonContains([
			'@id' => $iri,
			'price' => 500,
			'quantity' => 2000,
			'description' => 'The newly updated match description.'
		]);
	}

	/**
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 */
	public function testCreateInvalidTicket(): void
	{
		$this->createClientWithCredentials([
			'email' => 'admin@mail.com',
			'password' => '123456'
		])->request('POST', '/tickets', [
			'headers' => [
				'accept' => 'application/ld+json'
			],
			'json' => [
				'event' => '/events/1',
				'price' => 0,
				'quantity' => -1,
				'description' => 'Great derby match.'
			]
		]);

		$this->assertResponseStatusCodeSame(422);
		$this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');

		$this->assertJsonContains([
			'@type' => 'ConstraintViolationList',
			'hydra:title' => 'An error occurred',
			'hydra:description' => 'price: Price cannot be lower than 1.
quantity: Quantity must be greater than or equal to 0.'
		]);
	}

	/**
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 */
	public function testDeleteTicket()
	{
		$client = static::createClientWithCredentials([
			'email' => 'admin@mail.com',
			'password' => '123456'
		]);

		$iri = $this->findIriBy(Ticket::class, ['id' => 1]);

		$client->request('DELETE', $iri);

		/* @var EntityManagerInterface $registry */
		$registry = static::getContainer()->get('doctrine');

		$this->assertResponseStatusCodeSame(204);
		$this->assertNull($registry->getRepository(Ticket::class)->find(1));
	}
}