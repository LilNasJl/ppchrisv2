<?php

namespace App\Http\Controllers\SicRc;

use App\Http\Controllers\DtrPrintController;
use App\Models\Branch;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\EmployeeVisibleDtr;
use App\Models\PayrollPeriod;
use App\Services\DtrRecordService;
use App\Services\StationManagementAccess;
use App\Support\CompanyExportHeader;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class EmployeeVisibleDtrPrintController extends DtrPrintController
{
    public function __invoke(
        Request $request,
        string $period,
        string $branch,
        string $employee,
        DtrRecordService $dtrRecords,
    ): View {
        $manager = auth()->user()?->employee;

        abort_unless($manager instanceof Employee, 403, 'Unauthorized manager.');

        $periodId = PayrollPeriod::resolvePublicId($period);
        $branchId = Branch::resolvePublicId($branch);
        $employeeId = Employee::resolvePublicId($employee);

        abort_unless($periodId && $branchId && $employeeId, 404);
        abort_unless(StationManagementAccess::canManageBranch($manager, (int) $branchId), 403, 'Branch not assigned.');

        $payrollPeriod = PayrollPeriod::query()->findOrFail($periodId);
        $selectedBranch = Branch::query()->findOrFail($branchId);
        $selectedEmployee = Employee::query()
            ->with(['branch', 'designation'])
            ->findOrFail($employeeId);

        abort_unless((int) $selectedEmployee->branch_id === (int) $selectedBranch->id, 403);

        $rows = EmployeeVisibleDtr::query()
            ->where('payroll_period_id', $payrollPeriod->id)
            ->forEmployee($selectedEmployee)
            ->orderBy('date_in')
            ->orderBy('time_in')
            ->orderBy('id')
            ->get()
            ->map(fn (Dtr $record): array => $this->printRow($record));

        return view('dtr.print', [
            'employee' => $selectedEmployee,
            'branch' => $selectedBranch,
            'period' => $payrollPeriod,
            'rows' => $rows,
            'companyName' => CompanyExportHeader::COMPANY_NAME,
            'companyAddress' => CompanyExportHeader::ADDRESS_LINE.', '.CompanyExportHeader::PROVINCE_LINE,
            'logo' => CompanyExportHeader::logoDataUri(),
            'generatedAt' => CompanyExportHeader::generatedAt(),
        ]);
    }
}
