<?php
// Подключение к базе данных
$dsn = 'mysql:host=localhost;dbname=u68648;charset=utf8';
$username = 'u68648';
$password = '7759086';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Ошибка подключения к базе данных: ' . $e->getMessage());
}

// Валидация данных
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ФИО
    $full_name = trim($_POST['full_name']);
    if (!preg_match('/^[а-яА-ЯёЁa-zA-Z\s-]+$/u', $full_name) || iconv_strlen($full_name, 'UTF-8') > 150) {
        $errors[] = 'ФИО должно содержать только буквы, пробелы и дефисы, не более 150 символов.';
    }    
    
    // Телефон
    $phone = trim($_POST['phone']);
    if (!preg_match('/^\+?\d{10,15}$/', $phone)) {
        $errors[] = 'Телефон должен содержать от 10 до 15 цифр, возможно с префиксом "+".';
    }

    // E-mail
    $email = trim($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный формат e-mail.';
    }

    // Дата рождения
    $birth_date = $_POST['birth_date'];
    $date = DateTime::createFromFormat('Y-m-d', $birth_date);
    if (!$date || $date->format('Y-m-d') !== $birth_date || $date > new DateTime()) {
        $errors[] = 'Некорректная дата рождения или дата в будущем.';
    }

    // Пол
    $gender = $_POST['gender'];
    if (!in_array($gender, ['male', 'female'])) {
        $errors[] = 'Некорректное значение пола.';
    }

    // Языки программирования
    $languages = isset($_POST['languages']) ? $_POST['languages'] : [];
    if (empty($languages)) {
        $errors[] = 'Выберите хотя бы один язык программирования.';
    } else {
        // Проверка допустимых значений языков
        $valid_languages = range(1, 12); // ID языков из таблицы programming_languages
        foreach ($languages as $lang) {
            if (!in_array((int)$lang, $valid_languages)) {
                $errors[] = 'Некорректный язык программирования.';
                break;
            }
        }
    }

    // Биография
    $biography = trim($_POST['biography']);
    if (empty($biography)) {
        $errors[] = 'Поле биографии не может быть пустым.';
    }

    // Контракт
    $contract_accepted = isset($_POST['contract_accepted']) ? 1 : 0;
    if (!$contract_accepted) {
        $errors[] = 'Необходимо согласиться с контрактом.';
    }

    // Если ошибок нет, сохраняем данные
    if (empty($errors)) {
        try {
            // Начинаем транзакцию
            $pdo->beginTransaction();

            // Вставка данных в таблицу applications
            $stmt = $pdo->prepare("
                INSERT INTO applications (full_name, phone, email, birth_date, gender, biography, contract_accepted)
                VALUES (:full_name, :phone, :email, :birth_date, :gender, :biography, :contract_accepted)
            ");
            $stmt->execute([
                'full_name' => $full_name,
                'phone' => $phone,
                'email' => $email,
                'birth_date' => $birth_date,
                'gender' => $gender,
                'biography' => $biography,
                'contract_accepted' => $contract_accepted,
            ]);

            // Получаем ID последней вставленной записи
            $application_id = $pdo->lastInsertId();

            // Вставка языков программирования в таблицу application_languages
            $stmt = $pdo->prepare("
                INSERT INTO application_languages (application_id, language_id)
                VALUES (:application_id, :language_id)
            ");
            foreach ($languages as $lang) {
                $stmt->execute([
                    'application_id' => $application_id,
                    'language_id' => (int)$lang,
                ]);
            }

            // Завершаем транзакцию
            $pdo->commit();

            // Успешное сообщение
            $success = 'Данные успешно сохранены!';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Ошибка при сохранении данных: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Результат обработки формы</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f4f4f4;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        .error {
            color: red;
            font-size: 14px;
            margin-top: 5px;
        }

        .success {
            color: green;
            font-size: 14px;
            margin-top: 5px;
            text-align: center;
        }

        a {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #007bff;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>Результат обработки формы</h1>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div class="success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <a href="index.html">Вернуться к форме</a>
</body>
</html>
