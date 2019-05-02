<?php

namespace tests\unit;

use alexeevdv\yii\zerobounce\BadResponseException;
use alexeevdv\yii\zerobounce\Client;
use alexeevdv\yii\zerobounce\TransportException;
use Codeception\Test\Unit;
use yii\base\InvalidConfigException;
use yii\httpclient\Exception as HttpClientException;
use yii\httpclient\Request;
use yii\httpclient\Response;

class ClientTest extends Unit
{
    public function testApiKeyIsEnsured()
    {
        $this->expectException(InvalidConfigException::class);
        new Client;
    }

    public function testSuccessfulInstantiation()
    {
        new Client([
            'apiKey' => 'a95c530a7af5f492a74499e70578d150',
        ]);
    }

    public function testValidateOnValidEmail()
    {
        /** @var Client $client */
        $client = $this->make(Client::class, [
            'sendApiRequest' => $this->make(Response::class, [
                'getStatusCode' => '200',
                'getData' => [
                    'status' => 'valid',
                ],
            ])
        ]);
        $this->assertTrue($client->validate('valid@example.com')->isValid());
    }

    public function testValidateOnInvalidEmail()
    {
        /** @var Client $client */
        $client = $this->make(Client::class, [
            'createApiRequest' => $this->make(Request::class, [
                'send' => $this->make(Response::class, [
                    'getStatusCode' => '200',
                    'getData' => [
                        'status' => 'invalid',
                    ],
                ]),
            ])
        ]);
        $this->assertFalse($client->validate('invalid@example.com')->isValid());
    }

    public function testValidateWithTransportError()
    {
        /** @var Client $client */
        $client = $this->make(Client::class, [
            'createApiRequest' => $this->make(Request::class, [
                'send' => function () {
                    throw new HttpClientException;
                },
            ])
        ]);
        $this->expectException(TransportException::class);
        $client->validate('does-not-matter@example.com');
    }

    public function testValidateWithBadResponse()
    {
        /** @var Client $client */
        $client = $this->make(Client::class, [
            'createApiRequest' => $this->make(Request::class, [
                'send' => $this->make(Response::class, [
                    'getStatusCode' => '500',
                ]),
            ])
        ]);
        $this->expectException(BadResponseException::class);
        $client->validate('does-not-matter@example.com');
    }
}
