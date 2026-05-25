<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->departments() as $departmentData) {
            $department = Department::withTrashed()
                ->firstOrNew(['name' => $departmentData['name']]);

            $department->fill([
                'acronym' => $departmentData['acronym'],
                'description' => null,
            ]);

            $department->save();

            if (method_exists($department, 'trashed') && $department->trashed()) {
                $department->restore();
            }
        }
    }

    protected function departments(): array
    {
        return [
            ['name' => 'Accounting Management Office', 'acronym' => 'AMO'],
            ['name' => 'Human Resource Management Office', 'acronym' => 'HRMO'],
            ['name' => 'Information Communication Technology Office', 'acronym' => 'ICTO'],
            ['name' => 'Retail Management Office', 'acronym' => 'RMO'],
            ['name' => 'Station In Charge Management Office', 'acronym' => 'SICMO'],
            ['name' => 'Admin Office', 'acronym' => 'AO'],
            ['name' => 'Operation Management Office', 'acronym' => 'OMO'],
        ];
    }
}
