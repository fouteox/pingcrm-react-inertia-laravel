<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ReverbExampleJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

final class ReverbExampleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('ReverbExample/Index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'uuid' => 'required|uuid',
        ]);

        ReverbExampleJob::dispatch($request->uuid, App::getLocale());

        return back()->with('success', __('Job Reverb successfully launched'));
    }
}
