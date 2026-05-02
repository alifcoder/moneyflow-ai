<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TransactionTypeEnum;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ReportService $reports): Response
    {
        $user = $request->user();

        return Inertia::render('Dashboard/Index', [
            'dashboard' => $reports->dashboard($request, $user),
            'filters' => $reports->filters($request, $user),
            'options' => $reports->options($request, $user),
            'types' => [
                TransactionTypeEnum::INCOME->value,
                TransactionTypeEnum::EXPENSE->value,
            ],
            'canUseAllScope' => $user->isSuperAdmin(),
        ]);
    }
}
