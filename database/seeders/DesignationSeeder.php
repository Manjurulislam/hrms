<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Designation;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = Company::value('id') ?? 1;

        // Migrate legacy titles to the new naming convention.
        // Safe no-op when seeding a fresh database.
        Designation::where('company_id', $companyId)
            ->where('title', 'Senior Developer')
            ->update(['title' => 'Sr. Backend Developer', 'level' => 5]);

        Designation::where('company_id', $companyId)
            ->where('title', 'Developer')
            ->update(['title' => 'Backend Developer', 'level' => 6]);

        // Canonical hierarchy — parent references are resolved by model id
        // so re-running the seeder on a non-empty DB stays consistent.
        $ceo = Designation::updateOrCreate(
            ['title' => 'CEO', 'company_id' => $companyId],
            ['parent_id' => null, 'level' => 1, 'status' => true],
        );

        $cto = Designation::updateOrCreate(
            ['title' => 'CTO', 'company_id' => $companyId],
            ['parent_id' => $ceo->id, 'level' => 2, 'status' => true],
        );

        $pm = Designation::updateOrCreate(
            ['title' => 'Project Manager', 'company_id' => $companyId],
            ['parent_id' => $cto->id, 'level' => 3, 'status' => true],
        );

        $tl = Designation::updateOrCreate(
            ['title' => 'Team Lead', 'company_id' => $companyId],
            ['parent_id' => $pm->id, 'level' => 4, 'status' => true],
        );

        $teamLeadReports = [
            ['title' => 'Sr. Backend Developer',  'level' => 5],
            ['title' => 'Backend Developer',      'level' => 6],
            ['title' => 'Sr. Frontend Developer', 'level' => 5],
            ['title' => 'Frontend Developer',     'level' => 6],
            ['title' => 'Content Writer',         'level' => 6],
            ['title' => 'SEO Specialist',         'level' => 6],
            ['title' => 'UI/UX Designer',         'level' => 6],
        ];

        foreach ($teamLeadReports as $row) {
            Designation::updateOrCreate(
                ['title' => $row['title'], 'company_id' => $companyId],
                ['parent_id' => $tl->id, 'level' => $row['level'], 'status' => true],
            );
        }
    }
}
