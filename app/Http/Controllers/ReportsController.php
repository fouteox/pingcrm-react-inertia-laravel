<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;

final class ReportsController extends Controller
{
    public function index()
    {
        return Inertia::render('Reports/Index');
    }
}
