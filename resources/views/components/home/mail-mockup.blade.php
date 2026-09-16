@props([
    'compact' => false,
])

@php
    $folders = [
        ['label' => 'Inbox', 'count' => '12', 'active' => true, 'muted' => false],
        ['label' => 'Starred', 'count' => null, 'active' => false, 'muted' => false],
        ['label' => 'Snoozed', 'count' => null, 'active' => false, 'muted' => false],
        ['label' => 'Sent', 'count' => null, 'active' => false, 'muted' => false],
        ['label' => 'Drafts', 'count' => '3', 'active' => false, 'muted' => true],
        ['label' => 'Archive', 'count' => null, 'active' => false, 'muted' => false],
        ['label' => 'Spam', 'count' => null, 'active' => false, 'muted' => false],
        ['label' => 'Trash', 'count' => null, 'active' => false, 'muted' => false],
    ];

    $labels = [
        ['label' => 'Clients', 'tone' => 'pink'],
        ['label' => 'Invoices', 'tone' => 'amber'],
        ['label' => 'Projects', 'tone' => 'green'],
        ['label' => 'Personal', 'tone' => 'violet'],
    ];

    $rows = [
        [
            'from' => 'Billing · Acme',
            'subject' => 'Invoice #INV-3921',
            'preview' => 'Please find the invoice for June 2024 attached.',
            'meta' => '10:24 AM',
            'unread' => true,
            'active' => true,
            'badge' => null,
            'starred' => false,
        ],
        [
            'from' => 'Mailemon',
            'subject' => 'Welcome to Mailemon!',
            'preview' => 'Thanks for signing up. Let’s get you started.',
            'meta' => 'Yesterday',
            'unread' => true,
            'active' => false,
            'badge' => '2',
            'starred' => false,
        ],
        [
            'from' => 'Product · Nora',
            'subject' => 'Project update',
            'preview' => 'Q3 roadmap and key milestones attached.',
            'meta' => 'Yesterday',
            'unread' => false,
            'active' => false,
            'badge' => null,
            'starred' => true,
        ],
        [
            'from' => 'Support · James',
            'subject' => 'DNS records are ready',
            'preview' => 'Add these MX and SPF records to finish setup.',
            'meta' => 'Mon',
            'unread' => false,
            'active' => false,
            'badge' => null,
            'starred' => false,
        ],
        [
            'from' => 'Sales · Ada',
            'subject' => 'Quote for 12 mailboxes',
            'preview' => 'Here is the Team plan pricing you asked for.',
            'meta' => 'Sun',
            'unread' => true,
            'active' => false,
            'badge' => null,
            'starred' => false,
        ],
        [
            'from' => 'Ops · Kemi',
            'subject' => 'Mailbox migration complete',
            'preview' => 'All 8 addresses moved cleanly. Webmail is live.',
            'meta' => 'Sat',
            'unread' => false,
            'active' => false,
            'badge' => null,
            'starred' => false,
        ],
    ];

    if ($compact) {
        $folders = array_slice($folders, 0, 5);
        $rows = array_slice($rows, 0, 4);
    }
@endphp

<div {{ $attributes->class(['mail-mockup', 'mail-mockup-compact' => $compact]) }} aria-hidden="true">
    <div class="mail-mockup-shell">
        <div class="mail-mockup-chrome">
            <div class="mail-mockup-brand">
                <span class="mail-mockup-lemon"></span>
                <span class="mail-mockup-brand-text">Mailemon</span>
            </div>
            <div class="mail-mockup-tools">
                <span class="mail-mockup-tool" title="Search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                </span>
                <span class="mail-mockup-tool is-alert" title="Notifications">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                </span>
                <span class="mail-mockup-avatar"></span>
                <span class="mail-mockup-chevron"></span>
            </div>
        </div>

        <div class="mail-mockup-body">
            <aside class="mail-mockup-nav">
                <div class="mail-mockup-compose">
                    <span>+ Compose</span>
                </div>

                @foreach ($folders as $folder)
                    <div @class(['mail-mockup-nav-item', 'is-active' => $folder['active']])>
                        <span class="mail-mockup-nav-label">{{ $folder['label'] }}</span>
                        @if ($folder['count'])
                            <span @class(['mail-mockup-count', 'is-muted' => $folder['muted']])>{{ $folder['count'] }}</span>
                        @endif
                    </div>
                @endforeach

                @unless ($compact)
                    <div class="mail-mockup-labels-head">
                        <span>Labels</span>
                        <span class="mail-mockup-labels-plus">+</span>
                    </div>
                    @foreach ($labels as $label)
                        <div class="mail-mockup-label-item">
                            <span @class(['mail-mockup-label-dot', 'tone-'.$label['tone']])></span>
                            <span>{{ $label['label'] }}</span>
                        </div>
                    @endforeach
                @endunless
            </aside>

            <div class="mail-mockup-inbox">
                <div class="mail-mockup-toolbar">
                    <span class="mail-mockup-toolbar-mark"></span>
                    <span class="mail-mockup-tool-dark">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 3v6h-6"/></svg>
                    </span>
                    <span class="mail-mockup-tool-dark">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 20h16"/><path d="M4 4h5l2 3h9v13H4z"/></svg>
                    </span>
                    <span class="mail-mockup-tool-dark">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                    </span>
                    <span class="mail-mockup-toolbar-meta">1–{{ count($rows) }} of 128</span>
                </div>

                @foreach ($rows as $row)
                    <div @class(['mail-mockup-row', 'is-active' => $row['active'], 'is-unread' => $row['unread']])>
                        <span @class(['mail-mockup-star', 'is-on' => $row['starred']])></span>
                        <div class="mail-mockup-row-copy">
                            <p class="mail-mockup-from">{{ $row['from'] }}</p>
                            <p class="mail-mockup-subject">{{ $row['subject'] }}</p>
                            <p class="mail-mockup-preview">{{ $row['preview'] }}</p>
                        </div>
                        <div class="mail-mockup-row-meta">
                            @if ($row['badge'])
                                <span class="mail-mockup-badge">{{ $row['badge'] }}</span>
                            @elseif ($row['unread'])
                                <span class="mail-mockup-unread"></span>
                            @endif
                            <span>{{ $row['meta'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
