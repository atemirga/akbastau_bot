<?php

require 'vendor/autoload.php';
use TelegramBot\Api\Client;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;

// Конфигурация базы данных
$host = 'localhost';
$db = 'akbastau';
$user = 'root';
$pass = 'your_password'; // Замените на ваш пароль

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

$botToken = '8022686995:AAHwhfNWHjswT27kL3q8R_fLyyh8pxem3pM'; // Укажите токен вашего бота
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
    $bot->sendMessage(
        $chatId, 
        "Добро пожаловать в бот Акбастау!\n\nЕсли хотите получить расчетный лист — нажмите на кнопку ниже.",
        null,
        false,
        null,
        $keyboard
    );
});

// Обработчик кнопки "Расчетный лист"
$bot->on(function ($update) use ($bot) {
    $message = $update->getMessage();
    $text = $message->getText();
    $chatId = $message->getChat()->getId();

    if ($text === "Расчетный лист") {
        $bot->sendMessage($chatId, "Пожалуйста, отправьте ваш номер телефона, чтобы мы могли найти расчетный лист.");
        return;
    }

    if (preg_match('/^\+7[0-9]{10}$/', $text)) { // Проверка формата телефона
        $userPhone = $text;

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
    } else {
        $bot->sendMessage($chatId, "Некорректный номер телефона. Пожалуйста, отправьте корректный номер.");
    }
}, function () {
    return true;
});

// Запуск бота
$bot->run();

?>
