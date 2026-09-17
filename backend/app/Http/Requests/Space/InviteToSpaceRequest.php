<?php

namespace App\Http\Requests\Space;

use App\Rules\UserUuid;

class InviteToSpaceRequest extends AbstractSpaceRequest
{
    /**
     * set rules
     */
    public function rules(): array
    {
        return $this->applyBaseRules(array_merge($this->spaceUuidRules(), [
            'invitee_user_uuid' => [
                self::REQUIRED,
                self::STRING,
                self::UUID,
                new UserUuid,
            ],
        ]));
    }
}
