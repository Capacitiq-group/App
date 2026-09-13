<?php

namespace App\Http\Requests\Highlight;

use App\Rules\UploadFileId;

class UpdateHighlightRequest extends AbstractHighlightRequest
{
    /**
     * set rules
     */
    public function rules(): array
    {
        $maxTitle = (int) config('story.max_title_length');

        return $this->applyBaseRules(array_merge($this->highlightUuidRules(), [
            'title' => [
                self::SOMETIMES,
                self::STRING,
                self::MAX.":{$maxTitle}",
            ],
            'cover_file_id' => [
                self::SOMETIMES,
                self::NULLABLE,
                self::INTEGER,
                new UploadFileId,
            ],
        ]));
    }
}
