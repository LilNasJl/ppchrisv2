<?php

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $used = [];
        $next = 1;

        Employee::withTrashed()
            ->orderBy('id')
            ->get(['id', 'uid', 'user_id'])
            ->each(function (Employee $employee) use (&$used, &$next): void {
                $uid = Employee::normalizeUid($employee->uid);
                $number = (int) $uid;

                if ($number < 1 || in_array($uid, $used, true)) {
                    while (in_array(str_pad((string) $next, 4, '0', STR_PAD_LEFT), $used, true)) {
                        $next++;
                    }

                    $uid = str_pad((string) $next, 4, '0', STR_PAD_LEFT);
                    $number = $next;
                }

                $used[] = $uid;
                $next = max($next, $number + 1);

                DB::table('employees')
                    ->where('id', $employee->id)
                    ->update(['uid' => $uid]);

                if (filled($employee->user_id)) {
                    $username = User::companyUsernameFromUid($uid);

                    DB::table('users')
                        ->where('id', $employee->user_id)
                        ->update([
                            'username' => $username,
                            'name' => $username,
                        ]);
                }
            });

        if (! $this->hasIndex('employees', 'employees_uid_unique')) {
            Schema::table('employees', function (Blueprint $table): void {
                $table->unique('uid', 'employees_uid_unique');
            });
        }

        Employee::syncCounterToUid(DB::table('employees')->max('uid'));
    }

    public function down(): void
    {
        if ($this->hasIndex('employees', 'employees_uid_unique')) {
            Schema::table('employees', function (Blueprint $table): void {
                $table->dropUnique('employees_uid_unique');
            });
        }
    }

    protected function hasIndex(string $table, string $index): bool
    {
        return collect(DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]))->isNotEmpty();
    }
};
