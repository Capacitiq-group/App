<?php

namespace App\Http\Requests\Space;

use App\Http\Requests\BaseRequest;

class ListSpacesRequest extends BaseRequest
{
    /**
     * set rules
     */
    public function rules(): array
    {
        return $this->applyBaseRules([
            'topic_id' => [
                self::SOMETIMES,
                self::NULLABLE,
                self::INTEGER,
                self::EXISTS.':topics,id',
            ],
            'per_page' => [
                self::SOMETIMES,
                self::INTEGER,
                self::MIN.':1',
                self::MAX.':50',
            ],
        ]);
    }
}
