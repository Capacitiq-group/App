<?php

namespace App\Http\Requests\Space;

use App\Http\Requests\BaseRequest;
use App\Rules\SpaceUuid;

abstract class AbstractSpaceRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->merge([
            'space_uuid' => $this->route('space_uuid'),
        ]);
    }

    /**
     * Rules shared by every Space request. Subclasses extending this should
     * call `array_merge($this->spaceUuidRules(), [...])` inside their own
     * applyBaseRules() call.
     */
    protected function spaceUuidRules(): array
    {
        return [
            'space_uuid' => [
                self::REQUIRED,
                self::STRING,
                self::UUID,
                new SpaceUuid,
            ],
        ];
    }

    /**
     * set rules
     */
    public function rules(): array
    {
        return $this->applyBaseRules($this->spaceUuidRules());
    }
}
