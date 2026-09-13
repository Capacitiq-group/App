<?php

namespace App\Http\Requests\Space;

use App\Enums\Space\SpaceDiscoveryScopeEnum;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rules\Enum;

class CreateSpaceRequest extends BaseRequest
{
    /**
     * set rules
     */
    public function rules(): array
    {
        return $this->applyBaseRules([
            'title' => [
                self::REQUIRED,
                self::STRING,
                self::MAX.':100',
            ],
            'description' => [
                self::SOMETIMES,
                self::NULLABLE,
                self::STRING,
                self::MAX.':280',
            ],
            'topic_id' => [
                self::SOMETIMES,
                self::NULLABLE,
                self::INTEGER,
                self::EXISTS.':topics,id',
            ],
            'discovery_scope' => [
                self::SOMETIMES,
                new Enum(SpaceDiscoveryScopeEnum::class),
            ],
        ]);
    }
}
