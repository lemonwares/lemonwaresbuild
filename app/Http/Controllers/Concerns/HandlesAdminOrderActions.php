<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait HandlesAdminOrderActions
{
    /**
     * @param  array{ok:bool,message:string}  $result
     */
    protected function backWithResult(string $url, array $result): RedirectResponse
    {
        $redirect = redirect()->to($url);

        return $result['ok']
            ? $redirect->with('status', $result['message'])
            : $redirect->withErrors(['order' => $result['message']]);
    }

    /**
     * @return array{reference:?string,note:?string}
     */
    protected function validateMarkPaid(Request $request): array
    {
        $data = $request->validate([
            'reference' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:1000'],
            'confirm' => ['accepted'],
        ], [
            'confirm.accepted' => 'Tick the box to confirm the money has been received.',
        ]);

        return ['reference' => $data['reference'] ?? null, 'note' => $data['note'] ?? null];
    }

    /**
     * @return array{amount_ngn:float,reference:?string,note:?string}
     */
    protected function validateRefund(Request $request): array
    {
        $data = $request->validate([
            'amount_ngn' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        return [
            'amount_ngn' => (float) $data['amount_ngn'],
            'reference' => $data['reference'] ?? null,
            'note' => $data['note'] ?? null,
        ];
    }
}
