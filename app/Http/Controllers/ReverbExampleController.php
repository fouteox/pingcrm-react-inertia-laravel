<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ReverbExampleJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

final class ReverbExampleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('reverb-example');
    }

    #[Middleware('throttle:5,1')]
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'uuid' => 'required|uuid',
        ]);

        $request->session()->push('reverb_uuids', $validated['uuid']);

        ReverbExampleJob::dispatch($validated['uuid'], App::getLocale())
            ->delay(now()->addSeconds(5));

        return back()->with('success', __('Job Reverb successfully launched'));
    }
}
