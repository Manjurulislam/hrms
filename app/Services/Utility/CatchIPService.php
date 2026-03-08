<?php

namespace App\Services\Utility;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CatchIPService
{
    public function getPublicIp(): ?string
    {
        try {
            $response = Http::get('https://api.ipify.org');
            return $response->body();
        } catch (Exception $e) {
            Log::error('Failed to get public IP: ' . $e->getMessage());
            return null;
        }
    }


}
