<?php

use App\Models\Memo;
use App\Models\Leave;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/memo-attachments/{memo}', function (Memo $memo) {
    $user = request()->user();

    abort_unless($user, 403);
    abort_unless($user->role === 'hr' || (int) $user->employee?->id === (int) $memo->employee_id, 403);
    abort_if(blank($memo->attachment_path) || ! Storage::disk('local')->exists($memo->attachment_path), 404);

    return Storage::disk('local')->response($memo->attachment_path, $memo->attachment_name);
})
    ->middleware('auth')
    ->name('memo.attachments.show');

Route::get('/leave-attachments/{leave}', function (Leave $leave) {
    $user = request()->user();

    abort_unless($user, 403);
    abort_unless($user->role === 'hr' || (int) $user->employee?->id === (int) $leave->employee_id, 403);
    abort_if(blank($leave->attachment_path) || ! Storage::disk('local')->exists($leave->attachment_path), 404);

    return Storage::disk('local')->response($leave->attachment_path, $leave->attachment_name);
})
    ->middleware('auth')
    ->name('leave.attachments.show');

Route::redirect('/','/hr');
