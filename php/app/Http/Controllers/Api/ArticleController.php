<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ModelController;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\View;

class ArticleController extends Controller
{
    public function __construct()
    {
        $token = request()->cookie('jwt');
        if(!empty($token)){
            request()->headers->set('Authorization', 'Bearer '.$token);
        }
        $this->middleware('auth:api')->except('get_all_articles', 'get_articles_by_keyword', 'load_article_by_id');
    }

    public static function get_all_articles()
    {
        $articles = ModelController::get_all_articles();

        $result = [];
        foreach ($articles as $article)
        {
            $user = ModelController::get_user_by_id($article->userId);
            if(is_array($user) && !empty($user)) {
                $username = array_shift($user)->username;
            } else {
                // Handle the case where user is not found
                $username = 'Unknown';
            }
            array_push($result, [
                'title' => $article->title,
                'content' => $article->content,
                'author' => $username,
                'authorProfileUrl' => '/profile/' . $article->userId
            ]);
        }
        return response()->json($result);
    }

    public static function get_articles_by_keyword()
    {
        $search = request()->input('search');

        if(empty($search)) {
            return response()->json(['error' => 'Keyword expected'], 400);
        }

        $articles = ModelController::get_articles_by_keyword($search);

        $result = [];
        foreach ($articles as $article)
        {
            $author = ModelController::get_user_by_id($article->userId);
            if(is_array($author) && !empty($author)) {
                $authorName = array_shift($author)->username;
            } else {
                // Handle the case where author is not found
                $authorName = 'Unknown';
            }
            array_push($result, [
                'title' => $article->title,
                'content' => $article->content,
                'author' => $authorName,
                'id' => $article->articleId,
            ]);
        }
        return response()->json($result);
    }

    public static function load_article_by_id($id)
    {
        $article = ModelController::get_article_by_article_id($id);
        $article = array_shift($article);
        $user = ModelController::get_user_by_id($article->userId);
        $author = ModelController::get_user_by_id($article->userId);
        if(is_array($user) && !empty($user)) {
            $username = array_shift($user)->username;
        } else {
            // Handle the case where user is not found
            $username = 'Unknown';
        }
        if(is_array($author) && !empty($author)) {
            $authorName = array_shift($author)->username;
        } else {
            // Handle the case where author is not found
            $authorName = 'Unknown';
        }
        return View::make('article_template')
            ->with('title', $article->title)
            ->with('author_id', $article->userId)
            ->with('author', $username)
            ->with('content', $article->content);
    }

    public static function create_article()
    {
        $title = request('title');
        $content = request('content');

        if(!$title || !$content) {
            return response()->json(['error' => 'title and content expected'], 400);
        }

        if(session_status() != PHP_SESSION_ACTIVE) {
            session_start();
        }
        $user_id = $_SESSION['userId'];

        ModelController::add_article($title, $content, $user_id);

        $redirect_to = request('redirect_to');
        if(empty($redirect_to)){
            $redirect_to = '/';
        }

        header('Location:' . $redirect_to);
        return response(status: 302);
    }
}
