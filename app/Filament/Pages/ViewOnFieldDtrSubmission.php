<?php

namespace App\Filament\Pages;

use App\Models\DtrSubmission;
use App\Services\OnFieldDtrService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Override;

class ViewOnFieldDtrSubmission extends Page
{
    protected string $view = 'filament.pages.view-on-field-dtr-submission';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'on-field-dtr/view';

    protected static ?string $title = 'View On Field DTR';

    public ?string $submissionId = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && in_array($user->role, ['hr', 'admin'], true);
    }

    public function mount(): void
    {
        $this->submissionId = (string) request()->query('submissionId', '');
        $submission = $this->submission;

        abort_unless($submission && $submission->submission_type === DtrSubmission::TYPE_PROOF, 404);

        if ($submission->is_new || ! $submission->viewed_at) {
            $submission->forceFill([
                'is_new' => false,
                'viewed_at' => $submission->viewed_at ?? now(),
            ])->save();
        }
    }

    public function getSubmissionProperty(): ?DtrSubmission
    {
        $id = DtrSubmission::resolvePublicId($this->submissionId);

        return $id
            ? DtrSubmission::query()
                ->with([
                    'employee.branch',
                    'branch',
                    'payrollPeriod',
                    'sicRcAccount',
                    'reviewedBy',
                    'generatedDtrDeletedBy',
                ])
                ->find($id)
            : null;
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            Action::make('return')
                ->label('Return')
                ->icon(Heroicon::ArrowLeft)
                ->url(DtrProofSubmissions::getUrl()),

            Action::make('viewProof')
                ->label('View Proof')
                ->icon(Heroicon::Eye)
                ->url(fn (): string => route('hr_tools.dtr_submissions.view', $this->submission))
                ->openUrlInNewTab(),

            Action::make('downloadProof')
                ->label('Download Proof')
                ->icon(Heroicon::ArrowDownTray)
                ->url(fn (): string => route('hr_tools.dtr_submissions.download', $this->submission))
                ->openUrlInNewTab(),

            Action::make('approve')
                ->label('Approve')
                ->icon(Heroicon::CheckCircle)
                ->color('success')
                ->visible(fn (): bool => (bool) $this->submission?->isPending())
                ->requiresConfirmation()
                ->modalHeading('Approve On Field DTR?')
                ->modalDescription('The official and employee-visible D.T.R records will be calculated and created together.')
                ->schema([
                    Textarea::make('remarks')
                        ->label('HR Remarks')
                        ->rows(3)
                        ->maxLength(2000),
                ])
                ->action(fn (array $data): mixed => $this->reviewSubmission(true, $data['remarks'] ?? null)),

            Action::make('reject')
                ->label('Reject')
                ->icon(Heroicon::XCircle)
                ->color('danger')
                ->visible(fn (): bool => (bool) $this->submission?->isPending())
                ->requiresConfirmation()
                ->modalHeading('Reject On Field DTR?')
                ->modalDescription('No D.T.R entry will be created. The rejected request will remain in history.')
                ->schema([
                    Textarea::make('remarks')
                        ->label('HR Remarks')
                        ->rows(3)
                        ->maxLength(2000),
                ])
                ->action(fn (array $data): mixed => $this->reviewSubmission(false, $data['remarks'] ?? null)),
        ];
    }

    protected function reviewSubmission(bool $approve, ?string $remarks): mixed
    {
        try {
            $service = app(OnFieldDtrService::class);
            $submission = $this->submission;
            $user = auth()->user();

            abort_unless($submission && $user, 404);

            $approve
                ? $service->approve($submission, $user, $remarks)
                : $service->reject($submission, $user, $remarks);

            Notification::make()
                ->title($approve ? 'On Field DTR approved' : 'On Field DTR rejected')
                ->body($approve
                    ? 'The linked official and employee-visible D.T.R records were created.'
                    : 'No D.T.R record was created.')
                ->success()
                ->send();

            return $this->redirect(static::getUrl(['submissionId' => $this->submissionId]));
        } catch (\DomainException $exception) {
            Notification::make()
                ->title('On Field DTR was not reviewed')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }
}
