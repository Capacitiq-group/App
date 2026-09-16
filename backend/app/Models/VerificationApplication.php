<?php

namespace App\Models;

use App\Enums\Verification\VerificationApplicantTypeEnum;
use App\Enums\Verification\VerificationBillingCycleEnum;
use App\Enums\Verification\VerificationHoldStatusEnum;
use App\Enums\Verification\VerificationStatusEnum;
use App\Traits\HasUuidObservable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VerificationApplication extends Model
{
    use HasUuidObservable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'applicant_type',
        'billing_cycle',
        'status',
        'didit_session_id',
        'didit_workflow_id',
        'paystack_reference',
        'paystack_subscription_code',
        'hold_status',
        'hold_expires_at',
        'decided_manually',
        'reviewed_by',
        'decision_reason',
        'decided_at',
    ];

    /**
     * The default attributes for the model.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'applicant_type' => 'individual',
        'billing_cycle' => 'monthly',
        'status' => 'in_progress',
        'decided_manually' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'applicant_type' => VerificationApplicantTypeEnum::class,
            'billing_cycle' => VerificationBillingCycleEnum::class,
            'status' => VerificationStatusEnum::class,
            'hold_status' => VerificationHoldStatusEnum::class,
            'hold_expires_at' => 'datetime',
            'decided_manually' => 'boolean',
            'decided_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * Get the applicant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who made the manual decision, if any.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The relationship instance.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the Individual-form details, if applicant_type is individual.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne The relationship instance.
     */
    public function individualDetails(): HasOne
    {
        return $this->hasOne(VerificationIndividualDetail::class);
    }

    /**
     * Get the Business-form details, if applicant_type is business.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne The relationship instance.
     */
    public function businessDetails(): HasOne
    {
        return $this->hasOne(VerificationBusinessDetail::class);
    }

    /**
     * Get the Political-entity-form details, if applicant_type is political_entity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne The relationship instance.
     */
    public function politicalDetails(): HasOne
    {
        return $this->hasOne(VerificationPoliticalDetail::class);
    }

    /**
     * Get this application's audit trail, oldest first.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany The relationship instance.
     */
    public function events(): HasMany
    {
        return $this->hasMany(VerificationEvent::class)->orderBy('created_at');
    }

    /**
     * Whether this application's hold has passed its expiry without a decision.
     */
    public function holdHasExpired(): bool
    {
        return $this->hold_expires_at !== null && $this->hold_expires_at->isPast();
    }

    /**
     * Scope a query to applications sitting under manual review — the admin
     * queue referenced throughout Section 4.2 of the platform rules.
     *
     * @param  Builder  $query  The query builder instance.
     * @return Builder The modified query builder instance.
     */
    #[Scope]
    public function underReview(Builder $query): Builder
    {
        return $query->where('status', VerificationStatusEnum::UNDER_REVIEW->value)
            ->orderBy('submitted_at');
    }
}
