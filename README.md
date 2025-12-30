# Line Pay SDK for PHP (v3)

這是一個簡單易用的 Line Pay V3 API PHP 套件。

## 需求 (Requirements)

- PHP >= 7.4 或 PHP >= 8.0
- Composer

## 安裝 (Installation)

```bash
composer require nico0420/line-pay
```

## 快速開始 (Quick Start)

### 1. 初始化 Client

```php
use NICO0420\LinePay\Client;

$linePay = new Client([
    'channelId' => 'YOUR_CHANNEL_ID',
    'channelSecret' => 'YOUR_CHANNEL_SECRET',
    'isSandbox' => true, // 正式環境請設為 false
]);
```

### 2. 請求付款 (Request Payment)

```php
$response = $linePay->request([
    'amount' => 100,
    'currency' => 'TWD',
    'orderId' => 'ORDER_20250101_001',
    'packages' => [
        [
            'id' => 'PKG_1',
            'amount' => 100,
            'products' => [
                [
                    'name' => '測試商品',
                    'quantity' => 1,
                    'price' => 100,
                    'imageUrl' => 'https://example.com/image.jpg'
                ]
            ]
        ]
    ],
    'redirectUrls' => [
        'confirmUrl' => 'https://your-domain.com/pay/confirm',
        'cancelUrl' => 'https://your-domain.com/pay/cancel',
    ]
]);

if ($response->isSuccessful()) {
    $paymentUrl = $response->getPaymentUrl();
    // 導引使用者前往付款頁面
    header("Location: $paymentUrl");
}
```

### 3. 確認付款 (Confirm Payment)

當使用者付款完成後，Line Pay 會導回 `confirmUrl` 並帶上 `transactionId`。

```php
$transactionId = $_GET['transactionId']; // 從 Query String 取得

$response = $linePay->confirm($transactionId, [
    'amount' => 100,
    'currency' => 'TWD',
]);

if ($response->isSuccessful()) {
    echo "付款成功！交易編號: " . $response->info['transactionId'];
} else {
    echo "付款失敗: " . $response->getReturnMessage();
}
```

---

## 開發與測試 (Development & Testing)

本套件包含了一套完整的測試工作流，方便您在本機進行沙盒測試。

### 步驟 1: 啟動本機測試伺服器

請開啟一個新的 Terminal 視窗，執行：

```bash
php -S localhost:8080 -t tests/ tests/router.php
```

> 這個伺服器會負責接收 Line Pay 的 Callback，並自動儲存 Transaction ID。

### 步驟 2: 執行付款請求測試

在原本的 Terminal 執行：

```bash
vendor/bin/phpunit --filter testRequest
```

執行後：

1.  程式會自動開啟瀏覽器前往 Line Pay 付款頁面。
2.  請登入 Line 並完成付款動作。
3.  付款完成後，瀏覽器會跳轉到 `localhost:8080` 並顯示綠色成功畫面。

### 步驟 3: 執行確認付款測試

接著直接執行：

```bash
vendor/bin/phpunit --filter testConfirm
```

1.  程式會自動讀取剛剛暫存的 `Transaction ID`。
2.  執行確認請款 API。
3.  驗證成功後，會自動清除暫存檔。

### 手動指定 Transaction ID

如果您想手動輸入 ID 進行測試：

```bash
TRANSACTION_ID=20210322... vendor/bin/phpunit --filter testConfirm
```

## License

GPL
