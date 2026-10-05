{{-- Internal notes + admin history. Expects: $order, $notesUrl, $events. --}}
<div class="admin-customer-grid">
    <section class="admin-panel">
        <div class="admin-panel-toolbar compact">
            <h2 class="admin-dash-panel-title">Internal notes</h2>
        </div>
        <form method="POST" action="{{ $notesUrl }}" class="space-y-3" data-submit-form>
            @csrf
            @method('PUT')
            <textarea name="admin_notes" rows="5" class="admin-input w-full" placeholder="Only admins can see this.">{{ old('admin_notes', $order->admin_notes) }}</textarea>
            <button type="submit" class="admin-btn-ghost inline-flex items-center gap-2" data-submit-button>
                <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                <span data-submit-label>Save notes</span>
                <span class="hidden" data-submit-loading>Saving…</span>
            </button>
        </form>
    </section>

    <section class="admin-panel">
        <div class="admin-panel-toolbar compact">
            <h2 class="admin-dash-panel-title">Admin history</h2>
        </div>
        @if ($events->isEmpty())
            <p class="admin-table-empty">No admin actions yet.</p>
        @else
            <ul class="space-y-3 text-sm">
                @foreach ($events as $event)
                    <li>
                        <p><strong>{{ $event->summary }}</strong></p>
                        <p class="text-on-blush/60">{{ $event->created_at?->format('d M Y H:i') }} · {{ $event->admin?->name ?: 'System' }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
