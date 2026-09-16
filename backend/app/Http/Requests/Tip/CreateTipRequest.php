<?php

namespace App\Http\Requests\Tip;

use App\Enums\Tip\TipSourceTypeEnum;
use App\Http\Requests\BaseRequest;
use App\Rules\UserUuid;
use Illuminate\Validation\Rules\Enum;

class CreateTipRequest extends BaseRequest
{
    /**
     * set rules
     */
    public function rules(): array
    {
        $minAmount = (int) config('paystack.tips.min_amount_cents');

        return $this->applyBaseRules([
            'recipient_user_uuid' => [
                self::REQUIRED,
                self::STRING,
                self::UUID,
                new UserUuid,
            ],
            'callback_url' => [
                self::SOMETIMES,
                self::NULLABLE,
                self::STRING,
                self::MAX.':500',
            ],
            'amount_cents' => [
                self::REQUIRED,
                self::INTEGER,
                self::MIN.":{$minAmount}",
            ],
            'source_type' => [
                self::REQUIRED,
                new Enum(TipSourceTypeEnum::class),
            ],
            // Validated against the right table in the controller, since
            // which uuid space this belongs to depends on source_type.
            'source_uuid' => [
                self::REQUIRED_IF.':source_type,post,space',
                self::SOMETIMES,
                self::NULLABLE,
                self::STRING,
                self::UUID,
            ],
            'message' => [
                self::SOMETIMES,
                self::NULLABLE,
                self::STRING,
                self::MAX.':280',
            ],
        ]);
    }
}
