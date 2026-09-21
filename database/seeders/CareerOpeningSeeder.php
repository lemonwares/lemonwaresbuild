<?php

namespace Database\Seeders;

use App\Models\CareerOpening;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CareerOpeningSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'title' => 'Customer Support Specialist',
                'type' => 'Full-time',
                'location' => 'Lagos · Hybrid',
                'summary' => 'Help customers with hosting, domains, DNS, and Mailemon setup. Clear writing and calm troubleshooting matter most.',
                'description' => "You will be a primary point of contact for LemonWares customers — hosting, domains, DNS, and Mailemon email.\n\nSuccess looks like clear answers, careful follow-through, and customers who feel looked after.",
                'responsibilities' => "- Triage and resolve support tickets across hosting, domains, and Mailemon\n- Guide customers through DNS and mailbox setup\n- Escalate platform issues with clear notes for engineering\n- Keep help docs and canned replies accurate",
                'requirements' => "- Strong written English and calm phone/WhatsApp manner\n- Comfort explaining technical concepts simply\n- Familiarity with DNS, email, or hosting is a plus\n- Based in Lagos or able to work hybrid hours with the team",
                'apply_subject' => 'Careers · Customer Support Specialist',
                'sort_order' => 10,
            ],
            [
                'title' => 'Full-Stack Developer',
                'type' => 'Full-time',
                'location' => 'Lagos · Hybrid / Remote-friendly',
                'summary' => 'Build and improve LemonWares web products — Laravel, Blade, and modern frontends — with an eye for reliability.',
                'description' => "You will ship features across the LemonWares site, client area, and admin tools.\n\nWe care about readable code, careful migrations, and UIs that match the brand.",
                'responsibilities' => "- Build and maintain Laravel + Blade features\n- Improve checkout, account, and admin workflows\n- Collaborate on Mailemon and hosting product surfaces\n- Write tests for critical paths when touching payments or auth",
                'requirements' => "- Solid PHP/Laravel experience\n- Comfortable with Blade, Tailwind, and modern JS\n- Care about UX polish and accessibility basics\n- Experience with Postgres/MySQL and queues is a plus",
                'apply_subject' => 'Careers · Full-Stack Developer',
                'sort_order' => 20,
            ],
            [
                'title' => 'Cloud Infrastructure Engineer',
                'type' => 'Full-time',
                'location' => 'Lagos · Hybrid',
                'summary' => 'Own shared hosting, VPS, and platform operations: provisioning, monitoring, hardening, and incident response.',
                'description' => "You will keep LemonWares hosting and related platforms healthy — provisioning, monitoring, and incidents.\n\nReliability and clear runbooks matter as much as clever automation.",
                'responsibilities' => "- Operate and harden shared hosting and VPS environments\n- Improve monitoring, backups, and incident response\n- Automate repetitive provisioning where it pays off\n- Partner with support on customer-impacting issues",
                'requirements' => "- Hands-on Linux administration experience\n- Familiarity with cPanel/Plesk or similar stacks\n- Comfort with networking, DNS, and SSL basics\n- Scripting (Bash/Python) and monitoring tools are a plus",
                'apply_subject' => 'Careers · Cloud Infrastructure Engineer',
                'sort_order' => 30,
            ],
        ];

        foreach ($roles as $role) {
            $slug = Str::slug($role['title']);

            CareerOpening::query()->updateOrCreate(
                ['slug' => $slug],
                array_merge($role, [
                    'slug' => $slug,
                    'is_active' => true,
                ])
            );
        }
    }
}
