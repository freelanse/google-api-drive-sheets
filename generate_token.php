<?php
require 'vendor/autoload.php';

use Google\Client;

$client = new Client();
$client->setApplicationName('Google Drive API PHP');
$client->setScopes([Google\Service\Drive::DRIVE_FILE, Google\Service\Sheets::SPREADSHEETS]);
$client->setAuthConfig(__DIR__ . '/cred_2.json');
$client->setAccessType('offline');

// URL-декодируйте ваш код авторизации
$authCode = urldecode('сюда вставить свой токен');

try {
    // Обмениваем код авторизации на токен доступа
    $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

    // Проверка, что токен получен корректно
    if (isset($accessToken['access_token'])) {
        echo "Токен получен успешно: " . json_encode($accessToken);

        // Сохраните токен в файл
        $tokenPath = 'token.json';
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($accessToken));
    } else {
        echo "Ошибка при получении токена: " . json_encode($accessToken);
    }
} catch (Exception $e) {
    echo 'Ошибка: ' . $e->getMessage();
}
?>
