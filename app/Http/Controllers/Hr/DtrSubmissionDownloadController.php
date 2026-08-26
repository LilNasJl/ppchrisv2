<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\DtrSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DtrSubmissionDownloadController extends Controller
{
    public function __invoke(Request $request, DtrSubmission $submission)
    {
        $this->authorizeAndMarkViewed($request, $submission);

        return Storage::disk('local')->download($submission->file_path, $submission->file_name);
    }

    public function view(Request $request, DtrSubmission $submission)
    {
        $this->authorizeAndMarkViewed($request, $submission);

        return Storage::disk('local')->response(
            $submission->file_path,
            $submission->file_name,
        );
    }

    protected function authorizeAndMarkViewed(Request $request, DtrSubmission $submission): void
    {
        $user = $request->user();

        abort_unless($user && in_array($user->role, ['hr', 'admin'], true), 403);
        abort_unless(filled($submission->file_path) && Storage::disk('local')->exists($submission->file_path), 404);

        $submission->forceFill([
            'is_new' => false,
            'viewed_at' => $submission->viewed_at ?? now(),
        ])->save();
    }
}
