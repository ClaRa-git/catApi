<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CatControllerTest extends WebTestCase
{
    public function testCatsEndpoint(): void
    {
        $client = static::createClient();

        $client->request('GET', '/cats');

        $this->assertResponseIsSuccessful();

        $this->assertJson($client->getResponse()->getContent());
    }
}