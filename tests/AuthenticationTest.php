<?php
declare(strict_types=1);

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class AuthenticationTest extends ApiTestCase
{
	use RefreshDatabaseTrait;

	/**
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 */
	public function testLogin(): void
	{
		$client = self::createClient();

		$response = $client->request('POST', '/login_check', [
			'headers' => ['Content-Type' => 'application/json'],
			'json' => [
				'email' => 'user@mail.com',
				'password' => '123456'
			]
		]);

		$json = $response->toArray();
		$this->assertResponseIsSuccessful();
		$this->assertArrayHasKey('token', $json);

		// test not authorized
		$client->request('GET', '/orders');
		$this->assertResponseStatusCodeSame(401);

		// test authorized
		$client->request('GET', '/orders', ['auth_bearer' => $json['token']]);
		$this->assertResponseIsSuccessful();
	}
}