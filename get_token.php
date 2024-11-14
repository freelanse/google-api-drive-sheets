<?php
require 'vendor/autoload.php';

use Google\Client;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$client = new Client();
$client->setApplicationName('Google Drive API PHP');
$client->setScopes([Google\Service\Drive::DRIVE_FILE, Google\Service\Sheets::SPREADSHEETS]);
$client->setAuthConfig(__DIR__ . '/cred_2.json'); // Путь к файлу на сервере
$client->setAccessType('offline');
$client->setPrompt('select_account consent');

$tokenPath = 'token.json';

// Проверяем наличие сохраненного токена
if (file_exists($tokenPath)) {
    $accessToken = json_decode(file_get_contents($tokenPath), true);
    $client->setAccessToken($accessToken);
}

// Если токен недействителен или отсутствует, получаем новый
if ($client->isAccessTokenExpired()) {
    if ($client->getRefreshToken()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    } else {
        // Если нет refresh token, запрашиваем новый токен
        $authUrl = $client->createAuthUrl();
        printf('<a href="%s" target="_blank">Откройте эту ссылку для аутентификации</a><br>', $authUrl);

        // Если код подтверждения был отправлен через форму
        if (isset($_POST['auth_code'])) {
            $authCode = trim($_POST['auth_code']);

            // Обмениваем код на токен доступа
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Проверяем наличие ошибок
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }

            // Попытка сохранить токен
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }

            // Проверка записи токена
            if (file_put_contents($tokenPath, json_encode($client->getAccessToken())) === false) {
                echo "Ошибка при сохранении токена в 'token.json'. Проверьте права на запись.";
            } else {
                echo "Токен успешно получен и сохранен в 'token.json'.";
            }
        } else {
            // Показываем форму для ввода кода
            echo '<form method="POST">
                    <label for="auth_code">Введите код верификации:</label>
                    <input type="text" name="auth_code" id="auth_code">
                    <input type="submit" value="Отправить">
                  </form>';
        }
    }
}
?>
