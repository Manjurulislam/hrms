<?php

namespace App\Traits;

use App\Enums\CompanySetting;
use App\Models\Company;

trait CompanySettings
{
    protected function companySetting(?Company $company, string $key): mixed
    {
        $value = $company?->{$key};

        if ($value !== null) {
            return $value;
        }

        return CompanySetting::tryFrom($key)?->default();
    }
}
