<?php
// Соединение с базой данных
$host = 'localhost';
$dbname = 'u68645'; // Название базы данных
$username = 'u68645'; // Логин пользователя MySQL
$password = '4979729'; // Пароль пользователя MySQL

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Валидация данных
    $errors = [];

    // Проверяем ФИО
    if (empty($_POST['fio']) || !preg_match("/^[a-zA-Zа-яА-Я\s]+$/", $_POST['fio'])) {
        $errors[] = 'Неверный формат ФИО. Оно должно содержать только буквы и пробелы.';
    }

    // Проверяем телефон
    if (!empty($_POST['phone']) && !preg_match("/^\+?[0-9]{1,15}$/", $_POST['phone'])) {
        $errors[] = 'Неверный формат телефона.';
    }

    // Проверяем email
    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Неверный формат e-mail.';
    }

    // Проверяем пол
    if (empty($_POST['gender']) || !in_array($_POST['gender'], ['male', 'female'])) {
        $errors[] = 'Выберите пол.';
    }

    // Проверяем выбранные языки программирования
    if (empty($_POST['languages'])) {
        $errors[] = 'Выберите хотя бы один язык программирования.';
    }

    // Проверяем биографию
    if (empty($_POST['biography']) || strlen($_POST['biography']) < 10) {
        $errors[] = 'Биография должна содержать хотя бы 10 символов.';
    }

    // Проверка на принятие контракта
    if (!isset($_POST['contract_accepted'])) {
        $errors[] = 'Вы должны подтвердить ознакомление с контрактом.';
    }

    // Если есть ошибки, выводим их и прекращаем выполнение
    if ($errors) {
        echo '<ul>';
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo '</ul>';
        exit();
    }

    // Если ошибок нет, сохраняем данные в базу данных
    try {
        // Вставка данных в таблицу application
        $stmt = $pdo->prepare("INSERT INTO application (fio, phone, email, birthdate, gender, biography, contract_accepted) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['fio'], $_POST['phone'], $_POST['email'], $_POST['birthdate'], $_POST['gender'], $_POST['biography'], isset($_POST['contract_accepted']) ? 1 : 0
        ]);

        // Получаем ID последней вставленной записи
        $application_id = $pdo->lastInsertId();

        // Вставляем выбранные языки программирования в таблицу связи
        foreach ($_POST['languages'] as $language_id) {
            $stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            $stmt->execute([$application_id, $language_id]);
        }

        echo 'Данные успешно сохранены!';
    } catch (PDOException $e) {
        echo 'Ошибка при сохранении данных: ' . $e->getMessage();
    }
}
?>