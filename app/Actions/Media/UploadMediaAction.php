<?php

namespace App\Actions\Media;

use Illuminate\Database\Eloquent\Model;

class UploadMediaAction
{
    public function execute(Model $model, string $url)
    {
        $model
            ->addMedia($url)
            ->toMediaCollection('talk');

        // ->withResponsiveImages();
        return $model->getFirstMediaUrl('talk', 's3');
    }
}
