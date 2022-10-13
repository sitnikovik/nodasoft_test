<?php

namespace Manager;

use Exception;

class User
{
    /**
     * Возвращает пользователей старше заданного возраста.
     * @param int $ageFrom
     * @return array
     */
    public static function getUsersElderThan(int $ageFrom): array
    {
        return \Gateway\User::getUsersElderThan($ageFrom);
    }

    /**
     * Возвращает пользователей по списку имен.
     * @param array $names
     * @return array
     */
    public static function getUsersByNames(array $names): array
    {
        $users = [];
        foreach ($names as $name) {
            // Не очень то, что каждую итерацию идет отдельный запрос в БД
            // Правильнее было бы получить все записи в одном запросе, сгруппировывав их по `name`
            $users[] = \Gateway\User::getUserByName($name);
        }

        return $users;
    }

    /**
     * Добавляет пользователей в базу данных.
     * @param array $users
     * @return array
     */
    public static function addUsers(array $users): array
    {
        $ids = [];
        \Gateway\User::getInstance()->beginTransaction();
        try {
            // Сначала формируем запросы на добавление записей
            foreach ($users as $user) {
                // Предполагаю, что перед вызовом метода $users прошли санацию на актуальность передаваемых параметров
                $ids[] = \Gateway\User::addUser($user['name'], $user['lastName'], $user['age']);
            }
            // а в конце уже коммит
            \Gateway\User::getInstance()->commit();
        } catch (Exception $e) {
            \Gateway\User::getInstance()->rollBack();
        }

        return $ids;
    }
}