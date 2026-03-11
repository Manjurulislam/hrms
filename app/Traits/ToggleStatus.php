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

            $status = $model->status ? 'activated' : 'deactivated';

            return redirect()->back()->with('success', "Status {$status} successfully.");
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);
            return redirect()->back()->withErrors(['error' => 'Failed to update status.']);
        }
    }
}
