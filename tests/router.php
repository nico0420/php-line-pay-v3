<?php
// tests/router.php

if (preg_match('/^\/confirm/', $_SERVER["REQUEST_URI"])) {
    $id = $_GET['transactionId'] ?? 'Unknown';
    
    // 儲存到檔案，讓 Test.php 可以讀取
    file_put_contents(__DIR__ . '/last_transaction_id.txt', $id);

    echo "<!DOCTYPE html>";
    echo "<html><head><meta charset='utf-8'><title>LinePay Callback</title></head>";
    echo "<body style='font-family: sans-serif; text-align: center; padding: 50px;'>";
    echo "<h1>✅ 授權成功！</h1>";
    echo "<p>Transaction ID: <strong style='font-size: 2em; color: green;'>{$id}</strong></p>";
    echo "<p>這筆單號已經自動儲存。</p>";
    echo "<p>您現在可以回到 Terminal 執行：<br><br><code>vendor/bin/phpunit --filter testConfirm</code></p>";
    echo "</body></html>";
    exit;
}

return false; // let expected files serve as-is
