<?php

namespace App\Http\Requests\Space;

class ResolveSpeakRequestRequest extends AbstractSpaceRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->merge([
            'speak_request_id' => $this->route('speak_request_id'),
        ]);
    }

    /**
     * set rules
     */
    public function rules(): array
    {
        return $this->applyBaseRules(array_merge($this->spaceUuidRules(), [
            'speak_request_id' => [
                self::REQUIRED,
                self::INTEGER,
                self::EXISTS.':space_speak_requests,id',
            ],
        ]));
    }
}
