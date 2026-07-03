<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\PaymentRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(private PaymentRepository $payments)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->only('status', 'method', 'from', 'to');

        return view('admin.payments.index', [
            'payments' => $this->payments->paginateWithFilters($filters),
            'summary' => $this->payments->summaryTotals(),
            'methods' => $this->payments->methodLabels(),
            'filters' => $filters,
        ]);
    }
}
