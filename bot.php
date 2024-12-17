<?php

require 'vendor/autoload.php';

use TelegramBot\Api\Client;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;

// Логирование ошибок
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/bot_error.log');
error_reporting(E_ALL);

// Конфигурация базы данных
$host = 'localhost';
$db = 'akbastau';
$user = 'root';
$pass = 'your_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

function getUserByPhone($phone) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT fio FROM users WHERE phone = :phone");
    $stmt->execute(['phone' => $phone]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getFileByFio($fio) {
    $fileDir = __DIR__ . '/files/';
    $fileName = $fio . '.pdf';
    return file_exists($fileDir . $fileName) ? $fileDir . $fileName : null;
}

$botToken = '8022686995:AAHwhfNWHjswT27kL3q8R_fLyyh8pxem3pM';
$bot = new Client($botToken);

$keyboard = new ReplyKeyboardMarkup([["Расчетный лист"]], true, true);

// Обработчик /start
$bot->command('start', function ($message) use ($bot, $keyboard) {
    $chatId = $message->getChat()->getId();
    $bot->sendMessage($chatId, "Добро пожаловать в бот Акбастау!\n\nЕсли хотите получить расчетный лист — нажмите на кнопку ниже.", null, false, null, $keyboard);
});

// Обработчик кнопки "Расчетный лист"
$bot->on(function ($update) use ($bot) {
    $message = $update->getMessage();
    $text = $message->getText();
    $chatId = $message->getChat()->getId();

    if ($text === "Расчетный лист") {
        $bot->sendMessage($chatId, "Пожалуйста, отправьте ваш номер телефона в формате +77074794042.");
        return;
    }

    // Очистка номера телефона
    $cleanedPhone = preg_replace('/\D/', '', $text);
    if (preg_match('/^7[0-9]{10}$/', $cleanedPhone)) {
        $userPhone = '+' . $cleanedPhone;

        $user = getUserByPhone($userPhone);
        if ($user) {
            $filePath = getFileByFio($user['fio']);
            if ($filePath) {
                $bot->sendDocument($chatId, new CURLFile($filePath));
            } else {
                $bot->sendMessage($chatId, "Файл для вас не найден.");
            }
        } else {
            $bot->sendMessage($chatId, "Ваш номер телефона не найден в системе.");
        }
    } else {
        $bot->sendMessage($chatId, "Некорректный номер телефона. Пожалуйста, отправьте корректный номер в формате +77074794042.");
    }
}, function () {
    return true;
});

// Запуск бота
try {
    $bot->run();
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Произошла ошибка: " . $e->getMessage());
}

?>
