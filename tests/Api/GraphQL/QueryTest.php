<?php

declare(strict_types=1);

namespace OmegaCode\JwtSecuredApiGraphQL\Tests\Api\GraphQL;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class QueryTest extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        $this->client = new Client();
    }

    /**
     * @test
     */
    public function emptyQueryReturnsError(): void
    {
        $response = $this->client->request('POST', 'http://api/graphql', [
            'http_errors' => false,
            'json' => []
        ]);
        $this->assertStringContainsString('Query can not be empty', (string) $response->getBody());
    }

    /**
     * @test
     */
    public function greetPersonReturnsPersonsName(): void
    {
        $response = $this->client->request('POST', 'http://api/graphql', [
            'http_errors' => false,
            'json' => [
                'query' => '{greet(name:"Person")}'
            ]
        ]);
        $this->assertStringContainsString('Hello Person', (string) $response->getBody());
    }
}
