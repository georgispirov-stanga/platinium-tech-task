<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class AbstractTest extends ApiTestCase
{
	use RefreshDatabaseTrait;

	private ?string $token = null;

	public function setUp(): void
	{
		self::bootKernel();
	}

	/**
	 * @throws TransportExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws ClientExceptionInterface
	 */
	protected function createClientWithCredentials($body = []): Client
	{
		return static::createClient([], [
			'headers' => [
				'Authorization' => sprintf('Bearer %s', $this->getToken($body))
			]
		]);
	}

	/**
	 * @throws TransportExceptionInterface
	 * @throws ClientExceptionInterface
	 * @throws DecodingExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws ServerExceptionInterface
	 */
	protected function getToken($body = []): string
	{
		if ($this->token) {
			return $this->token;
		}

		$response = static::createClient()->request('POST', '/login_check', [
			'json' => $body ?: [
				'email' => 'user@mail.com',
				'password' => '123456'
			]
		]);

		$this->assertResponseIsSuccessful();
		$data = $response->toArray();
		$this->token = $data['token'];

		return $data['token'];
	}

	/**
	 * Helper method to mute unnecessary warnings in test running process.
	 */
	public function test()
	{
		$this->assertTrue(true);
	}
}