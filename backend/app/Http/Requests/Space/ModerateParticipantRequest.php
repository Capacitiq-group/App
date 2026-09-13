<?php

namespace App\Http\Requests\Space;

use App\Rules\UserUuid;

class ModerateParticipantRequest extends AbstractSpaceRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->merge([
            'target_user_uuid' => $this->route('user_uuid'),
        ]);
    }

    /**
     * set rules
     */
    public function rules(): array
    {
        return $this->applyBaseRules(array_merge($this->spaceUuidRules(), [
            'target_user_uuid' => [
                self::REQUIRED,
                self::STRING,
                self::UUID,
                new UserUuid,
            ],
        ]));
    }
}
