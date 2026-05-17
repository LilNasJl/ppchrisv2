<?php

namespace App\Filament\Employee\Pages;

use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class MyAccount extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.employee.pages.my-account';

    protected static ?string $slug = 'my-account';

    protected static ?string $title = 'My Account';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::Identification;

    protected static ?int $navigationSort = 3;

    public ?array $data = [];

    public function mount(): void
    {
        $user = auth()->user();
        $employee = $user->employee;

        $this->form->fill([
            'employee_id' => $employee?->uid ? 'PF-' . $employee->uid : null,
            'fingerprint_id' => $employee?->fingerprint_id,
            'name' => $user->name,
            'email' => $user->email,
            'profile_photo_path' => $user->profile_photo_path,
        ]);
    }

    protected function getFormStatePath(): ?string
    {
        return 'data';
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Account Information')
                ->schema([
                    TextInput::make('employee_id')
                        ->label('Employee ID')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('fingerprint_id')
                        ->label('Fingerprint ID')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('name')
                        ->label('Account Name')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->rules(fn () => [
                            Rule::unique('users', 'email')->ignore(auth()->id()),
                        ]),

                    TextInput::make('password')
                        ->label('New Password')
                        ->password()
                        ->revealable()
                        ->confirmed()
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->minLength(8),

                    TextInput::make('password_confirmation')
                        ->label('Confirm New Password')
                        ->password()
                        ->revealable()
                        ->dehydrated(false),

                    FileUpload::make('profile_photo_path')
                        ->label('Profile Picture')
                        ->image()
                        ->previewable()
                        ->acceptedFileTypes(['image/png', 'image/jpeg'])
                        ->maxSize(3072)
                        ->disk('public')
                        ->directory('profile-photos')
                        ->visibility('public')
                        ->downloadable()
                        ->openable()
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }

    public function save(): void
    {
        auth()->user()->update($this->form->getState());

        Notification::make()
            ->title('Account updated')
            ->success()
            ->send();
    }
}
