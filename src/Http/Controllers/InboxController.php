<?php

namespace ModernMcGuire\Eavesdrop\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use ModernMcGuire\Eavesdrop\Eavesdrop;

class InboxController extends Controller
{
    public function index(): View
    {
        $channels = Eavesdrop::query()
            ->whereNotNull('channel')
            ->distinct()
            ->orderBy('channel')
            ->pluck('channel');

        return view('eavesdrop::index', compact('channels'));
    }
}
