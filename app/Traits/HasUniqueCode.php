<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait HasUniqueCode
{
    protected static function bootHasUniqueCode(): void
    {
        static::creating(function ($model) {
            $column = $model->getCodeColumn();
            if (empty($model->{$column})) {
                $model->{$column} = $model->generateUniqueCode();
            }
        });
    }

    public function getCodeColumn(): string
    {
        return property_exists($this, 'codeColumn') ? $this->codeColumn : 'code';
    }

    public function generateUniqueCode(): string
    {
        $attempts = 0;
        $column   = $this->getCodeColumn();

        do {
            $prefix = $this->codePrefix ?? '';

            // Add date prefix if enabled
            if ($this->includeDatePrefix ?? false) {
                $dateFormat = $this->dateFormat ?? 'ymd';
                $prefix     .= now()->format($dateFormat);
            }

            $length = $this->codeLength ?? 8;
            $random = $this->generateRandomString($length);
            $code   = $prefix . $random;
            $attempts++;

            if ($attempts > 10) {
                Log::error('Unable to generate unique code for ' . static::class);
                // Return longer fallback code instead of throwing exception
                return $prefix . $this->generateRandomString($length + 2);
            }
        } while (static::where($column, $code)->exists());

        return $code;
    }

    protected function generateRandomString(int $length): string
    {
        $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $result     = '';
        $maxIndex   = strlen($characters) - 1;

        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[random_int(0, $maxIndex)];
        }

        return $result;
    }
}
