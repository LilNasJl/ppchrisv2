# Whole-day absence simplification: implementation and verification

Date: October 13, 2026.

## Scope and final rule

Implemented in the existing projects; no new application or replacement database.

| Component | Current responsibility |
| --- | --- |
| Original dual keypad/OLED firmware | Export actual attendance only; all automatic absence generation removed |
| Dual Touch TFT landscape firmware | Actual attendance plus whole-day absence candidates only |
| biometricsweb DTR reader | Read actual records/candidates; omit obsolete automatic half-day rows |
| ppchrisv2 official and SIC/RC importers | Validate candidates using HRIS context, preserve actual attendance and audit history |
| ppchrisv2 payroll | Infer missing-half deduction from actual half-day duty; reconcile approved paid/unpaid leave |

Single-type and portrait firmware were not edited. Existing staged/unrelated
changes were preserved. There was no Git reset, deployment, firmware upload or
GitHub publication.

## Plan applied

1. Inspect current firmware exports, DTR readers, shared importer validation,
   leave reconciliation, payroll inference and tests.
2. Back up both affected sketches and absence helper headers.
3. Remove all automatic absence generation and obsolete split configuration from
   the original firmware.
4. Limit TFT generation to whole-day candidates and completed Monday-Saturday
   regular workdays; Saturday is 08:00-11:00.
5. Route both HRIS import paths through one whole-day acceptance rule. Retain
   actual half-day, paid/unpaid leave, correction and payroll-lock safeguards.
6. Filter obsolete generated half-day rows in both readers; do not fabricate
   attendance or silently convert a legacy half candidate into whole-day absence.
7. Run focused regressions, broader suites, firmware compilation, source/pin
   checks, reader checks and read-only database preservation checks.

## Confirmed findings and patches

| Area | Finding | Change and expected result |
| --- | --- | --- |
| Both firmware exports | Earlier code added a separate missing-half absence beside actual half-day duty | Removed this generation; actual duty remains one row |
| Original firmware | Automatic absence no longer fits the clarified scope | Removed generator, include/helper and Split setting; no synthetic absence rows |
| TFT whole-day generation | Earlier policy excluded Saturday and evaluated day-part overlaps | Any attendance blocks automatic absence; Mon-Fri configured end, Saturday 11:00, Sunday excluded |
| Export state | A cross-date or open session could be mistaken for no duty if only a single date were considered | Conservatively mark spanned dates and pending/open sessions as having attendance |
| Importers | Earlier acceptance depended on absence_split and accepted half candidates | No split required; whole-day only; legacy half candidates explicitly skipped with notice |
| Reconciliation | Earlier code shrank source whole-day absence into a separate half classification | Any later actual attendance supersedes automatic absence for the date; existing half-day payroll inference supplies the missing-half deduction |
| Partial approved leave | Source absence must not be rewritten or double counted | Preserve whole-day automatic source; effective payroll overlays approved leave in memory. Paid/unpaid treatment retained |
| Legacy payroll data | Old generated absence plus actual duty could double deduct | Ignore automatic source absence on dates having actual duty during calculation; no bulk database cleanup |
| Both readers | Obsolete half candidates still appeared as redundant DTR rows | Omit legacy half candidates; omit whole candidates that conflict with matching actual rows |
| Payroll regression | Whole-day actual duty plus a same-day partial row could incorrectly infer half absence | Detect whole-day attendance before half-part inference; regression now passes |
| Test fixture | Station workflow fixture omitted existing pay_treatment field | Updated fixture; all 15 tests in that class pass |

Primary affected code:
- Firmware: each main .ino; TFT regular_absence_rules.h. Original helper deleted.
- HRIS: AutomaticAbsenceService.php, Imports/DtrImportService.php,
  LeavePayService.php, PayrollCalculator.php.
