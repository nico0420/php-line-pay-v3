<?php

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use GuzzleHttp\Client;

class Test extends TestCase
{

    public function testRequest()
    {
        $linePay = new NICO0420\LinePay\Client([
            'channelId' => '2008797507',
            'channelSecret' => '4fee3891da49814582502cba0edfd1d5',
            'isSandbox' => true
        ]);

        $bodyParams = [
            "amount" => 1,
            "currency" => "TWD",
            "orderId" => Uuid::uuid4()->toString(),
            "packages" => [
                [
                    "id" => Uuid::uuid4()->toString(),
                    "amount" => 1,
                    "products" => [
                        [
                            "id" => "test001",
                            "name" => "LinePay 測試商品",
                            "quantity" => 1,
                            "price" => 1,
                            'imageUrl' => 'https://static.iyp.tw/1/files/8dd7b8ab-1631-4548-b7b2-2a41b6d46326.jpeg'
                        ]
                    ]
                ]
            ],
            "redirectUrls" => [
                'confirmUrl' => 'http://localhost:8080/confirm',
                'cancelUrl' => 'https://developers-pay.line.me/zh',
                'confirmUrlType' => 'CLIENT',
            ]
        ];

        try {
            $response = $linePay->request($bodyParams);

            if (!$response->isSuccessful()) {
                $this->fail("LinePay request failed: " . $response->getReturnMessage() . " (Code: " . $response->getReturnCode() . ")");
            }

            // 1. Assert Payment URL
            $paymentUrl = $response->getPaymentUrl();
            $this->assertNotEmpty($paymentUrl, 'Payment URL is empty');
            $this->assertStringStartsWith('http', $paymentUrl, 'Payment URL does not look like a URL');

            // 2. Assert Transaction ID
            $info = $response->getInfo();
            $this->assertArrayHasKey('transactionId', $info, 'Response info is missing transactionId');
            $this->assertNotEmpty($info['transactionId'], 'Transaction ID is empty');

            echo "\nSuccess! Transaction ID: " . $info['transactionId'];
            echo "\nPayment URL: " . $paymentUrl . "\n";
            
            // Mac only: 自動開啟瀏覽器
            if (PHP_OS_FAMILY === 'Darwin') {
                echo "Opening browser...\n";
                exec("open '$paymentUrl'");
            }

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function testConfirm()
    {
        $linePay = new NICO0420\LinePay\Client([
            'channelId' => '2008797507',
            'channelSecret' => '4fee3891da49814582502cba0edfd1d5',
            'isSandbox' => true
        ]);

        $amount = 1;
        $transactionId = getenv('TRANSACTION_ID');
        if (!$transactionId) {
            // 嘗試從自動儲存的檔案讀取
            $lastIdFile = __DIR__ . '/last_transaction_id.txt';
            if (file_exists($lastIdFile)) {
                $lastId = trim(file_get_contents($lastIdFile));
                fwrite(STDOUT, "\n[自動偵測] 發現上次的 Transaction ID: {$lastId}\n");
                $input = $lastId; // 預設使用檔案中的 ID
            }
            
            if (empty($input)) {
                fwrite(STDOUT, "\n請輸入 Transaction ID (直接按 Enter 將使用預設值 2025123002331165010): ");
                $input = trim(fgets(STDIN));
            }
            
            $transactionId = $input ?: 2025123002331165010;
        }

        try {
            $response = $linePay->confirm($transactionId, [
                'amount' => $amount,
                'currency' => 'TWD'
            ]);

            // 如果原本就是已確認的交易(1172)，我們可以視為某種程度的通過(或至少印出訊息)，
            // 但標準測試應該要預期成功 '0000'。
            // 這裡我們嚴格檢查成功狀態。
            if (!$response->isSuccessful()) {
                 // 特殊處理：如果是 "Already confirmed"，我們或許不想讓 CI 失敗，但在開發時應該要知道。
                 $this->fail("LinePay confirm failed: " . $response->getReturnMessage() . " (Code: " . $response->getReturnCode() . ")");
            }

            // 驗證回傳資料
            $info = $response->getInfo();
            $this->assertNotEmpty($info, 'Response info is empty');
            $this->assertArrayHasKey('transactionId', $info, 'Response info is missing transactionId');
            
            // 驗證回傳的 ID 與我們送出的一致
            // 注意型別轉換，API 回傳可能是字串或數字
            $this->assertEquals($transactionId, $info['transactionId'], 'Transaction ID mismatch');

            echo "\nConfirm Success! Transaction ID: " . $info['transactionId'] . "\n";

            // 成功後清除暫存檔，避免下次誤用
            $lastIdFile = __DIR__ . '/last_transaction_id.txt';
            if (file_exists($lastIdFile)) {
                unlink($lastIdFile);
                echo "已自動清除暫存的 Transaction ID。\n";
            }

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
