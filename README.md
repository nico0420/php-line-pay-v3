# LINE Pay v3 PHP SDK (NICO0420)

一個簡單的 LINE Pay v3 PHP 封裝，提供付款請求、確認、退款、查詢、授權處理等常用 API。內建 Sandbox / Production 主機切換與簽章產生。

## 安裝

```bash
composer require nico0420/line-pay
```

## 快速開始

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use NICO0420\LinePay\Client;

$linePay = new Client([
    'channelId' => 'YOUR_CHANNEL_ID',
    'channelSecret' => 'YOUR_CHANNEL_SECRET',
    'isSandbox' => true, // 上線時請改成 false
]);

// 建立付款請求
$response = $linePay->request([
    'amount' => 1000,
    'currency' => 'TWD',
    'orderId' => 'ORDER-123',
    'packages' => [[
        'id' => 'PKG-1',
        'amount' => 1000,
        'products' => [[
            'id' => 'PROD-1',
            'name' => '測試商品',
            'quantity' => 1,
            'price' => 1000,
        ]],
    ]],
    'redirectUrls' => [
        'confirmUrl' => 'https://your.app/linepay/confirm',
        'cancelUrl' => 'https://your.app/linepay/cancel',
        'confirmUrlType' => 'CLIENT', // 預設 CLIENT，可依需求調整
    ],
]);

if ($response->isSuccessful()) {
    // 導向付款頁
    header('Location: ' . $response->getPaymentUrl());
    exit;
}

throw new \RuntimeException($response->getReturnMessage());
```

## 可用方法

- `request(array $bodyParams)`：建立付款請求。
- `confirm(int $transactionId, array $bodyParams)`：付款確認。
- `refund(int $transactionId, array $bodyParams = null)`：退款 / 部分退款。
- `details(array $queryParams)`：查詢交易紀錄。
- `check(int $transactionId)`：查詢付款請求狀態。
- `authorizationsCapture(int $transactionId, array $bodyParams)`：授權請款。
- `authorizationsVoid(int $transactionId, array $bodyParams = null)` / `void(...)`：取消授權。
- `preapproved(int $regKey, array $bodyParams = null)`：以 RegKey 直接扣款。
- `preapprovedCheck(int $regKey, array $queryParams = null)`：查詢 RegKey 狀態。
- `preapprovedExpire(int $regKey, array $bodyParams = null)`：使 RegKey 失效。

每個方法都會回傳 `NICO0420\LinePay\Response`：
- `isSuccessful()`：回傳是否為 `returnCode === '0000'`
- `getReturnCode()` / `getReturnMessage()`
- `getInfo()`、`getPaymentUrl($type = 'web')`
- `toArray()` / `toObject()` 取得完整回應

## 開發與測試

```bash
composer install
vendor/bin/phpunit
```

測試檔 `tests/Test.php` 示範了 request 與 confirm 的基本使用，已使用 Faker 生成隨機資料以避免真實敏感資訊。

## 需求

- PHP >= 5.5（依 composer.json）
- Guzzle 7.x

## 授權

GPL-2.0-only。詳見 `LICENSE`。
