<?php

namespace App\Http\Controllers;

use PDO;

class ModelController extends Controller
{
    public static function add_article($title, $content, $userId)
    {
        self::query(
            "INSERT INTO articles (title, content, userId) VALUES (:title, :content, :userId);",
            [
                ':title' => $title,
                ':content' => $content,
                ':userId' => $userId
            ]
        );
    }

    public static function get_all_articles()
    {
        return self::query('SELECT * FROM articles');
    }

    public static function get_articles_by_user_id($user_id)
    {
        return self::query(
            'SELECT * FROM articles WHERE userId = :userId',
            [':userId' => $user_id]
        );
    }

    public static function get_article_by_article_id($article_id)
    {
        return self::query(
            'SELECT * FROM articles WHERE articleId = :articleId',
            [':articleId' => $article_id]
        );
    }

    public static function get_articles_by_keyword($keyword)
    {
        return self::query(
            'SELECT * FROM articles WHERE title LIKE :keyword OR content LIKE :keyword',
            [':keyword' => "%$keyword%"]
        );
    }

    public static function get_user_by_id($id)
    {
        $result = self::query(
            'SELECT * FROM users WHERE id = :userId',
            [':userId' => $id]
        );
        return is_array($result) ? $result : [];
    }

    public static function get_user_id_by_username($username)
    {
        return self::query(
            'SELECT id FROM users WHERE username = :username',
            [':username' => $username]
        );
    }

    private static function query($query, array $bindings = [], bool $execute = true)
    {
        $connection = new PDO('sqlite:' . implode(DIRECTORY_SEPARATOR, ['.', 'database', 'database.sqlite']));
        $statement = $connection->prepare($query);
        if ($execute) {
            $statement->execute($bindings);
        } else {
            $statement->execute();
        }
        return $statement->fetchAll(PDO::FETCH_CLASS);
    }
}