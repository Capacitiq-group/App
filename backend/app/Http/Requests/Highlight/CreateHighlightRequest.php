<?php

namespace App\Http\Requests\Highlight;

use App\Http\Requests\BaseRequest;
use App\Rules\StoryUuid;
use App\Rules\UploadFileId;

class CreateHighlightRequest extends BaseRequest
{
    /**
     * set rules
     */
    public function rules(): array
    {
        $maxTitle = (int) config('story.max_title_length');

        return $this->applyBaseRules([
            'title' => [
                self::REQUIRED,
                self::STRING,
                self::MAX.":{$maxTitle}",
            ],
            'cover_file_id' => [
                self::SOMETIMES,
                self::NULLABLE,
                self::INTEGER,
                new UploadFileId,
            ],
            'story_uuids' => [
                self::SOMETIMES,
                self::ARRAY,
            ],
            'story_uuids.*' => [
                self::STRING,
                self::UUID,
                new StoryUuid,
            ],
        ]);
    }
}
