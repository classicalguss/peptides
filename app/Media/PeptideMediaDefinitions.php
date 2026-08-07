<?php

namespace App\Media;

use Lunar\Base\StandardMediaDefinitions;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The Lunar defaults letterbox every conversion onto a white background, which
 * ruins the dark product renders. These conversions preserve aspect ratio and
 * keep the original transparent/black backdrop.
 */
class PeptideMediaDefinitions extends StandardMediaDefinitions
{
    protected array $conversions = [
        'small' => 400,
        'medium' => 700,
        'large' => 1100,
        'zoom' => 1600,
    ];

    public function registerMediaConversions(HasMedia $model, ?Media $media = null): void
    {
        $this->applyConversions($model);
    }

    protected function registerCollectionConversions(MediaCollection $collection, HasMedia $model): void
    {
        $collection->registerMediaConversions(function (Media $media) use ($model) {
            $this->applyConversions($model);
        });
    }

    protected function applyConversions(HasMedia $model): void
    {
        foreach ($this->conversions as $name => $width) {
            $model->addMediaConversion($name)
                ->fit(Fit::Max, $width, $width)
                ->keepOriginalImageFormat()
                ->optimize();
        }
    }
}
