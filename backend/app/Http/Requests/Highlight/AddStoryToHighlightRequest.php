<?php

namespace App\Http\Requests\Highlight;

use App\Rules\StoryUuid;

class AddStoryToHighlightRequest extends AbstractHighlightRequest
{
    /**
     * set rules
     */
    public function rules(): array
    {
        return $this->applyBaseRules(array_merge($this->highlightUuidRules(), [
            'story_uuid' => [
                self::REQUIRED,
                self::STRING,
                self::UUID,
                new StoryUuid,
            ],
        ]));
    }
}
