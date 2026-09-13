<?php

namespace App\Rules;

use App\Repositories\SpaceRepository;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SpaceUuid implements ValidationRule
{
    private readonly SpaceRepository $spaceRepository;

    public function __construct()
    {
        $this->spaceRepository = app()->make(SpaceRepository::class);
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->spaceRepository->query()->where('uuid', $value)->exists()) {
            $fail(':attribute must be a valid space uuid.');
        }
    }
}
