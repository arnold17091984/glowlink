<?php

declare(strict_types=1);

namespace App\Filament\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DateTimeRules implements ValidationRule
{
    /**
     * @param  Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public int $interval;

    public function __construct(int $interval)
    {
        $this->interval = $interval;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $dateTime = Carbon::parse($value);

        if ($dateTime->lessThanOrEqualTo(Carbon::now())) {
            $fail('The date or time must be greater than the current date time.');
        }

        if ($dateTime->minute % $this->interval !== 0) {
            $fail('The time must be divisible by '.$this->interval.'.');
        }
    }
}
