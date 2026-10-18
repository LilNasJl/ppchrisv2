<?php

use App\Http\Middleware\AuthenticateBiometricsSync;
use App\Jobs\ProcessBiometricsSync;
use App\Models\BiometricsSyncRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/biometrics-sync/callback', function (Request $request) {
    $data = $request->validate(['request_id' => 'required|uuid']);
    $sync = BiometricsSyncRequest::findOrFail($data['request_id']);
    ProcessBiometricsSync::dispatch($sync->id)->afterCommit();

    return response()->json(['accepted' => true], 202);
})->middleware([AuthenticateBiometricsSync::class, 'throttle:120,1']);
