<?php

namespace App\Rules;

use App\Repositories\StoryRepository;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StoryUuid implements ValidationRule
{
    private readonly StoryRepository $storyRepository;

    public function __construct()
    {
        $this->storyRepository = app()->make(StoryRepository::class);
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->storyRepository->query()->where('uuid', $value)->exists()) {
            $fail(':attribute must be a valid story uuid.');
        }
    }
}
