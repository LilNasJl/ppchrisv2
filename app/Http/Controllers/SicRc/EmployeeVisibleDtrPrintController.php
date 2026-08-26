<?php

namespace App\Http\Controllers\SicRc;

use App\Http\Controllers\DtrPrintController;
use App\Models\Branch;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\EmployeeVisibleDtr;
use App\Models\PayrollPeriod;
use App\Models\SicRcAccount;
use App\Services\DtrRecordService;
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
        $account = auth('sicrc')->user();

        abort_unless($account instanceof SicRcAccount, 403);

        $periodId = PayrollPeriod::resolvePublicId($period);
        $branchId = Branch::resolvePublicId($branch);
        $employeeId = Employee::resolvePublicId($employee);

        abort_unless($periodId && $branchId && $employeeId, 404);
        abort_unless(in_array((int) $branchId, $account->assignedBranchIds(), true), 403);

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
