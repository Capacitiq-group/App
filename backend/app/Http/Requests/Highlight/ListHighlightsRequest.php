<?php

namespace App\Http\Requests\Highlight;

use App\Http\Requests\BaseRequest;

class ListHighlightsRequest extends BaseRequest
{
    /**
     * set rules
     */
    public function rules(): array
    {
        return $this->applyBaseRules([
            'user_uuid' => [
                self::REQUIRED,
            ],
        ]);
    }
}
