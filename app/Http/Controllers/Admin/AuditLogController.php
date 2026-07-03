<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\AuditLogRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct(private AuditLogRepository $auditLogs)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->only('action');

        return view('admin.audit.index', [
            'logs' => $this->auditLogs->paginateWithFilters($filters),
            'filters' => $filters,
        ]);
    }
}
