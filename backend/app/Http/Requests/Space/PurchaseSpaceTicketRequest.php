<?php

namespace App\Http\Requests\Space;

class PurchaseSpaceTicketRequest extends AbstractSpaceRequest
{
    /**
     * set rules
     */
    public function rules(): array
    {
        return $this->applyBaseRules(array_merge($this->spaceUuidRules(), [
            'callback_url' => [
                self::SOMETIMES,
                self::NULLABLE,
                self::STRING,
                self::MAX.':500',
            ],
        ]));
    }
}
