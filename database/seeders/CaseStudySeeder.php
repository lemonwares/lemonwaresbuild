<?php

namespace Database\Seeders;

use App\Models\CaseStudy;
use Illuminate\Database\Seeder;

class CaseStudySeeder extends Seeder
{
    public function run(): void
    {
        $studies = [
            [
                'title' => 'Restination Apt',
                'client_name' => 'Restination Apt',
                'summary' => 'A complete business website with clear content structure, polished visual direction, and responsive UX tuned for conversion.',
                'outcome' => 'Launched on schedule with stronger brand presence and a backend the client team can update without friction.',
                'description' => "Restination Apt needed a credible digital front door — not a template that looked like every other listing site.\n\nWe shaped information architecture, visual direction, and responsive UX around conversion goals, then shipped a site the team can maintain themselves.",
                // Public demo site that usually allows iframe embedding (good for View live tests).
                'cta_url' => 'https://example.com',
                'cta_label' => 'View live',
                'cover_path' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=1800&q=80',
                'sort_order' => 10,
            ],
            [
                'title' => 'Bright Media platform',
                'client_name' => 'Bright Media',
                'summary' => 'High-traffic WordPress platform migrated onto LemonWares cloud hosting with caching, monitoring, and backup policies.',
                'outcome' => 'Faster loads, fewer incidents, and stable uptime for continuous publishing.',
                'description' => "Bright Media’s WordPress platform was outgrowing unstable hosting. Peak traffic meant slow pages and recurring support noise.\n\nWe migrated the live site onto LemonWares cloud hosting with caching, monitoring, and backups so publishing could stay continuous.",
                'cta_url' => 'https://wordpress.org',
                'cta_label' => 'View live',
                'cover_path' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=1800&q=80',
                'sort_order' => 20,
            ],
            [
                'title' => 'SME branded email rollout',
                'client_name' => 'Growing SME',
                'summary' => 'Domain-based Mailemon mailboxes provisioned for a scaling team — spam controls, mobile setup, and non-technical onboarding included.',
                'outcome' => 'Professional addresses live within one business day with reliable delivery.',
                'description' => "A scaling SME needed branded mailboxes without a week of DNS guesswork.\n\nWe provisioned Mailemon on their domain, applied spam controls, configured mobile clients, and onboarded the team so communication looked professional from day one.",
                // First website — typically embeds cleanly for preview testing.
                'cta_url' => 'https://info.cern.ch',
                'cta_label' => 'View live',
                'cover_path' => 'https://images.unsplash.com/photo-1596526131083-e8c633c948d2?auto=format&fit=crop&w=1800&q=80',
                'sort_order' => 30,
            ],
            [
                'title' => 'Field Service App',
                'client_name' => 'Field operations team',
                'summary' => 'Cross-platform mobile app for field jobs — offline workflows, status updates, and real-time sync with a central dashboard.',
                'outcome' => 'iOS and Android rollout improved on-site coordination and cut turnaround on routine jobs.',
                'description' => "Field teams needed job status and offline workflows that still synced with a central dashboard.\n\nWe built and shipped a cross-platform app for iOS and Android so coordination improved on site and routine jobs closed faster.",
                // Often blocks iframes — useful to test the “Open in new tab” fallback.
                'cta_url' => 'https://github.com',
                'cta_label' => 'View live',
                'cover_path' => 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?auto=format&fit=crop&w=1800&q=80',
                'sort_order' => 40,
            ],
            [
                'title' => 'Custom platform on AMD EPYC VPS',
                'client_name' => 'Product team',
                'summary' => 'Custom application migrated onto dedicated AMD EPYC VPS with root access, hardening, and growth headroom.',
                'outcome' => 'Better performance under load with predictable infrastructure control.',
                'description' => "A custom platform needed dedicated resources, root access, and room to grow.\n\nWe provisioned AMD EPYC VPS infrastructure, hardened security, migrated the stack, and stayed available for ongoing support.",
                'cta_url' => 'https://www.w3.org',
                'cta_label' => 'View live',
                'cover_path' => 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?auto=format&fit=crop&w=1800&q=80',
                'sort_order' => 50,
            ],
        ];

        foreach ($studies as $study) {
            $row = CaseStudy::query()->firstOrNew([
                'client_name' => $study['client_name'],
                'title' => $study['title'],
            ]);

            if (! $row->exists) {
                $row->slug = CaseStudy::uniqueSlug($study['title']);
            }

            $row->fill(array_merge($study, [
                'is_published' => true,
            ]));
            $row->save();
        }

        // Remove older service-style seed rows if still present.
        CaseStudy::query()
            ->whereIn('title', [
                'Web Development',
                'WordPress Hosting',
                'Business Email',
                'Mobile Development',
                'VPS Hosting',
            ])
            ->delete();
    }
}
