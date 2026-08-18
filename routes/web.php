<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminEmailCatalogController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminCustomerController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminEmailOrderController;
use App\Http\Controllers\AdminHostingLeadController;
use App\Http\Controllers\AdminHostingPriceController;
use App\Http\Controllers\AdminSubscriberController;
use App\Http\Controllers\AdminTeamMemberController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\EmailOrderController;
use App\Http\Middleware\EnsureAdminAuthenticated;
use App\Models\HostingLead;
use App\Models\HostingPlanPrice;
use App\Models\NewsletterSubscriber;
use App\Models\TeamMember;
use App\Support\FlutterwavePayment;
use App\Support\HostingPlanPriceSync;
use App\Support\HostingPricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::view('/about', 'pages.about')->name('about');
Route::view('/contact', 'pages.contact')->name('contact');
Route::view('/case-studies', 'pages.case-studies')->name('case-studies');
Route::view('/faq', 'pages.faq')->name('faq');
Route::view('/terms', 'pages.terms')->name('terms');
Route::view('/refund-policy', 'pages.refund-policy')->name('refund-policy');
Route::view('/privacy-policy', 'pages.privacy-policy')->name('privacy-policy');
Route::view('/usage-terms', 'pages.usage-terms')->name('usage-terms');

Route::get('/team', function () {
    $members = TeamMember::query()
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get();

    return view('team', compact('members'));
})->name('team');

Route::get('/locale/{locale}', function (string $locale) {
    $supported = array_keys(config('site.locales', ['en' => 'English']));
    abort_unless(in_array($locale, $supported, true), 404);

    session(['locale' => $locale]);

    $previous = url()->previous();
    $fallback = route('home');

    if (! is_string($previous) || $previous === '' || str_contains($previous, '/locale/')) {
        return redirect()->to($fallback);
    }

    return redirect()->to($previous);
})->name('locale.switch');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:8,1')->name('register.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:8,1')->name('password.update');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::get('/email', [EmailOrderController::class, 'plans'])->name('email.plans');
Route::get('/email/payment/flutterwave/callback', [EmailOrderController::class, 'callback'])->name('email.flutterwave.callback');

