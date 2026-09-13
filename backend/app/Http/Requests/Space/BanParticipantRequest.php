<?php

namespace App\Http\Requests\Space;

class BanParticipantRequest extends ModerateParticipantRequest
{
    /**
     * set rules
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), $this->applyBaseRules([
            'reason' => [
                self::SOMETIMES,
                self::NULLABLE,
                self::STRING,
                self::MAX.':500',
            ],
        ]));
    }
}
