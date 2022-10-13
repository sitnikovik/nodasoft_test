<?php

namespace Gateway;

use PDO;

class User
{
    public const LIMIT = 10; // Константы пишутся капсом

    /**
     * @var PDO|null
     *
     * Должно инициализировано в при вызове методов, так как класс содержит только статику и нет конструктора
     */
    public static ?PDO $instance = null;

    /**
     * Реализация singleton
     * @return PDO
     */
    public static function getInstance(): PDO
    {
        if (is_null(self::$instance)) {
            self::$instance = new PDO(
                getenv('DATABASE_DSN'),
                getenv('DATABASE_USER'),
                getenv('DATABASE_PASSWORD')
            );
        }

        return self::$instance;
    }

    /**
     * Возвращает список пользователей старше заданного возраста.
     * @param int $ageFrom
     * @return array
     */
    public static function getUsersElderThan(int $ageFrom): array
    {
        // Названия таблиц и полей SQL лучше писать через snake_case, а в PHP через camelCase

        $stmt = self::getInstance()->prepare(
            "
            SELECT `id`, 
                   `name`, 
                   `last_name`, 
                   `from`, 
                   `age`, 
                   `settings`
            FROM `users`
            WHERE `age` > :ageFrom
            LIMIT :limit
        ");

        // Переменные нужно приводить к типу к типу
        $stmt->bindValue('ageFrom', $ageFrom, PDO::PARAM_INT);
        $stmt->bindValue('limit', self::LIMIT, PDO::PARAM_INT);

        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $users = [];
        foreach ($rows as $row) {
            $settings = json_decode($row['settings'], true) ?? []; // в случае неудачного декора будет false

            // Предполагаю, что id, name, last_name, from, age - NOT NULL в SQL
            $users[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'lastName' => $row['last_name'],
                'from' => $row['from'],
                'age' => $row['age'],
                'key' => $settings['key'] ?? null,
            ];
        }

        return $users;
    }

    /**
     * Возвращает пользователя по имени.
     * @param string $name
     * @return array
     */
    public static function getUserByName(string $name): array
    {
        $stmt = self::getInstance()->prepare("
            SELECT `id`,
                   `name`, 
                   `last_name`,
                   `from`, 
                   `age` 
            FROM `users` 
            WHERE name = :name
        ");

        // Здесь один строчный параметр. Можно и через execute забиндить (По умолчанию приведется как строка)
        $stmt->execute(['name' => $name]);

        $userByName = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'id' => $userByName['id'],
            'name' => $userByName['name'],
            'lastName' => $userByName['last_name'],
            'from' => $userByName['from'],
            'age' => $userByName['age'],
        ];
    }

    /**
     * Добавляет пользователя в базу данных.
     * @param string $name
     * @param string $lastName
     * @param int $age
     * @return int|null // предпочитаю, чтобы id всегда был PRIMARY KEY и AUTO_INCREMENT, но это не является истиной
     */
    public static function addUser(string $name, string $lastName, int $age): ?int
    {
        // По-хорошему, кол-во аргументов не должно превышать двух, а то и вовсе один

        // Используя такой запрос, необходимо, чтобы поля `from`, `settings` могли быть nullable
        // Или имели значение по умолчанию в противном случае - будет ошибка
        $query = "INSERT INTO `users` (`name`, `last_name`, `age`) VALUES (:name, :lastName, :age)";
        $stmt = self::getInstance()->prepare($query);
        $stmt->bindValue('age', $age, PDO::PARAM_INT);
        $stmt->bindValue('name', $name);
        $stmt->bindValue('lastName', $lastName);

        $stmt->execute();

        $id = intval(self::getInstance()->lastInsertId());

        return ($id !== 0) ? $id : null; // null = что-то пошло не так
    }
}