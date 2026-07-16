<x-filament-panels::page>
    @include('filament.pages.partials.compact-management-table-styles')

    <!-- CARDS -->
    {{--  @livewire(\App\Filament\Widgets\EmployeeCardsDetails::class) --}}
   

    <!-- EMPLOYEE TABLES -->
    @livewire(\App\Filament\Widgets\EmployeeDetailsTable::class)

</x-filament-panels::page>
