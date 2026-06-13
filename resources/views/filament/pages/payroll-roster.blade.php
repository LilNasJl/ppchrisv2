<x-filament-panels::page>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
        <a
            href="{{ $this->branchExemptionUrl() }}"
            style="display: block; border: 1px solid rgb(229 231 235 / 0.35); border-radius: 0.75rem; padding: 1.25rem; text-decoration: none;"
        >
            <div style="font-size: 0.875rem; font-weight: 600; color: rgb(59 130 246);">Payroll Roster</div>
            <div style="margin-top: 0.35rem; font-size: 1.15rem; font-weight: 700;">Branch Exemption</div>
            <div style="margin-top: 0.5rem; font-size: 0.9rem; color: rgb(107 114 128);">
                Exclude whole branches from the selected payroll period. Exempted branches will not appear in branch payroll lists or payroll summaries.
            </div>
        </a>

        <a
            href="{{ $this->employeeExemptionUrl() }}"
            style="display: block; border: 1px solid rgb(229 231 235 / 0.35); border-radius: 0.75rem; padding: 1.25rem; text-decoration: none;"
        >
            <div style="font-size: 0.875rem; font-weight: 600; color: rgb(59 130 246);">Payroll Roster</div>
            <div style="margin-top: 0.35rem; font-size: 1.15rem; font-weight: 700;">Employee Exemption</div>
            <div style="margin-top: 0.5rem; font-size: 0.9rem; color: rgb(107 114 128);">
                Exclude selected employees from the selected payroll period while keeping their branch available for other employees.
            </div>
        </a>
    </div>
</x-filament-panels::page>
