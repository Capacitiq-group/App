<?php

namespace App\Http\Resources\Api\Verification;

use App\Http\Resources\BaseJsonResource;

class VerificationApplicationResource extends BaseJsonResource
{
    /**
     * Set by the controller right after submit() — only present while an
     * application is still actionable (in_progress), so the client knows
     * where to send the user next.
     */
    public ?string $diditUrl = null;

    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'applicant_type' => $this->applicant_type->value,
            'billing_cycle' => $this->billing_cycle->value,
            'fee_cents' => $this->billing_cycle->feeCents(),
            'status' => $this->status->value,
            'decision_reason' => $this->when($this->status->value === 'failed', $this->decision_reason),
            'hold_expires_at' => $this->hold_expires_at?->toDateTimeString(),
            'submitted_at' => $this->submitted_at->toDateTimeString(),
            'decided_at' => $this->decided_at?->toDateTimeString(),
            'didit_url' => $this->when($this->diditUrl !== null, $this->diditUrl),
        ];
    }
}
