<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires an active, usable tenant in context. Apply to every tenant-scoped
 * route group (after resolve.tenant).
 */
final class EnsureTenant
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->context->has()) {
            abort(403, 'No hay un espacio de trabajo activo para esta solicitud.');
        }

        if (! $this->context->require()->isActive()) {
            abort(403, 'Este espacio de trabajo no está activo.');
        }

        return $next($request);
    }
}
