<?php

use PHPUnit\Framework\TestCase;
use Faker\Factory;
use GuzzleHttp\Client;

class Test extends TestCase
{
    /**
     * @var \Faker\Generator
     */
    protected $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create();
    }

    public function testRequest()
    {
        $linePay = new NICO0420\LinePay\Client([
            'channelId' => $this->faker->numerify('##########'),
            'channelSecret' => $this->faker->sha1,
            'isSandbox' => true
        ]);

        $bodyParams = [
            'amount' => 1000,
            'currency' => 'TWD',
            'orderId' => $this->faker->uuid,
            'packages' => [
                [
                    'id' => $this->faker->uuid,
                    'amount' => 1000,
                    'products' => [
                        [
                            'id' => $this->faker->bothify('prod-####'),
                            'name' => $this->faker->sentence(3),
                            'quantity' => 1,
                            'price' => 1000,
                            'imageUrl' => $this->faker->imageUrl(640, 480, 'technics', true)
                        ]
                    ]
                ]
            ],
            'redirectUrls' => [
                'confirmUrl' => $this->faker->url,
                'cancelUrl' => $this->faker->url,
                'confirmUrlType' => 'CLIENT',
            ]
        ];

        try {
            $response = $linePay->request($bodyParams);

            if ($response->isSuccessful()) {
                var_dump($response->getPaymentUrl());
                // var_dump($response->info->transactionId);
                // die();
            } else {
                echo $response->getReturnMessage();
            }
        } catch (\Throwable $th) {
            $this->assertTrue(false);
        }

        $this->expectNotToPerformAssertions();
    }

    public function testConfirm()
    {
        $linePay = new NICO0420\LinePay\Client([
            'channelId' => $this->faker->numerify('##########'),
            'channelSecret' => $this->faker->sha1,
            'isSandbox' => true
        ]);

        $amount = 1000;
        $transactionId = $this->faker->numerify('##################');

        try {
            $response = $linePay->confirm($transactionId, [
                'amount' => $amount,
                'currency' => 'TWD'
            ]);
    

            if ($response->isSuccessful()) {
                var_dump($response->info);
                // var_dump($response->info->transactionId);
                // die();
            } else {
                echo $response->getReturnMessage();
            }
        } catch (\Throwable $th) {
            $this->assertTrue(false);
        }
        die();
    }
}
