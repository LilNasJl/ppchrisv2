<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thirteenth_month_releases', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('segment', 20);
            $table->decimal('basis_amount', 14, 2)->default(0);
            $table->decimal('released_amount', 14, 2)->default(0);
            $table->json('calculation_data')->nullable();
            $table->timestamp('released_at');
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'year', 'segment'], 'thirteenth_month_release_unique');
            $table->index(['year', 'segment', 'branch_id'], 'thirteenth_month_release_filter');
        });

        if (Schema::hasTable(config('permission.table_names.permissions', 'permissions'))) {
            Permission::findOrCreate('View:ThirteenthMonthPay', 'web');
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('thirteenth_month_releases');

        if (Schema::hasTable(config('permission.table_names.permissions', 'permissions'))) {
            Permission::query()
                ->where('name', 'View:ThirteenthMonthPay')
                ->where('guard_name', 'web')
                ->delete();

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
};
