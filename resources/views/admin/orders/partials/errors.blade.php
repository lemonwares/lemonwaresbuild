@if ($errors->has('order') || $errors->has('transaction_id') || $errors->has('reason') || $errors->has('reference'))
    <p class="mb-5 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">
        {{ $errors->first('order') ?: $errors->first('transaction_id') ?: $errors->first('reason') ?: $errors->first('reference') }}
    </p>
@endif
