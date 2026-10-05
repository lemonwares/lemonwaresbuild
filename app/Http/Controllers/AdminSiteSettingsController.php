<?php

namespace App\Http\Controllers;

use App\Support\ExchangeRate;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminSiteSettingsController extends Controller
{
    public const GROUPS = [
        'general' => 'Company & contact',
        'social' => 'Social links',
        'currency' => 'Exchange rate',
        'google' => 'Google reviews',
        'maintenance' => 'Maintenance mode',
    ];

    public function index(): View
    {
        $fields = SiteSettings::fields();
        $values = [];
        foreach (array_keys($fields) as $path) {
            $values[$path] = SiteSettings::value($path);
        }

        return view('admin.site-settings.index', [
            'groups' => self::GROUPS,
            'fields' => $fields,
            'values' => $values,
            'liveRate' => ExchangeRate::usdToNgn(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [];
        foreach (SiteSettings::fields() as $path => $field) {
            $rules[$this->inputName($path)] = match ($field['type']) {
                'email' => ['nullable', 'email', 'max:190'],
                'url' => ['nullable', 'string', 'max:500', function ($attribute, $value, $fail) {
                    if ($value !== '-' && ! filter_var($value, FILTER_VALIDATE_URL)) {
                        $fail('Enter a full link starting with https://, or "-" to hide it.');
                    }
                }],
                'number' => ['nullable', 'numeric', 'min:0'],
                'boolean' => ['nullable', 'boolean'],
                'select' => ['nullable', Rule::in(['auto', 'manual'])],
                default => ['nullable', 'string', 'max:1000'],
            };
        }

        $request->validate($rules);

        if ($request->input($this->inputName('currency.mode')) === 'manual' && (float) $request->input($this->inputName('currency.manual_rate')) <= 0) {
            return back()->withInput()->withErrors([$this->inputName('currency.manual_rate') => 'Enter the manual rate, or switch the source back to automatic.']);
        }

        $values = [];
        foreach (SiteSettings::fields() as $path => $field) {
            $value = $request->input($this->inputName($path));
            if ($field['type'] === 'boolean') {
                $value = $request->boolean($this->inputName($path)) ? '1' : '0';
            }
            if ($field['type'] === 'secret' && blank($value)) {
                continue;
            }
            $values[$path] = is_scalar($value) ? (string) $value : null;
        }

        SiteSettings::save($values);

        return redirect()->route('admin.site-settings.index')->with('status', 'Settings saved. The website uses them straight away.');
    }

    public function refreshRate(): RedirectResponse
    {
        $rate = ExchangeRate::refresh();

        return redirect()->route('admin.site-settings.index')->with('status', 'Exchange rate now ₦'.number_format($rate, 2).' per $1.');
    }

    private function inputName(string $path): string
    {
        return str_replace('.', '__', $path);
    }
}
