<?php
declare(strict_types=1);

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\Order;
use App\Entity\OrderTicket;
use App\Entity\User;
use App\Enum\OrderStateEnum;
use App\Factory\OrderTicketFactory;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class OrdersTest extends AbstractTest
{
	private static ?Order $orderWithTickets = null;

	public static function setUpBeforeClass(): void
	{
		self::$orderWithTickets = self::getOrderAndAttachTickets();
	}

	/**
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 */
	public function testCreateOrder()
	{
		$response = $this->createClientWithCredentials()->request('POST', '/orders', [
			'json' => []
		]);

		$this->assertResponseStatusCodeSame(201);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
		$this->assertJsonContains([
			'@context' => '/contexts/Order',
			'@type' => 'Order',
			'state' => OrderStateEnum::CREATED->value,
			'amount' => 0
		]);
		$this->assertMatchesRegularExpression('~^/orders/\d+$~', $response->toArray()['@id']);
		$this->assertMatchesResourceItemJsonSchema(Order::class);
	}

	/**
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 */
	public function testPaymentSuccessful(): void
	{
		$iri = $this->getIriFromResource(self::$orderWithTickets);

		$this->createOrderPaymentRequest(
			static::createClientWithCredentials(),
			self::$orderWithTickets,
			'4111111111111111',
			'111',
			[
				'month' => '02',
				'year' => '30'
			]
		);

		$this->assertResponseIsSuccessful();

		$this->assertJsonContains([
			'@id' => $iri,
			'@context' => '/contexts/Order',
			'@type' => 'Order',
			'state' => OrderStateEnum::PAID->value,
			'paymentMethod' => 'card',
			'paymentAttributes' => [
				'cardNumber' => '4111111111111111',
				'cvv' => '111',
				'exp' => [
					'month' => '02',
					'year' => '30'
				]
			]
		]);
	}

	/**
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 */
	public function testPaymentMissingCard()
	{
		$this->createOrderPaymentRequest(
			static::createClientWithCredentials(),
			self::$orderWithTickets,
			'',
			'111',
			[
				'month' => '02',
				'year' => '30'
			]
		);

		$this->assertResponseStatusCodeSame(400);
		$this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
		$this->assertJsonContains([
			'@id' => '/errors/400',
			'@type' => 'hydra:Error',
			'title' => 'An error occurred',
			'detail' => 'Card number must be provided.'
		]);
	}

	/**
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 */
	public function testPaymentInvalidCard()
	{
		$this->createOrderPaymentRequest(
			static::createClientWithCredentials(),
			self::$orderWithTickets,
			'4111111111111113',
			'111',
			[
				'month' => '02',
				'year' => '30'
			]
		);

		$this->assertResponseStatusCodeSame(400);
		$this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
		$this->assertJsonContains([
			'@id' => '/errors/400',
			'@type' => 'hydra:Error',
			'title' => 'An error occurred',
			'detail' => 'Card number is invalid.'
		]);
	}

	/**
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 */
	public function testPaymentMissingCvv()
	{
		$this->createOrderPaymentRequest(
			static::createClientWithCredentials(),
			self::$orderWithTickets,
			'4111111111111111',
			'',
			[
				'month' => '02',
				'year' => '30'
			]
		);

		$this->assertResponseStatusCodeSame(400);
		$this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
		$this->assertJsonContains([
			'@id' => '/errors/400',
			'@type' => 'hydra:Error',
			'title' => 'An error occurred',
			'detail' => 'CVV must be provided.'
		]);
	}

	/**
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 */
	public function testPaymentInvalidCvv()
	{
		$this->createOrderPaymentRequest(
			static::createClientWithCredentials(),
			self::$orderWithTickets,
			'4111111111111111',
			'112',
			[
				'month' => '02',
				'year' => '30'
			]
		);

		$this->assertResponseStatusCodeSame(400);
		$this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
		$this->assertJsonContains([
			'@id' => '/errors/400',
			'@type' => 'hydra:Error',
			'title' => 'An error occurred',
			'detail' => 'CVV is invalid.'
		]);
	}

	/**
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 */
	public function testPaymentMissingExpiration()
	{
		$this->createOrderPaymentRequest(
			static::createClientWithCredentials(),
			self::$orderWithTickets,
			'4111111111111111',
			'111',
			[]
		);

		$this->assertResponseStatusCodeSame(400);
		$this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
		$this->assertJsonContains([
			'@id' => '/errors/400',
			'@type' => 'hydra:Error',
			'title' => 'An error occurred',
			'detail' => 'Expiration date with month and year must be provided.'
		]);
	}

	/**
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 */
	public function testPaymentInvalidExpirationInformation()
	{
		$this->createOrderPaymentRequest(
			static::createClientWithCredentials(),
			self::$orderWithTickets,
			'4111111111111111',
			'111',
			[
				'month' => '03',
				'year' => '33'
			]
		);

		$this->assertResponseStatusCodeSame(400);
		$this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
		$this->assertJsonContains([
			'@id' => '/errors/400',
			'@type' => 'hydra:Error',
			'title' => 'An error occurred',
			'detail' => 'Expiration date is invalid.'
		]);
	}

	private static function getOrderAndAttachTickets(): Order
	{
		if (null !== self::$orderWithTickets) {
			return self::$orderWithTickets;
		}

		/* @var Registry $em */
		$registry = static::getContainer()->get('doctrine');
		$order = $registry->getRepository(Order::class)->find(1);

		$orderTickets = OrderTicketFactory::createMany(10, [
			'order' => $order,
			'quantity' => 1
		]);

		/* @var OrderTicket $orderTicket */
		foreach ($orderTickets as $orderTicket) {
			$ticket = $orderTicket->getTicket();
			$ticket->setQuantity($ticket->getQuantity() - $orderTicket->getQuantity());
			$order->setAmount($ticket->getPrice() * $orderTicket->getQuantity());
		}

		$registry->getManager()->flush();

		self::$orderWithTickets = $order;

		return self::$orderWithTickets;
	}

	/**
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 */
	public function testSuccessfulCancellingOrder()
	{
		/* @var Registry $registry */
		$registry = static::getContainer()->get('doctrine');

		$user = $registry->getRepository(User::class)->find(1);

		/* @var Order $order */
		$order = $registry->getRepository(Order::class)->findOneBy(['user' => $user]);

		$iri = $this->getIriFromResource($order);

		$this->createClientWithCredentials([
			'email' => $user->getEmail(),
			'password' => '123456'
		])->request('PATCH', $iri, [
			'headers' => [
				'Content-Type' => 'application/merge-patch+json'
			],
			'json' => [
				'state' => OrderStateEnum::CANCELLED->value
			]
		]);

		$this->assertResponseIsSuccessful();
		$this->assertJsonContains([
			'@id' => $iri,
			'state' => OrderStateEnum::CANCELLED->value,
			'user' => $this->getIriFromResource($user)
		]);
	}

	/**
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 */
	public function testSuccessfullyCompletingOrder()
	{
		/* @var Registry $registry */
		$registry = static::getContainer()->get('doctrine');

		/* @var Order $order */
		$order = $registry->getRepository(Order::class)->find(1);

		$iri = $this->getIriFromResource($order);

		$this->createClientWithCredentials([
			'email' => 'admin@mail.com',
			'password' => '123456'
		])->request('PATCH', $iri, [
			'headers' => [
				'Content-Type' => 'application/merge-patch+json'
			],
			'json' => [
				'state' => OrderStateEnum::COMPLETED->value
			]
		]);

		$this->assertResponseIsSuccessful();
		$this->assertJsonContains([
			'@id' => $iri,
			'state' => OrderStateEnum::COMPLETED->value
		]);
	}

	/**
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 */
	public function testUpdatingOrderWithoutOwnership()
	{
		/* @var Registry $registry */
		$registry = static::getContainer()->get('doctrine');

		$firstUser = $registry->getRepository(User::class)->find(1);
		$secondUser = $registry->getRepository(User::class)->find(3);

		/* @var Order $order */
		$order = $registry->getRepository(Order::class)->findOneBy(['user' => $firstUser]);

		$iri = $this->getIriFromResource($order);

		$this->createClientWithCredentials([
			'email' => $secondUser->getEmail(),
			'password' => '123456'
		])->request('PATCH', $iri, [
			'headers' => [
				'Content-Type' => 'application/merge-patch+json'
			],
			'json' => [
				'state' => OrderStateEnum::CANCELLED->value
			]
		]);

		$this->assertResponseStatusCodeSame(404);
		$this->assertJsonContains([
			'@id' => '/errors/404',
			'@type' => 'hydra:Error',
			'title' => 'An error occurred'
		]);
	}

	/**
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 */
	public function testDeleteOrder()
	{
		$client = static::createClientWithCredentials([
			'email' => 'user@mail.com',
			'password' => '123456'
		]);

		$client->request('DELETE', $this->findIriBy(Order::class, ['id' => 1]));

		/* @var Registry $registry */
		$registry = static::getContainer()->get('doctrine');
		$this->assertResponseStatusCodeSame(204);
		$this->assertNull($registry->getRepository(Order::class)->find(1));
	}

	/**
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 */
	public function testDeleteOrderWithoutOwnership()
	{
		$client = static::createClientWithCredentials([
			'email' => 'user3@mail.com',
			'password' => '123456'
		]);

		$client->request('DELETE', $this->findIriBy(Order::class, ['id' => 1]));
		$this->assertResponseStatusCodeSame(404);
		$this->assertJsonContains([
			'@id' => '/errors/404',
			'@type' => 'hydra:Error',
			'title' => 'An error occurred'
		]);
	}

	/**
	 * @throws TransportExceptionInterface
	 */
	private function createOrderPaymentRequest(Client $client, Order $order, string $cardNumber, string $cvv, array $exp): void
	{
		$client->request('PATCH', $this->getIriFromResource($order), [
			'json' => [
				'paymentMethod' => 'card',
				'paymentAttributes' => [
					'cardNumber' => $cardNumber,
					'cvv' => $cvv,
					'exp' => $exp
				]
			],
			'headers' => [
				'Content-Type' => 'application/merge-patch+json',
			]
		]);
	}
}