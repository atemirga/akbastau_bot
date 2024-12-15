<?php

require 'vendor/autoload.php';
use TelegramBot\Api\Client;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use PDO;

// Конфигурация базы данных
$host = 'localhost';
$db = 'aqbastay';
$user = 'root';
$pass = 'password'; // Замените на ваш пароль

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
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ? $user : null;
}

function getFileByFio($fio) {
    $fileDir = __DIR__ . '/files/';
    $fileName = $fio . '.pdf';
    $filePath = $fileDir . $fileName;

    return file_exists($filePath) ? $filePath : null;
}

$botToken = 'your_telegram_bot_token'; // Укажите токен вашего бота
$bot = new Client($botToken);

// Клавиатура
$keyboard = new ReplyKeyboardMarkup(
    [["Расчетный лист"]], // Кнопки
    true, // Сжать клавиатуру
    true  // Сделать клавиатуру постоянной
);

// Обработчик команды /start
$bot->command('start', function ($message) use ($bot, $keyboard) {
    $chatId = $message->getChat()->getId();
    $bot->sendMessage($chatId, "Привет! Это бот для получения расчетных листов.\n\nНажмите 'Расчетный лист'.", null, false, null, $keyboard);
});

// Обработчик кнопки "Расчетный лист"
$bot->on(function ($update) use ($bot) {
    $message = $update->getMessage();
    $text = $message->getText();
    $chatId = $message->getChat()->getId();

    if ($text === "Расчетный лист") {
        $userPhone = null; // Здесь вы можете реализовать сопоставление chat_id -> номер телефона, если оно требуется.

        if (!$userPhone) {
            $bot->sendMessage($chatId, "Вы не авторизованы. Отправьте ваш номер телефона.");
            return;
        }

        // Получаем пользователя по номеру телефона
        $user = getUserByPhone($userPhone);
        if ($user) {
            // Путь к файлу
            $filePath = getFileByFio($user['fio']);
            if ($filePath) {
                $bot->sendDocument($chatId, new CURLFile($filePath));
            } else {
                $bot->sendMessage($chatId, "Файл для вас не найден.");
            }
        } else {
            $bot->sendMessage($chatId, "Ваш номер телефона не найден в системе.");
        }
    }
}, function () {
    return true;
});

// Запуск бота
$bot->run();

?>
