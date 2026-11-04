<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AgeGroupSummary;
use App\Filament\Widgets\BirthdayGreetingCard;
use App\Filament\Widgets\DashboardCard;
use App\Filament\Widgets\DepartmentEmployeeCountChart;
use App\Filament\Widgets\EducationLevelSummary;
use App\Filament\Widgets\EmployeeStatusSummary;
use App\Filament\Widgets\EmploymentTypeHeadCount;
use App\Filament\Widgets\HeadCountPerDept;
use App\Filament\Widgets\HolidayCalendarHighlights;
use App\Filament\Widgets\LeaveRequestStatusSummary;
use App\Filament\Widgets\MonthlyEmployeeMovementChart;
use App\Filament\Widgets\OverallDailyAttendanceSummaryChart;
use App\Filament\Widgets\OvertimeLogHoursChart;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Livewire\Attributes\Url;
use Override;

class Dashboard extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Squares2x2;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -1000;

    #[Url(as: 'view', except: 'primary')]
    public string $viewMode = 'primary';

    public function mount(): void
    {
        if (request()->hasAny(['attendanceFilters', 'overtimeFilters'])) {
            $this->redirect(static::getUrl($this->viewMode === 'analytics' ? ['view' => 'analytics'] : []), navigate: false);
        }
    }

    #[Override]
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    #[Override]
    public function getHeading(): ?string
    {
        return null;
    }

    #[Override]
    public function getBreadcrumbs(): array
    {
        return [];
    }

    #[Override]
    protected function getHeaderActions(): array
    {
        if ($this->viewMode !== 'analytics') {
            return [];
        }

        return [
            Action::make('dashboard-view')
                ->label('Back to Dashboard')
                ->icon(Heroicon::ArrowLeft)
                ->iconButton()
                ->tooltip('Back to Dashboard')
                ->size(Size::Small)
                ->color('primary')
                ->url(static::getUrl()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        if ($this->viewMode === 'analytics') {
            return [
                DashboardCard::class,
                BirthdayGreetingCard::class,
                HeadCountPerDept::class,
                EmploymentTypeHeadCount::class,
                EducationLevelSummary::class,
                AgeGroupSummary::class,
            ];
        }

        return [
            EmployeeStatusSummary::class,
            DepartmentEmployeeCountChart::class,
            MonthlyEmployeeMovementChart::class,
            LeaveRequestStatusSummary::class,
            OverallDailyAttendanceSummaryChart::class,
            OvertimeLogHoursChart::class,
            HolidayCalendarHighlights::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'lg' => 2,
        ];
    }

    #[Override]
    public function headerWidgets(Schema $schema): Schema
    {
        if ($this->viewMode === 'analytics') {
            return $schema->components([
                RenderHook::make(PanelsRenderHook::PAGE_HEADER_WIDGETS_START),
                View::make('filament.pages.partials.employee-snapshot-header')
                    ->viewData([
                        'eyebrow' => 'People Analytics',
                        'title' => 'Workforce Analytics',
                        'description' => 'Explore employee headcount, tenure, demographics, and education at a glance.',
                    ])
                    ->columnSpanFull(),
                Grid::make($this->getHeaderWidgetsColumns())
                    ->schema(fn (): array => $this->cachedHeaderWidgetsSchemaComponents ??= $this->getWidgetsSchemaComponents($this->getHeaderWidgets())),
                RenderHook::make(PanelsRenderHook::PAGE_HEADER_WIDGETS_END),
            ])->hidden(fn (): bool => empty($this->cachedHeaderWidgetsSchemaComponents ??= $this->getWidgetsSchemaComponents($this->getHeaderWidgets())));
        }

        return $schema->components([
            RenderHook::make(PanelsRenderHook::PAGE_HEADER_WIDGETS_START),
            Grid::make([
                'default' => 1,
                'xl' => 12,
            ])
                ->schema([
                    // Left Column (Core Analytics)
                    Grid::make(1)
                        ->schema([
                            View::make('filament.pages.partials.employee-snapshot-header')
                                ->viewData([
                                    'eyebrow' => 'Workforce Overview',
                                    'title' => 'Dashboard: Employee Snapshot',
                                    'description' => 'Workforce status, pending tickets, and monthly movement.',
                                    'dashboardActionUrl' => static::getUrl(['view' => 'analytics']),
                                ]),
                            Grid::make(1)
                                ->schema(fn (): array => $this->getWidgetsSchemaComponents([EmployeeStatusSummary::class]))
                                ->extraAttributes(['class' => 'ppc-dashboard-employee-snapshot-cards']),
                            Grid::make(1)
                                ->schema(fn (): array => $this->getWidgetsSchemaComponents($this->getEmployeeSnapshotChartWidgets()))
                                ->extraAttributes(['class' => 'ppc-dashboard-employment-charts']),
                            View::make('filament.pages.partials.employee-snapshot-header')
                                ->viewData([
                                    'eyebrow' => 'Attendance Overview',
                                    'title' => 'Attendance & Leave Overview',
                                    'description' => 'Daily attendance, overtime activity, and leave request status.',
                                ]),
                            Grid::make(1)
                                ->schema(fn (): array => $this->getWidgetsSchemaComponents([LeaveRequestStatusSummary::class]))
                                ->extraAttributes(['class' => 'ppc-dashboard-leave-cards']),
                            Grid::make([
                                'default' => 1,
                                'md' => 2,
                            ])
                                ->schema(fn (): array => $this->getWidgetsSchemaComponents([
                                    OverallDailyAttendanceSummaryChart::class,
                                    OvertimeLogHoursChart::class,
                                ]))
                                ->extraAttributes(['class' => 'ppc-dashboard-attendance-charts']),
                            Grid::make(1)
                                ->schema(fn (): array => $this->getWidgetsSchemaComponents([HolidayCalendarHighlights::class]))
                                ->extraAttributes(['class' => 'ppc-dashboard-holiday-highlights']),
                        ])
                        ->columnSpan([
                            'default' => 12,
                            'xl' => 8,
                        ])
                        ->extraAttributes(['class' => 'ppc-dashboard-left-col']),

                    // Right Column (Tall Side Panel: Shout Out Hall)
                    Grid::make(1)
                        ->schema([
                            View::make('filament.pages.partials.dashboard-shoutout-hall'),
                        ])
                        ->columnSpan([
                            'default' => 12,
                            'xl' => 4,
                        ])
                        ->extraAttributes(['class' => 'ppc-dashboard-right-col']),
                ])
                ->columnSpanFull()
                ->extraAttributes(['class' => 'ppc-dashboard-master-grid']),
            RenderHook::make(PanelsRenderHook::PAGE_HEADER_WIDGETS_END),
        ])->hidden(fn (): bool => empty($this->cachedHeaderWidgetsSchemaComponents ??= $this->getWidgetsSchemaComponents($this->getHeaderWidgets())));
    }

    protected function getAttendanceLeaveWidgets(): array
    {
        return [
            LeaveRequestStatusSummary::class,
            OverallDailyAttendanceSummaryChart::class,
            OvertimeLogHoursChart::class,
            HolidayCalendarHighlights::class,
        ];
    }

    protected function getEmployeeSnapshotChartWidgets(): array
    {
        return [
            DepartmentEmployeeCountChart::class,
            MonthlyEmployeeMovementChart::class,
        ];
    }
}
