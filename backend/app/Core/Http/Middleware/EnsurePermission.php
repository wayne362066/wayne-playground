<?php

namespace App\Core\Http\Middleware;

use App\Core\Access\AuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EnsurePermission
{
    public function __construct(
        private readonly AuthorizationService $authorization,
    ) {}

    public function handle(
        Request $request,
        Closure $next,
        string $permission,
    ): Response {
        if (! $this->authorization->allows($request->user(), $permission)) {
            throw new AccessDeniedHttpException;
        }

        return $next($request);
    }
}
