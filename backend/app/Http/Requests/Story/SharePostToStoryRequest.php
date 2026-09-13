<?php

namespace App\Http\Requests\Story;

use App\Enums\Post\AudienceTypeEnum;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rules\Enum;

class SharePostToStoryRequest extends BaseRequest
{
    /**
     * set rules
     */
    public function rules(): array
    {
        return $this->applyBaseRules([
            'post_uuid' => [
                self::REQUIRED,
            ],
            'text_content' => [
                self::SOMETIMES,
                self::NULLABLE,
                self::STRING,
                self::MAX.':280',
            ],
            'audience' => [
                self::SOMETIMES,
                new Enum(AudienceTypeEnum::class),
            ],
        ]);
    }
}
