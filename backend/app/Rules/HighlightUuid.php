<?php

namespace App\Rules;

use App\Repositories\HighlightRepository;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HighlightUuid implements ValidationRule
{
    private readonly HighlightRepository $highlightRepository;

    public function __construct()
    {
        $this->highlightRepository = app()->make(HighlightRepository::class);
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->highlightRepository->query()->where('uuid', $value)->exists()) {
            $fail(':attribute must be a valid highlight uuid.');
        }
    }
}
