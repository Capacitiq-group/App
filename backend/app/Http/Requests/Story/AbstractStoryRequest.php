<?php

namespace App\Http\Requests\Story;

use App\Http\Requests\BaseRequest;
use App\Rules\StoryUuid;

abstract class AbstractStoryRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->merge([
            'story_uuid' => $this->route('story_uuid'),
        ]);
    }

    protected function storyUuidRules(): array
    {
        return [
            'story_uuid' => [
                self::REQUIRED,
                self::STRING,
                self::UUID,
                new StoryUuid,
            ],
        ];
    }

    /**
     * set rules
     */
    public function rules(): array
    {
        return $this->applyBaseRules($this->storyUuidRules());
    }
}
