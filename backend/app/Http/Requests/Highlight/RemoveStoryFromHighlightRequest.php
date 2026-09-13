<?php

namespace App\Http\Requests\Highlight;

use App\Rules\StoryUuid;

class RemoveStoryFromHighlightRequest extends AbstractHighlightRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->merge([
            'story_uuid' => $this->route('story_uuid'),
        ]);
    }

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
