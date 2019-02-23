<?php

namespace App\Http\traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait ImageTrait
{
    /**
     * Get image by model.
     *
     * @param string $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getImage($id)
    {
        $path = $this->_model::findOrFail($id)->image;

        if (! Storage::exists($path)) {
            return response(null);
        }

        $type = Storage::mimeType($path);

        self::decodeImageToHtml($path);

        die;

        return response(file_get_contents($path))->header('Content-Type', $type);
    }

    /**
     * Set image by model.
     *
     * @param  string  $id
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setImage(Request $request, $id)
    {
        $this->validate($request, [
            'image' => 'required|file|mimes:jpeg,jpg,png|max:8000'
        ]);

        $file = $request->file('image');
        $model = $this->_model::findOrFail($id);
        $tableName = (new $this->_model)->getTable();

        if (Storage::exists($model->image)) {
            Storage::delete($model->image);
        }

        $md5 = md5($id);
        $f = substr($md5, 0, 3);
        $s = substr($md5, 3, 3);

        $uploadedUri = $file->storeAs(
            $tableName . '/avatars/' . $f . '/' . $s,
            str_replace('.', '_', uniqid('', true))
                . '.' . $file->getClientOriginalExtension()
        );

        if (! $uploadedUri) {
            return response()->json(['message' => 'Файл не зберігся'], 422);
        }

        $model->image = $uploadedUri;

        if (! $model->save()) {
            Storage::delete($uploadedUri);
            return response()->json(['message' => 'Помилка створення запису в БД'], 422);
        }

        self::decodeImageToHtml($model);

        return response()->json(['message' => 'Зображення збережено', 'image' => $model->image]);
    }

    /**
     * Delete avatar by model.
     *
     * @param string $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteImage($id)
    {
        $model = $this->_model::findOrFail($id);
        $deleted = true;

        if (Storage::exists($model->image)) {
            $deleted = Storage::delete($model->image);
        }

        if (! $deleted) {
            return response()->json(['message' => 'Зображення не вилучено'], 422);
        }

        $model->image = null;

        if (! $model->save()) {
            return response()->json(['message' => 'Помилка видалення зображення з БД'], 422);
        }

        return response()->json(['message' => 'Видалено зображення', 'deleted' => $deleted]);
    }

    /**
     * Get images from storage and decode it.
     *
     * @param  object  $image
     *
     * @return void
     */
    public static function decodeImageToHtml(&$image)
    {
        if ($image && Storage::exists($image)) {
            $image = 'data:image/jpeg;base64,' . base64_encode(Storage::get($image));
        }
    }
}
