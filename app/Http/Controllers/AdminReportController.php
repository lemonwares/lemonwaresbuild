<?php

namespace App\Http\Controllers;

use App\Support\AdminReports;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReportController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to] = $this->range($request);

        return view('admin.reports.index', [
            'from' => $from,
            'to' => $to,
            'summary' => AdminReports::summary($from, $to),
            'attention' => AdminReports::attention(),
            'products' => AdminReports::PRODUCTS,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->range($request);
        $sales = AdminReports::sales($from, $to);

        return response()->streamDownload(function () use ($sales) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Product', 'Order ID', 'Order', 'Customer', 'Amount (NGN)', 'Refunded (NGN)', 'Payment reference', 'Paid via']);
            foreach ($sales as $sale) {
                fputcsv($out, [
                    $sale['date']->format('Y-m-d H:i'),
                    AdminReports::PRODUCTS[$sale['product']],
                    $sale['id'],
                    $sale['label'],
                    $sale['customer'],
                    number_format($sale['amount_ngn'], 2, '.', ''),
                    number_format($sale['refunded_ngn'], 2, '.', ''),
                    $sale['reference'],
                    $sale['provider'],
                ]);
            }
            fclose($out);
        }, 'lemonwares-sales-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{0:CarbonImmutable,1:CarbonImmutable}
     */
    private function range(Request $request): array
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $to = $request->filled('to') ? CarbonImmutable::parse($request->input('to')) : CarbonImmutable::now();
        $from = $request->filled('from') ? CarbonImmutable::parse($request->input('from')) : $to->subMonths(5)->startOfMonth();

        if ($from->diffInDays($to) > 1100) {
            $from = $to->subYears(3);
        }

        return [$from, $to];
    }
}
