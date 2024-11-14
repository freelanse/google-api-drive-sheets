<?php
// Включение отображения ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Подключаем автозагрузчик Composer
require 'vendor/autoload.php';

use Google\Client;
use Google\Service\Drive;
use Google\Service\Sheets;

// Функция для получения MIME-типа файла
function getMimeType($filePath) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    return $mimeType;
}

// Генерация уникального ID
function generateUniqueId() {
    $idFile = 'last_id.txt';
    if (!file_exists($idFile)) {
        file_put_contents($idFile, 0);
    }
    $lastId = (int)file_get_contents($idFile);
    $newId = $lastId + 1;
    file_put_contents($idFile, $newId);
    return $newId;
}

try {
    // Настройки
    $spreadsheetId = 'your list id';
    $sheetName = 'Лист1';
    $uploadDir = 'uploads/';  // Временная директория для загрузки файлов

    // Создаем директорию для загрузок, если ее нет
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Авторизация и инициализация Google API клиента
    $client = new Client();
    $client->setApplicationName('Google Drive API PHP');
    $client->setScopes([Drive::DRIVE_FILE, Sheets::SPREADSHEETS]);
    $client->setAuthConfig(__DIR__ . '/cred_2.json'); // Путь к файлу на сервере
    $client->setAccessType('offline');

    // Проверка и обновление токенов
    $tokenPath = 'token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    if ($client->isAccessTokenExpired()) {
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            throw new Exception("Токен доступа истек, и нет refresh токена.");
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }

    $driveService = new Drive($client);
    $sheetsService = new Sheets($client);

    // Создание папки на Google Диске
    $fio = isset($_POST['fio']) ? $_POST['fio'] : 'Не указано';
    $fileMetadata = new Drive\DriveFile([
        'name' => $fio,
        'mimeType' => 'application/vnd.google-apps.folder',
        'parents' => ['your id folders parent']
    ]);
    $folder = $driveService->files->create($fileMetadata, [
        'fields' => 'id'
    ]);
    $folderId = $folder->id;
    $folderLink = "https://drive.google.com/drive/folders/$folderId?usp=sharing";

    // Обработка загруженных файлов и загрузка на Google Диск в созданную папку
    $uploadedFiles = [];
    foreach (['rezume', 'anketa', 'zadanie'] as $fileField) {
        if (isset($_FILES[$fileField]) && is_uploaded_file($_FILES[$fileField]['tmp_name'])) {
            $fileName = basename($_FILES[$fileField]['name']);
            $targetFilePath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES[$fileField]['tmp_name'], $targetFilePath)) {
                // Загрузка файла на Google Диск
                $fileMetadata = new Drive\DriveFile([
                    'name' => $fileName,
                    'parents' => [$folderId]
                ]);
                $content = file_get_contents($targetFilePath);
                $file = $driveService->files->create($fileMetadata, [
                    'data' => $content,
                    'mimeType' => getMimeType($targetFilePath),
                    'uploadType' => 'multipart',
                    'fields' => 'id'
                ]);
                $fileId = $file->id;
                $fileLink = "https://drive.google.com/file/d/$fileId/view?usp=sharing";
                $uploadedFiles[] = $fileLink;
            }
        }
    }

    // Формируем данные для отправки в Google Sheets
    $data = [
        'ID' => generateUniqueId(),
        'ФИО' => isset($_POST['fio']) ? $_POST['fio'] : 'Не указано',
        'Должность' => isset($_POST['position_select']) ? $_POST['position_select'] : 'Не указана',
        'Email' => isset($_POST['email']) ? $_POST['email'] : 'Не указан',
        'Телефон' => isset($_POST['phone']) ? $_POST['phone'] : 'Не указан',
        'Дата' => date('Y-m-d H:i:s'),
        'Файлы' => implode(", ", $uploadedFiles),
        'Ссылка на папку' => $folderLink
    ];

    // Функция для отправки данных в Google Sheets
    function sendDataToGoogleSheets($sheetsService, $spreadsheetId, $sheetName, $data) {
        $range = "$sheetName!A:H";
        $values = [array_values($data)];
        $body = new Sheets\ValueRange([
            'values' => $values
        ]);
        $params = [
            'valueInputOption' => 'RAW'
        ];
        $sheetsService->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
    }

    sendDataToGoogleSheets($sheetsService, $spreadsheetId, $sheetName, $data);

    echo "Данные успешно отправлены!";
} catch (Exception $e) {
    echo 'Ошибка: ',  $e->getMessage(), "\n";
}




?>