- HR reader: public/page/hr_atttendance_viewer.html.
- biometricsweb reader: public/assets/dtr-viewer/index.html.
- Tests: AutomaticAbsenceImportTest.php,
  LeaveAbsencePayrollReconciliationTest.php,
  PayrollHalfDayInferenceTest.php,
  StationManagementApprovalWorkflowAccessTest.php.

EmployeeVisibleDtrImportService continues to use shared candidate validation and
AutomaticAbsenceService; it does not need a competing absence algorithm.

## Examples

Assume a regular weekday, no leave/holiday or employment exception, and the
workday has ended. Existing late/undertime policy remains separate.

| Actual punches | TFT exported DTR | Original exported DTR | HRIS interpretation |
| --- | --- | --- | --- |
| 08:00-12:30 only | One actual morning half-day row | Same | Half-day duty; missing afternoon inferred |
| 12:31-18:00 only | One actual afternoon half-day row | Same | Half-day duty; missing morning inferred |
| No punches all day | One whole-day Absent candidate, punches empty | No synthetic row | TFT candidate validated before absence deduction |
| Time In only or Time Out only | Actual incomplete record; no automatic absence | Same | Existing incomplete/Forgot to Punch review |
| Actual complete whole-day duty | Existing actual records, no added absence | Same | Existing full-day calculation |
| Saturday no punches | One whole-day candidate for 08:00-11:00, after cutoff | No synthetic row | Existing Saturday absence value: 180 minutes |
| Sunday no punches | No automatic candidate | No synthetic row | Existing rest-day rules |

One row means one existing actual session: distinct genuine sessions are not
merged or deleted merely to enforce a row count.

For TFT, candidates are generated during export/download, not by an unattended
nightly writer. Re-exporting recomputes them from retained sessions and current
schedule configuration. No fake Time In/Out is inserted.

### Leave examples

- Afternoon duty plus approved paid morning leave: one actual duty row plus the
  legitimate leave row; no extra generated absence; paid leave treatment applies.
- Afternoon duty plus approved unpaid morning leave: afternoon work retained;
  missing morning classified as unpaid leave, not unauthorized absence as well.
- No actual duty plus approved morning leave: whole-day automatic source may
  remain for audit; payroll covers morning with leave and charges only the
  uncovered afternoon as absence. It does not create a second firmware half row.
- Full-day approved leave covers the entire absence; pending leave requires
  review. Approval/import order regressions pass.
- Locked payroll remains protected; there is no silent recalculation of a
  finalized period.

Illustrative existing payroll rates: PHP 800/day and PHP 100/hour, without other
adjustments, give PHP 400 for a 240-minute uncovered weekday half and PHP 800 for
a 480-minute weekday absence. Saturday uses the existing 180-minute rule
(PHP 300 at that hourly rate), not a new 480-minute deduction. Actual payroll
also depends on employee pay basis, period and approval conditions.

## Compatibility and data preservation

- Actual scan/export framing remains compatible; whole-day candidates retain
  explicit source, day_part=whole_day, schedule boundaries, zero work/day credit
  and empty punches.
- Legacy automatic half candidates are skipped, not upgraded to whole-day.
- Readers scope matching by identity, branch and date. They do not calculate HR
  leave/payroll rules.
- An authorized later actual DTR supersedes automatic absence using existing
  recoverable soft deletion and audit logging. This is runtime behavior; no
  historical rows were bulk changed during this task.
- Prior paid/unpaid leave fields and useful indexes remain. No schema migration,
  index removal or new migration execution was necessary for this revision.
- Read-only before/after checks confirmed matching counts and data digests for:
  19 leaves, 1,839 official DTR rows, 359 employee-visible DTR rows and 10 payroll
  periods. Columns, indexes and foreign keys also matched.
- There were zero stored auto_absence records in the two local DTR tables, so no
  cleanup was needed. Deployment databases must be checked separately.
- Firmware source/GPIO assertions confirm no pin assignment changes.
- Removed original helper is recoverable with the pre-change sketches in:
  C:/Users/Joelyn/Documents/Codex/2026-08-15/use/absence-revision-backup

