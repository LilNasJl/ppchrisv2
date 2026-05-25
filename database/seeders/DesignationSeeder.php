<?php

namespace Database\Seeders;

use App\Models\Designation;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->designations() as $title) {
            $designation = Designation::withTrashed()
                ->firstOrNew(['title' => $title]);

            $designation->fill([
                'description' => null,
                'specification' => null,
            ]);

            $designation->save();

            if (method_exists($designation, 'trashed') && $designation->trashed()) {
                $designation->restore();
            }
        }
    }

    protected function designations(): array
    {
        return [
            'President',
            'Corporate Secretary',
            'Accounting Head',
            'HR Head',
            'Accounting Staff',
            'HR Staff',
            'IT Staff',
            'Retail Coordinator',
            'Station In Charge',
            'Liaison',
            'Operation Staff',
            'Forecourt Attendant',
            'Cashier/Forecourt Attendant',
            'Maintenance',
            'Tanker Driver',
        ];
    }
}
