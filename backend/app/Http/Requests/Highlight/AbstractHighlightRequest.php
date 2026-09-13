<?php

namespace App\Http\Requests\Highlight;

use App\Http\Requests\BaseRequest;
use App\Rules\HighlightUuid;

abstract class AbstractHighlightRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->merge([
            'highlight_uuid' => $this->route('highlight_uuid'),
        ]);
    }

    protected function highlightUuidRules(): array
    {
        return [
            'highlight_uuid' => [
                self::REQUIRED,
                self::STRING,
                self::UUID,
                new HighlightUuid,
            ],
        ];
    }

    /**
     * set rules
     */
    public function rules(): array
    {
        return $this->applyBaseRules($this->highlightUuidRules());
    }
}
