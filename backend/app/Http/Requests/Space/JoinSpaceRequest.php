<?php

namespace App\Http\Requests\Space;

class JoinSpaceRequest extends AbstractSpaceRequest
{
    /**
     * set rules
     */
    public function rules(): array
    {
        return $this->applyBaseRules(array_merge($this->spaceUuidRules(), [
            'invited_by_user_uuid' => [
                self::SOMETIMES,
                self::NULLABLE,
                self::STRING,
                self::UUID,
            ],
        ]));
    }
}