## Test results

| Check | Result |
| --- | --- |
| Targeted absence/import/leave/half-day payroll suite | PASS: 74 tests, 659 assertions |
| Station workflow fixture suite | PASS: 15 tests, 106 assertions |
| Whole-day firmware eligibility | PASS: 16 compile-time assertions |
| Both DTR reader JavaScript tests | PASS |
| Firmware source/configuration/pin assertions | PASS |
| PHP formatting | PASS: 21 dirty files checked |
| Git whitespace checks | PASS |
| Original ESP32-S3 compile | PASS: 1,300,008 program bytes; 73,272 static RAM |
| TFT landscape ESP32-S3 compile | PASS: 1,307,463 program bytes; 80,220 static RAM |
| Existing OTA partition fit | PASS: both below 3,145,728 bytes per application slot |
| Database preservation | PASS: read-only hashes/schema comparisons unchanged |
| Physical scanner/RTC/TFT/SD/export validation | Requires Hardware Validation |
| Interactive browser end-to-end workflows | Requires Manual Validation |

Both firmware builds use ESP32-S3/16MB/custom partitions. Original retains PSRAM
disabled; TFT uses OPI PSRAM. Their version numbers were not bumped.

Coverage includes both import paths and processing orders, morning/afternoon
actual duty, whole-day absence, Saturday cutoff/window, Sunday, duplicates,
incomplete attendance, corrections, leave pay treatments/coverage, pending and
rejected leave behavior, holidays/employment constraints, locked payroll and
historical automatic rows. No device was connected for physical execution.

### Broader suites are not all green

- ppchrisv2 full suite: 418 passed, 12 failed (430 total; 2,123 assertions).
  All remaining failing names were reproduced with the pre-revision service
  implementations; one related fixture failure was fixed.
  Remaining groups: locked-period fixture timing in DtrChangeRequestApproval;
  missing submitDtrProof actions in synchronization/station tests; landing-page
  text expectation; six PayrollCalculationPage authorization/test-schema errors;
  promotion-audit expectation; station fixture missing leaves table.
- biometricsweb full suite: 53 passed, 5 failed (58 total; 229 assertions).
  Unchanged backend failures: one device-reset-cooldown authorization response
  and four DeviceTimeWarning tests calling missing deviceTimeDriftSeconds().
  This revision changes the DTR reader, not those backend paths.

These unrelated failures were not hidden or broadened into unrequested fixes.
They should be addressed separately before describing either complete project
as having an entirely passing suite.

JUnit evidence:
- C:/Users/Joelyn/Documents/Codex/2026-08-15/use/hris-absence-final-tests.xml
- C:/Users/Joelyn/Documents/Codex/2026-08-15/use/hris-absence-baseline-failures.xml
- C:/Users/Joelyn/Documents/Codex/2026-08-15/use/biometricsweb-absence-test-results.xml

## Deployment and remaining recommendations

1. Upload the matching sketch to each device; source edits alone do not update
   installed firmware. Deploy the corresponding reader/importer changes together.
2. Create a fresh export, especially when the older export contains half-day
   absence candidates. Existing downloaded files are not automatically rewritten.
3. Validate morning-only, afternoon-only, no-duty weekday, Saturday-after-11:00,
   incomplete punch, paid/unpaid leave and a later attendance correction in an
   unlocked test period before production payroll.
4. Review HRIS employment/schedule/holiday data. The firmware cannot determine
   approved leave, special non-working days or employee-specific rest calendars.
   HRIS validates the context it has; unsupported historical schedule/rest-roster
   changes still require HR review.
5. Do not infer absence from incomplete retained attendance history. Keep RTC and
   date coverage correct; re-export after corrections.
6. Original firmware now intentionally supplies no synthetic whole-day absences.
   Do not expect it to match TFT's whole-day generation without a future explicitly
   authorized change.

