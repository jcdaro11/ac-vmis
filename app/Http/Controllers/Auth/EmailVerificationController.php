<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmailVerificationController extends Controller
{
    public function __construct(private EmailVerificationService $verification)
    {
    }

    public function send(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 403);

        if ($user->hasVerifiedEmail()) {
            return back()->with('success', 'Your email address is already verified.');
        }

        $sent = $this->verification->sendVerificationEmail($user);

        if (!$sent) {
            return back()->with('error', 'Unable to send verification email. Please try again.');
        }

        return back()->with('success', 'Verification email sent.');
    }

    public function verify(Request $request, int $id, string $hash)
    {
        abort_unless($request->hasValidSignature(), 403);

        $user = User::query()->findOrFail($id);
        abort_unless(hash_equals(sha1((string) $user->email), $hash), 403);

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return Inertia::render('Auth/VerifyEmailSuccess', [
            'email' => $user->email,
            'role' => $user->role,
            'settingsHref' => '/account/account-settings',
            'dashboardHref' => match ($user->role) {
                'admin' => '/AdminDashboard',
                'coach' => '/coach/dashboard',
                'student-athlete', 'student' => '/StudentAthleteDashboard',
                default => '/Login',
            },
        ]);
    }
}
