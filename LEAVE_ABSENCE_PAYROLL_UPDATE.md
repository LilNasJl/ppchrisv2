# Leave, absence and payroll reconciliation — 2026-10-13

> Superseded in part by [Whole-day absence revision](WHOLE_DAY_ABSENCE_REVISION.md).
> The paid/unpaid leave and locking safeguards below remain. Automatic half-day
> firmware candidates and shrinking automatic whole-day source rows were removed.
> Actual half-day duty is one row; approved leave overlays source absence in payroll.
> Refer to the revision report for current import rules and final test results.

## Applied behavior

- Requests carry a requested paid/unpaid treatment. HR confirms the final treatment during approval.
- Existing leave records retain paid treatment through the additive database default.
- Paid leave follows the existing leave-credit rules. Unpaid leave does not consume credits or require a credit balance. Birthday leave remains paid.
- Final approval creates the appropriate morning, afternoon or whole-day leave DTR and reconciles only overlapping absence.
- Real attendance in the other half remains identical in both the HR DTR and SIC/RC DTR tables.
- Real attendance in the requested leave half blocks approval and leaves the credits and request unchanged.
- Absence rows covered by leave are retained through soft deletion and ActionHistory. A whole-day absence covered by morning leave becomes an afternoon absence, with the before/after values audited.
- A whole-day firmware absence imported after approved morning leave also becomes an afternoon absence. The result no longer depends on import order.
- Pending half-day leave holds only a matching absence candidate; it no longer suppresses an opposite-half candidate. Pending/rejected/cancelled requests do not create payable leave rows.
- Regular half-day leave uses the configured branch schedule. The existing non-regular half-day schedule fallback is retained.
- Open payroll uses current DTR and approved leave data. Locked payroll snapshots are not recalculated or modified.
- Approved leave cannot be directly deleted or silently changed to a different date or pay treatment. Such a change requires a controlled HR correction; this patch does not introduce an approved-leave cancellation workflow.

## Calculation examples

Assume a daily rate of PHP 800 and the normal half-day value of 0.5, with no late/undertime or other deductions.

| Date / case | Morning | Afternoon | Daily-rate base pay | Monthly-rate leave deduction |
|---|---|---|---:|---:|
| Oct 13: paid morning leave | Approved paid leave | Actual 13:00–18:00 work | 800 | 0 |
| Oct 13: unpaid morning leave | Approved unpaid leave | Actual 13:00–18:00 work | 400 | 400 |
| Paid afternoon leave | Actual 08:00–12:00 work | Approved paid leave | 800 | 0 |
| Unpaid afternoon leave | Actual 08:00–12:00 work | Approved unpaid leave | 400 | 400 |
| Whole-day paid leave | Approved paid leave | Approved paid leave | 800 | 0 |
| Whole-day unpaid leave | Approved unpaid leave | Approved unpaid leave | 0 | 800 |
| Morning paid leave, no afternoon duty | Approved paid leave | Absent | 400 | 400 absence |

For a monthly salary of PHP 20,800, divisor 26 and a 13-day cutoff, base cutoff pay is PHP 10,400. One unpaid half-day produces PHP 400 under Unpaid Leave, with zero unauthorized absence/half-day deduction for that same covered half. Other actual attendance deductions remain separate.

Leave pay units are deduplicated by date/day-part and do not double-credit actual work in an overlapping part. Paid leave is included in daily-rate pay. Monthly unpaid leave is a separate visible deduction. Leave is not treated as actual worked holiday premium.

## Query and indexing changes

- Leave date coverage uses an employee/status/date composite index and indexable range comparisons.
- Official DTR conflict checks use payroll-period/branch/fingerprint/date. Absence day lookups use branch/fingerprint/date.
- The existing SIC/RC scope/date index is retained, with a branch/fingerprint/date lookup index added.
- Leave range conflict checking reads each DTR table once rather than querying once for each leave date. A 13-day range test confirms two SELECTs total.
- Payroll loads related leave data in batches and resolves coverage in memory.
- Candidate assessment reuses its leave-date lookup.

Read-only MySQL EXPLAIN results on the local database:

| Query | Estimated rows before | Estimated rows after | Index used after |
|---|---:|---:|---|
| Leave coverage | 18 | 3 | leaves_employee_status_dates_idx |
| Official DTR range conflicts | 5 | 1 | dtrs_scope_date_reconcile_idx |
| Absence day lookup | 1513 | 1 | dtrs_branch_fp_date_idx |

These are optimizer estimates, not measured end-to-end response times.

## Verification

- Core regression run: **144 tests / 824 assertions PASS**, including 39 new reconciliation tests.
- Additional workflow run: **80 passed; 8 failed/error**. All eight also occur against original HEAD source in an isolated class-loader overlay.
- Additional existing failures: PayrollCalculationPageTest has three permission/403 failures and three form-testing errors; DtrSynchronizationWorkflowTest expects a missing submitDtrProof action; DtrChangeRequestApprovalWorkflowTest attempts to create a DTR inside an already locked period.
- The prior LeaveApprovalWorkflowFeatureTest schema fixture was corrected to include description and pay_treatment. Its 13 workflow tests pass.
- PHP formatting: PASS. Blade compilation: PASS. git diff --check: PASS.
- Tests use in-memory SQLite, without inserting test attendance or leave into the live database.
- MySQL verification checked actual query plans and the applied migration; interactive browser testing was not performed.

The new scenarios cover both import services, both processing orders, morning/afternoon, paid/unpaid, whole-day candidate splitting, actual attendance preservation in both DTR tables, duplicate import, manual absence, rejection/cancellation, insufficient credits, overlapping work, branch schedule, silent repricing/deletion protection, open payroll and locked snapshots.

## Database preservation

Applied only migration 2026_10_13_100000_add_leave_pay_treatment_and_reconciliation_indexes.php. It adds one field and four indexes. No existing IDs, dates, punches, payroll values or foreign keys were rewritten.

Before/after counts and SHA-256 digests of all original columns matched:

| Table | Rows preserved |
|---|---:|
| leaves | 19 |
| dtrs | 1686 |
| employee_visible_dtrs | 209 |
| payroll_periods | 10 |

Existing locked snapshots continue to use their saved values. No historical leave/DTR records were retroactively corrected during deployment. Open payroll calculations will use the updated rules on refresh.

## Main files

- app/Models/Leave.php: pay treatment, credits, approved-history safeguards.
- app/Services/LeaveApprovalService.php and LeaveDtrService.php: final decision and day-part reconciliation.
- app/Services/AutomaticAbsenceService.php and both import services: candidate matching and splitting.
- app/Services/DtrSynchronizationService.php: leave identity matching; prevents overwriting other attendance.
- app/Services/LeavePayService.php and PayrollCalculator.php: effective coverage and paid/unpaid calculations.
- Leave request/approval forms, leave details, payroll table, employee payroll and payslip: pay treatment and unpaid-leave feedback.
- tests/Feature/LeaveAbsencePayrollReconciliationTest.php: new regression scenarios.
