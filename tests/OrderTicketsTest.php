<?php
declare(strict_types=1);

namespace App\Tests;

use App\Entity\Order;
use App\Entity\OrderTicket;
use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class OrderTicketsTest extends AbstractTest
{
	/**
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 */
	public function testCreateOrderTicket()
	{
		$payload = [
			'order' => $this->findIriBy(Order::class, ['id' => 1]),
			'ticket' => $this->findIriBy(Ticket::class, ['id' => 1]),
			'quantity' => 1
		];

		$response = $this->createClientWithCredentials()->request('POST', '/order_tickets', [
			'json' => $payload
		]);

		$this->assertResponseStatusCodeSame(201);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
		$this->assertJsonContains(array_merge([
			'@context' => '/contexts/OrderTicket',
			'@type' => 'OrderTicket'
		], $payload));

		$this->assertMatchesRegularExpression('~^/order_tickets/\d+$~', $response->toArray()['@id']);
		$this->assertMatchesResourceItemJsonSchema(OrderTicket::class);
	}

	/**
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 */
	public function testCreateOrderTicketWithInsufficientTicketQuantity()
	{
		/* @var Registry $em */
		$registry = static::getContainer()->get('doctrine');
		/* @var Ticket $ticket */
		$ticket = $registry->getRepository(Ticket::class)->find(1);

		$this->createClientWithCredentials([
			'email' => 'admin@mail.com',
			'password' => '123456'
		])->request('POST', '/order_tickets', [
			'headers' => [
				'accept' => 'application/ld+json'
			],
			'json' => [
				'order' => $this->findIriBy(Order::class, ['id' => 1]),
				'ticket' => $this->getIriFromResource($ticket),
				'quantity' => $ticket->getQuantity() + 1
			]
		]);

		$this->assertResponseStatusCodeSame(422);
		$this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
		$this->assertJsonContains([
			'@type' => 'ConstraintViolationList',
			'hydra:title' => 'An error occurred',
			'detail' => 'quantity: Insufficient quantity availability.'
		]);
	}
}