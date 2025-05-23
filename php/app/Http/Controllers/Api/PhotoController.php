<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;

class PhotoController extends Controller
{
    public function __construct()
    {
        $token = request()->cookie('jwt');
        if(!empty($token)){
            request()->headers->set('Authorization', 'Bearer '.$token);
        }
        $this->middleware('auth:api')->except('get_path_to_photo_by_id');
    }

    public static function get_path_to_photo_by_id($id)
    {
        $path_to_custom_photo = implode(DIRECTORY_SEPARATOR, ['.', 'Photos', 'profile_photo' . $id . '.jpg']);
        $path_to_default_photo = implode(DIRECTORY_SEPARATOR, ['.', 'Photos', 'default_profile_photo.jpg']);
        return file_exists($path_to_custom_photo) ? $path_to_custom_photo : $path_to_default_photo;
    }

    public static function upload_photo_by_url()
    {
        $photo_url = request()->post('profile-photo-url');
        if(empty($photo_url)){
            return response()->json(['error' => 'No photo url provided'], 400);
        }

        // Проверяем, что это валидный URL
        if (!filter_var($photo_url, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'Invalid URL'], 400);
        }

        // Получаем содержимое файла
        $maxSize = 5 * 1024 * 1024;
        $context = stream_context_create(['http' => ['timeout' => 5]]);
        $image_content = @file_get_contents($photo_url, false, $context, 0, $maxSize + 1);
        if ($image_content === false) {
            return response()->json(['error' => 'Unable to download image'], 400);
        }
        if (strlen($image_content) > $maxSize) {
            return response()->json(['error' => 'Image too large'], 400);
        }

        // Проверяем MIME-тип
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($image_content);
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mimeType, $allowedMimeTypes)) {
            return response()->json(['error' => 'Invalid image type'], 400);
        }



        $tmpFile = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($tmpFile, $image_content);
        if (getimagesize($tmpFile) === false) {
            unlink($tmpFile);
            return response()->json(['error' => 'Corrupted or invalid image'], 400);
        }
        unlink($tmpFile);

        if(session_status() != PHP_SESSION_ACTIVE) {
            session_start();
        }

        $file_name = 'profile_photo' . $_SESSION['userId'] . '.jpg';
        $new_path_to_photo = '.' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'Photos' . DIRECTORY_SEPARATOR . $file_name;

        // Сохраняем файл
        if (file_put_contents($new_path_to_photo, $image_content) === false) {
            return response()->json(['error' => 'Failed to save image'], 500);
        }

        self::convert_image_to_jpg($new_path_to_photo, $file_name);

        header('Location:api/auth/my_profile');
        return response(status: 302);
    }

    public static function upload_photo()
    {
        $file_data = request()->file('profile-photo');
        if(empty($file_data)){
            return response()->json(['error' => 'No photo provided'], 400);
        }

        // Проверяем MIME-тип файла
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file_data->getMimeType(), $allowedMimeTypes)) {
            return response()->json(['error' => 'Invalid file type'], 400);
        }

        // Проверяем размер файла (например, не более 5 МБ)
        if ($file_data->getSize() > 5 * 1024 * 1024) {
            return response()->json(['error' => 'File too large'], 400);
        }

        if(session_status() != PHP_SESSION_ACTIVE) {
            session_start();
        }

        $file_name = 'profile_photo' . $_SESSION['userId'] . '.jpg';
        $new_path_to_photo = '.' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'Photos' . DIRECTORY_SEPARATOR . $file_name;

        // Перемещаем файл с проверкой результата
        if (!$file_data->move(dirname($new_path_to_photo), basename($new_path_to_photo))) {
            return response()->json(['error' => 'Failed to save file'], 500);
        }

        self::convert_image_to_jpg($new_path_to_photo, $file_name);

        header('Location:api/auth/my_profile');
        return response(status: 302);
    }

    private static function convert_image_to_jpg($path_to_image, $image_name)
    {
        $path_to_new_image = str_replace($image_name, 'new_' . $image_name, $path_to_image);

        // Экранируем аргументы для shell
        $safe_path_to_image = escapeshellarg($path_to_image);
        $safe_path_to_new_image = escapeshellarg($path_to_new_image);

        // Используем отдельные команды и экранируем аргументы
        exec("magick mogrify -format jpg $safe_path_to_image");
        exec("magick convert $safe_path_to_image -resize 200x200 $safe_path_to_new_image");
    }
}
