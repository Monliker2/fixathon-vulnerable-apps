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
        // Валидация id
        $safeId = filter_var($id, FILTER_VALIDATE_INT);
        if ($safeId === false) {
            error_log("Invalid profile id", 3, '.' . DIRECTORY_SEPARATOR . 'phdays_log.txt');
            return response()->json(['error' => 'Invalid profile id'], 400);
        }

        $safeId = htmlspecialchars($safeId, ENT_QUOTES, 'UTF-8');

        $author = ModelController::get_user_by_id($safeId);

        if(empty($author)){
            error_log("Unable to load profile by id $safeId", 3, '.' . DIRECTORY_SEPARATOR . 'phdays_log.txt');
            return response()->json(['error' => 'Profile not found'], 404);
        }
        $author = array_shift($author)->username;

        $articles = ModelController::get_articles_by_user_id($safeId);

        $articles_save_data = [];
        if(!empty($articles))
        {
            foreach($articles as $article)
            {
                array_push($articles_save_data, [
                    'title' => $article->title,
                    'content' => $article->content,
                ]);
            }
        }

        return View::make('author_template')
            ->with('author', $author)
            ->with('articles', $articles_save_data);
    }

    public static function load_my_profile()
    {
        if(session_status() != PHP_SESSION_ACTIVE) {
            session_start();
        }

        $author = $_SESSION['username'] ?? null;
        $user_id = $_SESSION['userId'] ?? null;

        if (!$author || !$user_id) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $articles = ModelController::get_articles_by_user_id($user_id);
        $articles_save_data = [];
        if(!empty($articles))
        {
            foreach($articles as $article)
            {
                array_push($articles_save_data, [
                    'title' => $article->title,
                    'content' => $article->content,
                ]);
            }
        }

        $path_to_photo = URL::asset(PhotoController::get_path_to_photo_by_id($user_id));

        return View::make('my_profile', [
            'author' => $author,
            'articles' => $articles_save_data,
            'path_to_photo' => $path_to_photo]);
    }
}