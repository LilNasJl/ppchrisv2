<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Services\PayrollCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PayrollPayslipPrintController extends Controller
{
    public function __invoke(
        Request $request,
        PayrollCalculator $calculator,
        string $period,
        ?string $employee = null,
    ): View {
        $user = $request->user();

        abort_unless($user, 403);

        $periodId = PayrollPeriod::resolvePublicId($period);
        $payrollPeriod = PayrollPeriod::query()->findOrFail($periodId);

        if ($user->role === 'employee') {
            abort_unless((bool) $user->can_view_payroll, 403);
            abort_unless((bool) $payrollPeriod->is_locked, 403);

            $payrollEmployee = $user->employee()
                ->with(['designation', 'department', 'branch', 'employeeDeductions.deduction'])
                ->activeEmployment()
                ->firstOrFail();

            if (filled($employee)) {
                abort_unless(Employee::resolvePublicId($employee) === $payrollEmployee->id, 403);
            }
        } else {
            abort_unless(in_array($user->role, ['hr', 'admin'], true), 403);
            abort_if(blank($employee), 404);

            $employeeId = Employee::resolvePublicId($employee);
            $payrollEmployee = Employee::query()
                ->with(['designation', 'department', 'branch', 'employeeDeductions.deduction'])
                ->activeEmployment()
                ->findOrFail($employeeId);
        }

        abort_if($calculator->isEmployeePayrollExcluded($payrollPeriod, $payrollEmployee), 404);

        return view('payroll.payslip', [
            'employee' => $payrollEmployee,
            'period' => $payrollPeriod,
            'row' => $calculator->employeeRow($payrollEmployee, $payrollPeriod),
        ]);
    }
}
