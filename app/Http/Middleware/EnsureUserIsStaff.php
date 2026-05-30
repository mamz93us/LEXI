<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate the entire firm back-office to STAFF roles only
 * (partner | associate | paralegal | admin). The `client` role is for
 * the (future) client portal — a client account must never reach the
 * staff CRUD screens for cases, documents, AI drafts, invoices, etc.
 *
 * This is the coarse default-deny layer mandated by CLAUDE.md §7. Finer
 * role checks (only partners approve AI drafts, only partners/admins
 * manage users) live in the relevant policies/components on top of this.
 *
 * Also blocks any tenant-less account (tenant_id === null) from the
 * tenant back-office — belt-and-suspenders against a stray landlord/
 * super-admin row wandering into a tenant context.
 */
final class EnsureUserIsStaff
{
    private const STAFF_ROLES = [
        UserRole::Partner,
        UserRole::Associate,
        UserRole::Paralegal,
        UserRole::Admin,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        // Deactivated accounts lose access immediately. (is_active is cast
        // to bool + defaults true; only an explicit false locks out.)
        if ($user->is_active === false) {
            abort(403, 'هذا الحساب معطّل. تواصل مع مدير المكتب.');
        }

        if ($user->tenant_id === null) {
            abort(403, 'حساب غير مرتبط بمكتب.');
        }

        if (! in_array($user->role, self::STAFF_ROLES, true)) {
            abort(403, 'هذه الصفحة مخصصة لطاقم المكتب فقط.');
        }

        return $next($request);
    }
}