Route::middleware('auth')->group(function (): void {
    Route::get('/account', [AccountController::class, 'show'])->name('account.show');
    Route::get('/account/profile', [AccountController::class, 'profile'])->name('account.profile');
    Route::put('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::get('/account/settings', [AccountController::class, 'settings'])->name('account.settings');
    Route::post('/account/settings/contacts', [AccountController::class, 'storeContact'])->middleware('throttle:20,1')->name('account.contacts.store');
    Route::delete('/account/settings/contacts/{contact}', [AccountController::class, 'destroyContact'])->name('account.contacts.destroy');
    Route::get('/account/email', [AccountController::class, 'email'])->name('account.email.index');
    Route::get('/account/email/{order}', [EmailOrderController::class, 'show'])->name('account.email.show');
    Route::post('/account/email/{order}/pay', [EmailOrderController::class, 'pay'])->middleware('throttle:10,1')->name('email.pay');
    Route::post('/account/email/{order}/provision', [EmailOrderController::class, 'provision'])->middleware('throttle:5,1')->name('email.provision');
    Route::get('/account/vps', [AccountController::class, 'vps'])->name('account.vps.index');
    Route::get('/account/vps/{lead}', [AccountController::class, 'vpsShow'])->name('account.vps.show');
    Route::get('/account/hosting', [AccountController::class, 'hosting'])->name('account.hosting.index');
    Route::get('/account/hosting/{lead}', [AccountController::class, 'hostingShow'])->name('account.hosting.show');
    Route::get('/email/checkout', [EmailOrderController::class, 'create'])->name('email.checkout');
    Route::post('/email/checkout', [EmailOrderController::class, 'store'])->middleware('throttle:10,1')->name('email.checkout.store');
});

Route::get('/hosting/request', function (Request $request) {
    $planOptions = config('site.hosting_plans', []);
    abort_if(empty($planOptions), 404);

    $requestedPlan = strtolower((string) $request->query('plan', ''));
    $planSlug = array_key_exists($requestedPlan, $planOptions)
        ? $requestedPlan
        : array_key_first($planOptions);
    $plan = $planOptions[$planSlug];
    HostingPlanPriceSync::sync();

    $billingCycles = HostingPricing::billingCycles();
    $selectedBillingCycle = strtolower((string) $request->query('billing_cycle', 'monthly'));
    if (! array_key_exists($selectedBillingCycle, $billingCycles)) {
        $selectedBillingCycle = 'monthly';
    }

    $priceMap = HostingPlanPrice::query()
        ->where('plan_slug', $planSlug)
        ->where('is_visible', true)
        ->get()
        ->keyBy('spec_key');

    $specifications = collect($plan['specifications'] ?? [])
        ->map(function (array $spec) use ($priceMap, $selectedBillingCycle) {
            $price = $priceMap->get($spec['key'] ?? '');
            $hasPrice = $price && (float) $price->price_amount > 0;

            if ($hasPrice) {
                $payload = HostingPricing::pricePayload($price, $selectedBillingCycle);
                $spec = array_merge($spec, $payload);
                $spec['price_amount'] = $payload['monthly_usd'];
            } else {
                $spec['price_display'] = null;
                $spec['period_display'] = null;
                $spec['price_amount'] = null;
                $spec['billing_cycle_label'] = HostingPricing::cycleLabel($selectedBillingCycle);
                $spec['discount_percent'] = (int) (HostingPricing::cycle($selectedBillingCycle)['discount_percent'] ?? 0);
            }

            return $spec;
        })
        ->values()
        ->all();

    $rawSpecs = $request->query('spec', []);
    if (! is_array($rawSpecs)) {
        $rawSpecs = $rawSpecs !== null && $rawSpecs !== '' ? [$rawSpecs] : [];
    }

    $validKeys = collect($specifications)
        ->pluck('key')
        ->map(fn ($key) => strtolower((string) $key))
        ->all();

    $selectedSpecKeys = collect($rawSpecs)
        ->map(fn ($key) => strtolower((string) $key))
        ->filter(fn ($key) => in_array($key, $validKeys, true))
        ->values()
        ->all();

    $usdToNgn = HostingPricing::usdToNgnRate();

    return view('pages.hosting-specifications', compact(
        'plan',
        'planSlug',
        'specifications',
        'selectedSpecKeys',
        'billingCycles',
        'selectedBillingCycle',
        'usdToNgn'
    ));
})->name('hosting.specifications');

Route::get('/hosting/request/details', function (Request $request) {
    $planOptions = config('site.hosting_plans', []);
    abort_if(empty($planOptions), 404);

    $requestedPlan = strtolower((string) $request->query('plan', ''));
    $selectedPlan = array_key_exists($requestedPlan, $planOptions)
        ? $requestedPlan
        : array_key_first($planOptions);
    $plan = $planOptions[$selectedPlan];
    $specifications = $plan['specifications'] ?? [];
    $specMap = collect($specifications)->keyBy(fn ($item) => strtolower((string) ($item['key'] ?? '')));

    $billingCycles = HostingPricing::billingCycles();
    $selectedBillingCycle = strtolower((string) $request->query('billing_cycle', 'monthly'));
    if (! array_key_exists($selectedBillingCycle, $billingCycles)) {
        $selectedBillingCycle = 'monthly';
    }

    $rawSpecs = $request->query('spec', []);
    if (! is_array($rawSpecs)) {
        $rawSpecs = $rawSpecs !== null && $rawSpecs !== '' ? [$rawSpecs] : [];
    }

    $selectedSpecKeys = collect($rawSpecs)
        ->map(fn ($key) => strtolower((string) $key))
        ->filter(fn ($key) => $specMap->has($key))
        ->unique()
        ->values()
        ->all();

    if ($selectedSpecKeys === []) {
        return redirect()
            ->route('hosting.specifications', ['plan' => $selectedPlan])
            ->with('hosting_feedback', [
                'type' => 'error',
                'message' => 'Please select at least one hosting specification to continue.',
            ]);
    }

    $priceMap = HostingPlanPrice::query()
        ->where('plan_slug', $selectedPlan)
        ->get()
        ->keyBy(fn ($item) => strtolower((string) $item->spec_key));

    $selectedSpecsData = collect($selectedSpecKeys)
        ->map(function ($key) use ($specMap, $priceMap, $selectedBillingCycle) {
            $spec = $specMap->get($key);
            if (! $spec) {
                return null;
            }

            $price = $priceMap->get($key);
            $hasPrice = $price && (float) $price->price_amount > 0;

            if ($hasPrice) {
                $payload = HostingPricing::pricePayload($price, $selectedBillingCycle);
                $spec = array_merge($spec, $payload);
            } else {
                $spec['price_display'] = null;
                $spec['period_display'] = null;
                $spec['billing_cycle_label'] = HostingPricing::cycleLabel($selectedBillingCycle);
            }

            return $spec;
        })
        ->filter()
        ->values()
        ->all();

    $selectedSpec = implode(',', $selectedSpecKeys);
    $selectedSpecData = $selectedSpecsData[0] ?? null;
    $orderTotalUsd = collect($selectedSpecsData)->sum(fn ($spec) => (float) ($spec['period_usd'] ?? 0));

    return view('pages.hosting-intake', [
        'planOptions' => $planOptions,
        'selectedPlan' => $selectedPlan,
        'selectedPlanData' => $plan,
        'selectedSpec' => $selectedSpec,
        'selectedSpecKeys' => $selectedSpecKeys,
        'selectedSpecData' => $selectedSpecData,
        'selectedSpecsData' => $selectedSpecsData,
        'selectedBillingCycle' => $selectedBillingCycle,
        'orderTotalUsd' => $orderTotalUsd,
        'orderTotalDisplay' => HostingPricing::dualPriceDisplay($orderTotalUsd),
    ]);
})->name('hosting.intake');

Route::get('/hosting/request/received/{lead}', function (HostingLead $lead) {
    return view('pages.hosting-order-received', compact('lead'));
})->name('hosting.order-received');

Route::get('/hosting/payment/flutterwave/callback', function (Request $request) {
    $status = strtolower((string) $request->query('status', ''));
    $txRef = (string) $request->query('tx_ref', '');
    $transactionId = (string) $request->query('transaction_id', '');

    $lead = HostingLead::query()
        ->where('payment_reference', $txRef)
        ->first();

    if (! $lead) {
        return redirect()
            ->route('home')
            ->with('hosting_feedback', [
                'type' => 'error',
                'message' => 'We could not match that payment to an order.',
            ]);
    }

    if ($status !== 'successful' || $transactionId === '') {
        $lead->update([
            'payment_status' => $status ?: 'failed',
            'status' => 'payment_failed',
        ]);

        return redirect()
            ->route('hosting.order-received', $lead)
            ->with('hosting_feedback', [
                'type' => 'error',
                'message' => 'Payment was not completed. You can try again from this page.',
            ]);
    }

    $verified = FlutterwavePayment::verifyTransaction($transactionId);

    if (! $verified || strtolower((string) data_get($verified, 'status')) !== 'successful') {
        $lead->update([
            'payment_status' => 'unverified',
            'status' => 'payment_failed',
        ]);

        return redirect()
            ->route('hosting.order-received', $lead)
            ->with('hosting_feedback', [
                'type' => 'error',
                'message' => 'We could not verify your payment yet. Please contact support if you were charged.',
            ]);
    }

    $paidAmount = (float) data_get($verified, 'amount', 0);
    $expected = (float) ($lead->amount_ngn ?? 0);
    $currency = strtoupper((string) data_get($verified, 'currency', ''));

    if ($currency !== 'NGN' || abs($paidAmount - $expected) > 1) {
        $lead->update([
            'payment_status' => 'amount_mismatch',
            'status' => 'payment_failed',
            'flutterwave_transaction_id' => (string) data_get($verified, 'id'),
        ]);

        return redirect()
            ->route('hosting.order-received', $lead)
            ->with('hosting_feedback', [
                'type' => 'error',
                'message' => 'Payment verification failed amount checks. Please contact support.',
            ]);
    }

    $lead->update([
        'payment_status' => 'successful',
        'status' => 'paid',
        'flutterwave_transaction_id' => (string) data_get($verified, 'id'),
    ]);

    return redirect()
        ->route('hosting.order-received', $lead)
        ->with('hosting_feedback', [
            'type' => 'success',
            'message' => 'Payment confirmed. Our team will provision your VPS next.',
        ]);
})->name('hosting.flutterwave.callback');

Route::post('/hosting/request/received/{lead}/pay', function (HostingLead $lead) {
    abort_unless(($lead->checkout_provider === 'internal' || $lead->payment_provider === 'flutterwave'), 404);

    $link = FlutterwavePayment::createPaymentLink($lead);

    if (! $link) {
        return redirect()
            ->route('hosting.order-received', $lead)
            ->with('hosting_feedback', [
                'type' => 'error',
                'message' => 'Unable to start Flutterwave checkout right now. Please try again shortly.',
            ]);
    }

    return redirect()->away($link);
})->middleware('throttle:10,1')->name('hosting.flutterwave.pay');

Route::post('/hosting/request/details', function (Request $request) {
    $planOptions = config('site.hosting_plans', []);
    $whmcsConfig = config('site.whmcs', []);
    $billingCycles = HostingPricing::billingCycles();

    $rawPhone = trim((string) $request->input('phone', ''));
    $digitsOnly = preg_replace('/\D+/', '', $rawPhone) ?? '';
    $normalizedPhone = $digitsOnly !== '' ? '+' . $digitsOnly : '';
    $request->merge(['phone' => $normalizedPhone]);

    $validator = Validator::make($request->all(), [
        'full_name' => ['required', 'string', 'max:120'],
        'email' => ['required', 'email', 'max:160'],
        'phone' => ['required', 'regex:/^\+[1-9]\d{6,14}$/'],
        'company' => ['nullable', 'string', 'max:120'],
        'billing_address_line_1' => ['required', 'string', 'max:180'],
        'billing_address_line_2' => ['nullable', 'string', 'max:180'],
        'billing_city' => ['required', 'string', 'max:120'],
        'billing_state' => ['required', 'string', 'max:120'],
        'billing_postcode' => ['required', 'string', 'max:30'],
        'billing_country' => ['required', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
        'plan' => ['required', 'string'],
        'spec' => ['required', 'string', 'max:500'],
        'billing_cycle' => ['required', 'string', 'in:' . implode(',', array_keys($billingCycles))],
        'notes' => ['nullable', 'string', 'max:2000'],
    ]);

    if ($validator->fails()) {
        return back()
            ->withErrors($validator)
            ->withInput()
            ->with('hosting_feedback', [
                'type' => 'error',
                'message' => $validator->errors()->first(),
            ]);
    }

    $payload = $validator->validated();
    $planSlug = strtolower($payload['plan']);
    $selectedPlan = $planOptions[$planSlug] ?? null;
    $billingCycle = $payload['billing_cycle'];

    if (! $selectedPlan) {
        return back()
            ->withInput()
            ->with('hosting_feedback', [
                'type' => 'error',
                'message' => 'The selected plan is unavailable. Please choose another hosting option.',
            ]);
    }

    $specifications = $selectedPlan['specifications'] ?? [];
    $specMap = collect($specifications)->keyBy(fn ($spec) => strtolower((string) ($spec['key'] ?? '')));

    $selectedSpecKeys = collect(explode(',', (string) ($payload['spec'] ?? '')))
        ->map(fn ($key) => strtolower(trim($key)))
        ->filter(fn ($key) => $key !== '' && $specMap->has($key))
        ->unique()
        ->values()
        ->all();

    $selectedSpecsData = collect($selectedSpecKeys)
        ->map(fn ($key) => $specMap->get($key))
        ->filter()
        ->values()
        ->all();

    if ($selectedSpecsData === []) {
        return back()
            ->withInput()
            ->with('hosting_feedback', [
                'type' => 'error',
                'message' => 'Please choose a valid service specification before continuing.',
            ]);
    }

    $priceMap = HostingPlanPrice::query()
        ->where('plan_slug', $planSlug)
        ->get()
        ->keyBy(fn ($item) => strtolower((string) $item->spec_key));

    $amountUsd = collect($selectedSpecKeys)->sum(function ($key) use ($priceMap, $billingCycle) {
        $price = $priceMap->get($key);
        if (! $price || (float) $price->price_amount <= 0) {
            return 0;
        }

        return HostingPricing::periodTotalUsd((float) $price->price_amount, $billingCycle);
    });
    $amountNgn = $amountUsd * HostingPricing::usdToNgnRate();

    $specLabel = collect($selectedSpecsData)->pluck('label')->filter()->join(', ');
    $specKeyJoined = implode(',', $selectedSpecKeys);
    $checkoutProvider = (string) ($selectedPlan['checkout_provider'] ?? 'whmcs');

    $whmcsPid = (string) ($selectedPlan['whmcs_pid'] ?? '');
    $whmcsBaseUrl = rtrim((string) ($whmcsConfig['base_url'] ?? ''), '/');
    $whmcsOrderRoute = '/' . ltrim((string) ($whmcsConfig['order_route'] ?? '/cart.php'), '/');
    $nameParts = preg_split('/\s+/', trim($payload['full_name'])) ?: [];
    $firstname = $nameParts[0] ?? '';
    $lastname = trim(implode(' ', array_slice($nameParts, 1)));
    if ($lastname === '') {
        $lastname = $firstname;
    }

    $specSummary = collect($selectedSpecsData)
        ->map(function ($spec) {
            $metrics = collect($spec)
                ->reject(fn ($value, $key) => in_array($key, ['key', 'label', 'description', 'highlights', 'details', 'default_price', 'default_currency', 'default_billing_cycle', 'default_suffix'], true) || is_array($value))
                ->map(fn ($value, $key) => strtoupper((string) $key) . ': ' . $value)
                ->values()
                ->join(' | ');

            return trim(($spec['label'] ?? 'Spec') . ($metrics !== '' ? ' (' . $metrics . ')' : ''));
        })
        ->join('; ');

    $leadPayload = [
        'user_id' => $request->user()?->id,
        'full_name' => $payload['full_name'],
        'email' => strtolower($payload['email']),
        'phone' => $payload['phone'],
        'company' => $payload['company'] ?? null,
        'billing_address_line_1' => $payload['billing_address_line_1'],
        'billing_address_line_2' => $payload['billing_address_line_2'] ?? null,
        'billing_city' => $payload['billing_city'],
        'billing_state' => $payload['billing_state'],
        'billing_postcode' => $payload['billing_postcode'],
        'billing_country' => strtoupper($payload['billing_country']),
        'plan_slug' => $planSlug,
        'plan_name' => $selectedPlan['title'],
        'spec_key' => $specKeyJoined,
        'spec_label' => $specLabel ?: null,
        'spec_summary' => $specSummary ?: null,
        'billing_cycle' => $billingCycle,
        'amount_usd' => $amountUsd,
        'amount_ngn' => $amountNgn,
        'checkout_provider' => $checkoutProvider,
        'status' => 'pending',
        'notes' => $payload['notes'] ?? null,
        'whmcs_pid' => $whmcsPid ?: null,
        'checkout_url' => null,
        'source_url' => $request->headers->get('referer'),
        'ip_address' => $request->ip(),
    ];

    if ($checkoutProvider === 'internal') {
        $lead = HostingLead::create($leadPayload);

        $paymentLink = FlutterwavePayment::createPaymentLink($lead);

        if ($paymentLink) {
            return redirect()->away($paymentLink);
        }

        return redirect()
            ->route('hosting.order-received', $lead)
            ->with('hosting_feedback', [
                'type' => 'info',
                'message' => 'Order saved. Payment checkout is temporarily unavailable — use Pay Now when ready.',
            ]);
    }

    if ($whmcsBaseUrl === '' || $whmcsPid === '') {
        logger()->warning('WHMCS checkout mapping missing', [
            'plan_slug' => $planSlug,
            'whmcs_base_url' => $whmcsBaseUrl,
            'whmcs_pid' => $whmcsPid,
        ]);

        HostingLead::create($leadPayload);

        return back()
            ->withInput()
            ->with('hosting_feedback', [
                'type' => 'error',
                'message' => 'Checkout is temporarily unavailable for this plan. Please try again shortly.',
            ]);
    }

    $whmcsCycle = (string) ($billingCycles[$billingCycle]['whmcs'] ?? 'monthly');

    $checkoutUrl = $whmcsBaseUrl . $whmcsOrderRoute . '?' . http_build_query([
        'a' => 'add',
        'pid' => $whmcsPid,
        'billingcycle' => $whmcsCycle,
        'email' => strtolower($payload['email']),
        'firstname' => $firstname,
        'lastname' => $lastname,
        'phonenumber' => $payload['phone'],
        'companyname' => $payload['company'] ?? '',
        'address1' => $payload['billing_address_line_1'],
        'address2' => $payload['billing_address_line_2'] ?? '',
        'city' => $payload['billing_city'],
        'state' => $payload['billing_state'],
        'postcode' => $payload['billing_postcode'],
        'country' => strtoupper($payload['billing_country']),
        'promocode' => strtoupper($specKeyJoined),
    ]);

    $leadPayload['checkout_url'] = $checkoutUrl;
    $leadPayload['status'] = 'redirected_whmcs';
    HostingLead::create($leadPayload);

    return redirect()->away($checkoutUrl);
})->middleware('throttle:10,1')->name('hosting.intake.submit');

Route::get('/location/countries', function () {
    try {
        $response = Http::timeout(10)->get('https://countriesnow.space/api/v0.1/countries/iso');

        if (! $response->successful()) {
            return response()->json(['data' => []], 200);
        }

        $countries = collect($response->json('data', []))
            ->filter(fn ($item) => ! empty($item['name']) && ! empty($item['Iso2']))
            ->map(fn ($item) => [
                'label' => $item['name'],
                'value' => strtoupper($item['Iso2']),
                'countryName' => $item['name'],
            ])
            ->sortBy('label')
            ->values()
            ->all();

        return response()->json(['data' => $countries]);
    } catch (\Throwable $exception) {
        logger()->warning('Country API fetch failed', ['error' => $exception->getMessage()]);
        return response()->json(['data' => []], 200);
    }
})->name('location.countries');

Route::post('/location/states', function (Request $request) {
    $country = trim((string) $request->input('country'));

    if ($country === '') {
        return response()->json(['data' => []], 200);
    }

    try {
        $response = Http::timeout(10)
            ->post('https://countriesnow.space/api/v0.1/countries/states', ['country' => $country]);

        if (! $response->successful()) {
            return response()->json(['data' => []], 200);
        }

        $states = collect($response->json('data.states', []))
            ->filter(fn ($item) => ! empty($item['name']))
            ->map(fn ($item) => [
                'label' => $item['name'],
                'value' => $item['name'],
            ])
            ->values()
            ->all();

        return response()->json(['data' => $states]);
    } catch (\Throwable $exception) {
        logger()->warning('State API fetch failed', [
            'country' => $country,
            'error' => $exception->getMessage(),
        ]);
        return response()->json(['data' => []], 200);
    }
})->name('location.states');

Route::post('/location/cities', function (Request $request) {
    $country = trim((string) $request->input('country'));
    $state = trim((string) $request->input('state'));

    if ($country === '' || $state === '') {
        return response()->json(['data' => []], 200);
    }

    try {
        $response = Http::timeout(10)->post('https://countriesnow.space/api/v0.1/countries/state/cities', [
            'country' => $country,
            'state' => $state,
        ]);

        if (! $response->successful()) {
            return response()->json(['data' => []], 200);
        }

        $cities = collect($response->json('data', []))
            ->map(fn ($city) => [
                'label' => $city,
                'value' => $city,
            ])
            ->values()
            ->all();

        return response()->json(['data' => $cities]);
    } catch (\Throwable $exception) {
        logger()->warning('City API fetch failed', [
            'country' => $country,
            'state' => $state,
            'error' => $exception->getMessage(),
        ]);
        return response()->json(['data' => []], 200);
    }
})->name('location.cities');

Route::post('/newsletter-subscribe', function (Request $request) {
    $previousUrl = $request->headers->get('referer') ?: url('/');
    $redirectToNewsletter = str($previousUrl)->before('#')->append('#footer-newsletter')->toString();

    $validator = Validator::make($request->all(), [
        'full_name' => ['required', 'string', 'max:120'],
        'email' => ['required', 'email', 'max:160'],
    ]);

    if ($validator->fails()) {
        return redirect()
            ->to($redirectToNewsletter)
            ->withInput()
            ->with('newsletter_feedback', [
                'type' => 'error',
                'message' => $validator->errors()->first(),
            ]);
    }

    $data = $validator->validated();

    $email = strtolower(trim($data['email']));
    $existingSubscriber = NewsletterSubscriber::query()
        ->where('email', $email)
        ->first();

    if ($existingSubscriber) {
        return redirect()
            ->to($redirectToNewsletter)
            ->withInput()
            ->with('newsletter_feedback', [
                'type' => 'info',
                'message' => "You're already on our newsletter list.",
            ]);
    }

    NewsletterSubscriber::create([
        'full_name' => $data['full_name'],
        'email' => $email,
    ]);

    return redirect()
        ->to($redirectToNewsletter)
        ->with('newsletter_feedback', [
            'type' => 'success',
            'message' => 'Thanks for subscribing to our newsletter.',
        ]);
})->middleware('throttle:10,1')->name('newsletter.subscribe');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.submit');

    Route::middleware([EnsureAdminAuthenticated::class])->group(function (): void {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/customers', [AdminCustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [AdminCustomerController::class, 'show'])->name('customers.show');
        Route::get('/hosting-leads', [AdminHostingLeadController::class, 'index'])->name('hosting-leads.index');
        Route::get('/hosting-leads/{hostingLead}', [AdminHostingLeadController::class, 'show'])->name('hosting-leads.show');
        Route::get('/subscribers', [AdminSubscriberController::class, 'index'])->name('subscribers.index');
        Route::resource('team-members', AdminTeamMemberController::class)->except(['show']);
        Route::get('/hosting-prices', [AdminHostingPriceController::class, 'index'])->name('hosting-prices.index');
        Route::put('/hosting-prices', [AdminHostingPriceController::class, 'update'])->name('hosting-prices.update');
        Route::get('/email-catalog', [AdminEmailCatalogController::class, 'index'])->name('email-catalog.index');
        Route::put('/email-catalog', [AdminEmailCatalogController::class, 'update'])->name('email-catalog.update');
        Route::get('/email-orders', [AdminEmailOrderController::class, 'index'])->name('email-orders.index');
        Route::get('/email-orders/{emailOrder}', [AdminEmailOrderController::class, 'show'])->name('email-orders.show');
        Route::post('/email-orders/{emailOrder}/provision', [AdminEmailOrderController::class, 'provision'])->name('email-orders.provision');
    });
});
