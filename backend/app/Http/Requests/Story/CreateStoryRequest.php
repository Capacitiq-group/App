<?php

namespace App\Http\Requests\Story;

use App\Enums\Media\MediaTypeEnum;
use App\Enums\Post\AudienceTypeEnum;
use App\Enums\Story\StoryTypeEnum;
use App\Http\Requests\BaseRequest;
use App\Rules\UploadFileId;
use Illuminate\Validation\Rules\Enum;

class CreateStoryRequest extends BaseRequest
{
    /**
     * set rules
     */
    public function rules(): array
    {
        $maxCarouselItems = (int) config('story.max_carousel_items');
        $minDuration = (int) config('story.min_duration_seconds');
        $maxDuration = (int) config('story.max_duration_seconds');

        return $this->applyBaseRules([
            'type' => [
                self::REQUIRED,
                new Enum(StoryTypeEnum::class),
            ],
            'audience' => [
                self::SOMETIMES,
                new Enum(AudienceTypeEnum::class),
            ],
            'duration_seconds' => [
                self::SOMETIMES,
                self::INTEGER,
                self::MIN.":{$minDuration}",
                self::MAX.":{$maxDuration}",
            ],

            // TEXT stories
            'text_content' => [
                self::REQUIRED_IF.':type,text',
                self::SOMETIMES,
                self::NULLABLE,
                self::STRING,
                self::MAX.':500',
            ],
            'background_color' => [
                self::SOMETIMES,
                self::NULLABLE,
                self::STRING,
                self::MAX.':20',
            ],

            // IMAGE/VIDEO stories (carousel-capable)
            'medias' => [
                self::REQUIRED_IF.':type,image,video',
                self::SOMETIMES,
                self::ARRAY,
                self::MIN.':1',
                self::MAX.":{$maxCarouselItems}",
            ],
            'medias.*.file_id' => [
                self::REQUIRED,
                self::INTEGER,
                new UploadFileId,
            ],
            'medias.*.type' => [
                self::REQUIRED,
                new Enum(MediaTypeEnum::class),
            ],
        ]);
    }
}
