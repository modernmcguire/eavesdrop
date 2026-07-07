<?php

namespace ModernMcGuire\Eavesdrop\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class Authorize
{
    public function handle(Request $request, Closure $next): mixed
    {
        abort_unless($this->allowed(), 403);

        return $next($request);
    }

    protected function allowed(): bool
    {
        return Gate::has('viewEavesdrop')
            ? Gate::allows('viewEavesdrop')
            : app()->environment('local');
    }
}
