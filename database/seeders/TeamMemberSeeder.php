<?php

namespace Database\Seeders;

use App\Models\TeamMember;
use Illuminate\Database\Seeder;

class TeamMemberSeeder extends Seeder
{
    public function run(): void
    {
        $members = [
            [
                'name' => 'Adaeze Nwosu',
                'role' => 'Managing Director',
                'department' => TeamMember::DEPARTMENT_MANAGERIAL,
                'quote' => 'Clear decisions, dependable delivery.',
                'bio' => 'Leads LemonWares strategy across hosting, Mailemon, and product delivery.',
                'sort_order' => 10,
            ],
            [
                'name' => 'Tunde Bakare',
                'role' => 'Office & People Coordinator',
                'department' => TeamMember::DEPARTMENT_ADMINISTRATIVE,
                'quote' => 'Ops that keep the team moving.',
                'bio' => 'Handles day-to-day administration, scheduling, and internal coordination.',
                'sort_order' => 20,
            ],
        ];

        foreach ($members as $member) {
            TeamMember::query()->updateOrCreate(
                [
                    'name' => $member['name'],
                    'department' => $member['department'],
                ],
                array_merge($member, [
                    'is_active' => true,
                ]),
            );
        }
    }
}
