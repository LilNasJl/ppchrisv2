<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->nullable()->after('name')->unique();
        });

        $usedUsernames = [];

        DB::table('users')
            ->leftJoin('employees', 'employees.user_id', '=', 'users.id')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.role',
                'employees.uid',
            ])
            ->orderBy('users.id')
            ->get()
            ->each(function ($user) use (&$usedUsernames): void {
                $baseUsername = $user->role === 'employee' && filled($user->uid)
                    ? $this->companyUsernameFromUid($user->uid)
                    : $this->systemUsernameFromUser($user);

                $username = $this->uniqueUsername($baseUsername, (int) $user->id, $usedUsernames);
                $usedUsernames[] = $username;

                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'username' => $username,
                        'name' => $user->role === 'employee' ? $username : ($user->name ?: $username),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }

    private function companyUsernameFromUid(?string $uid): string
    {
        $uid = Str::of((string) $uid)
            ->replace('PF', '')
            ->replace('-', '')
            ->trim()
            ->toString();

        return 'PF'.str_pad((string) ((int) $uid), 4, '0', STR_PAD_LEFT);
    }

    private function systemUsernameFromUser(object $user): string
    {
        $source = filled($user->email)
            ? Str::before((string) $user->email, '@')
            : (string) $user->name;

        return $this->normalizeUsername($source ?: 'USER'.$user->id);
    }

    private function normalizeUsername(string $username): string
    {
        $username = Str::of($username)
            ->trim()
            ->toString();

        $username = preg_replace('/\s+/', '', $username) ?: 'USER';

        return $username;
    }

    private function uniqueUsername(string $baseUsername, int $userId, array $usedUsernames): string
    {
        $username = $baseUsername;
        $suffix = 1;

        while (
            in_array($username, $usedUsernames, true) ||
            DB::table('users')->where('username', $username)->where('id', '!=', $userId)->exists()
        ) {
            $username = $baseUsername.$suffix;
            $suffix++;
        }

        return $username;
    }
};
