<?php

namespace App\Traits;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

trait ToggleStatus
{
    public function toggleModelStatus($model): RedirectResponse
    {
        try {
            $model->status = !$model->status;
            $model->save();
            return redirect()->back();
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back();
        }
    }
}
