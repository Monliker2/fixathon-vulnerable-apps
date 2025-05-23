<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ModelController;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;

class ProfileController extends Controller
{
    public function __construct()
    {
        $token = request()->cookie('jwt');
        if(!empty($token)){
            request()->headers->set('Authorization', 'Bearer '. $token);
        }
        $this->middleware('auth:api')->except('load_profile_by_id');
    }

    public static function load_profile_by_id($id)
    {
        // Валидация ID
        if (!is_numeric($id) || $id <= 0) {
            return response()->json(['error' => 'Invalid profile ID'], 400);
        }

        $author = ModelController::get_user_by_id($id);

        if (empty($author)) {
            // Логируем только ID, не передаём пользовательские данные
            error_log("Unable to load profile by id", 3, '.' . DIRECTORY_SEPARATOR . 'phdays_log.txt');
            return response()->json(['error' => 'Profile not found'], 404);
        }
        $author = htmlspecialchars(array_shift($author)->username, ENT_QUOTES, 'UTF-8');

        $articles = ModelController::get_articles_by_user_id($id);

        $articles_save_data = [];
        if (!empty($articles)) {
            foreach ($articles as $article) {
                array_push($articles_save_data, [
                    'title' => htmlspecialchars($article->title, ENT_QUOTES, 'UTF-8'),
                    'content' => htmlspecialchars($article->content, ENT_QUOTES, 'UTF-8'),
                ]);
            }
        }

        return View::make('author_template')
            ->with('author', $author)
            ->with('articles', $articles_save_data);
    }

    public static function load_my_profile()
    {
        // Используем Laravel Auth вместо $_SESSION
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $author = htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8');
        $user_id = $user->id;

        $articles = ModelController::get_articles_by_user_id($user_id);
        $articles_save_data = [];
        if (!empty($articles)) {
            foreach ($articles as $article) {
                array_push($articles_save_data, [
                    'title' => htmlspecialchars($article->title, ENT_QUOTES, 'UTF-8'),
                    'content' => htmlspecialchars($article->content, ENT_QUOTES, 'UTF-8'),
                ]);
            }
        }

        $path_to_photo = URL::asset(PhotoController::get_path_to_photo_by_id($user_id));

        return View::make('my_profile', [
            'author' => $author,
            'articles' => $articles_save_data,
            'path_to_photo' => $path_to_photo
        ]);
    }
}
