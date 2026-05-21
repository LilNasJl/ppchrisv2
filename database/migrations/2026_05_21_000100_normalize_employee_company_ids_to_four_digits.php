<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $employees = DB::table('employees')
                ->select('id', 'uid', 'user_id')
                ->orderByRaw('CAST(uid AS UNSIGNED) ASC')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $usedIds = [];
            $nextId = 1;

            foreach ($employees as $employee) {
                $number = (int) preg_replace('/\D+/', '', (string) $employee->uid);

                if ($number < 1 || in_array($number, $usedIds, true) || $this->usernameBelongsToAnotherUser($number, $employee->user_id)) {
                    while (in_array($nextId, $usedIds, true)) {
                        $nextId++;
                    }

                    while ($this->usernameBelongsToAnotherUser($nextId, $employee->user_id)) {
                        $nextId++;
                    }

                    $number = $nextId;
                }

                $usedIds[] = $number;
                $nextId = max($nextId, $number + 1);

                $uid = str_pad((string) $number, 4, '0', STR_PAD_LEFT);
                $username = 'PF'.$uid;

                DB::table('employees')
                    ->where('id', $employee->id)
                    ->update([
                        'uid' => $uid,
                        'updated_at' => now(),
                    ]);

                if ($employee->user_id) {
                    DB::table('users')
                        ->where('id', $employee->user_id)
                        ->where('role', 'employee')
                        ->update([
                            'username' => $username,
                            'name' => $username,
                            'updated_at' => now(),
                        ]);
                }
            }

            $maxId = count($usedIds) ? max($usedIds) : 0;

            DB::table('counters')->updateOrInsert(
                ['id' => 1],
                [
                    'uid' => $maxId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::table('employees')
                ->orderBy('id')
                ->get(['id', 'uid', 'user_id'])
                ->each(function ($employee): void {
                    $uid = str_pad((string) ((int) preg_replace('/\D+/', '', (string) $employee->uid)), 5, '0', STR_PAD_LEFT);
                    $username = 'PF'.str_pad((string) ((int) $uid), 4, '0', STR_PAD_LEFT);

                    DB::table('employees')
                        ->where('id', $employee->id)
                        ->update([
                            'uid' => $uid,
                            'updated_at' => now(),
                        ]);

                    if ($employee->user_id) {
                        DB::table('users')
                            ->where('id', $employee->user_id)
                            ->where('role', 'employee')
                            ->update([
                                'username' => $username,
                                'name' => $username,
                                'updated_at' => now(),
                            ]);
                    }
                });
        });
    }

    private function usernameBelongsToAnotherUser(int $number, ?int $userId): bool
    {
        $username = 'PF'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);

        return DB::table('users')
            ->where('username', $username)
            ->when($userId, fn ($query) => $query->where('id', '!=', $userId))
            ->exists();
    }
};
