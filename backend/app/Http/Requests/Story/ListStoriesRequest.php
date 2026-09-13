<?php

namespace App\Http\Requests\Story;

use App\Http\Requests\BaseRequest;

class ListStoriesRequest extends BaseRequest
{
    /**
     * set rules
     */
    public function rules(): array
    {
        return $this->applyBaseRules([
            'user_uuid' => [
                self::SOMETIMES,
                self::NULLABLE,
            ],
        ]);
    }
}
