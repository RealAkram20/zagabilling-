<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Repositories\PlanRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function __construct(private PlanRepository $plans)
    {
    }

    public function index(): View
    {
        return view('admin.plans.index', [
            'plans' => $this->plans->paginate(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePlan($request);

        $this->plans->create($data + ['grace_days' => 3]);

        return back()->with('status', 'Plan created.');
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $data = $this->validatePlan($request);

        $plan->update($data);

        return back()->with('status', 'Plan updated.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->devices()->exists()) {
            return back()->withErrors(['plan' => 'This plan is in use by one or more devices and cannot be deleted.']);
        }

        $plan->delete();

        return back()->with('status', 'Plan deleted.');
    }

    private function validatePlan(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'term_months' => ['required', 'integer', 'min:1', 'max:120'],
            'deposit_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'cadence' => ['required', 'in:monthly,biweekly,weekly'],
        ]);
    }
}
