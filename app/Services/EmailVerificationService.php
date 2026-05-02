<?php

namespace App\Services;

use App\Mail\VerifyEmailAddressMail;
use App\Models\User;
use Illuminate\Support\Facades\URL;

class EmailVerificationService
{
    public function sendVerificationEmail(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return true;
        }

        $verificationUrl = $this->verificationUrl($user);

        return app(SystemNotificationService::class)->sendUserEmail(
            $user,
            new VerifyEmailAddressMail($user, $verificationUrl),
            [
                'defer' => false,
                'respect_preferences' => false,
                'context' => [
                    'communication' => 'email_verification',
                    'user_id' => $user->id,
                ],
            ],
        );
    }

    public function verificationUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes((int) config('auth.verification.expire', 60)),
            [
                'id' => $user->id,
                'hash' => sha1((string) $user->email),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function statusPayload(User $user): array
    {
        return [
            'required' => !$user->hasVerifiedEmail(),
            'email' => (string) $user->email,
            'status' => $user->hasVerifiedEmail() ? 'verified' : 'not_verified',
            'verified_at' => $user->email_verified_at?->toIso8601String(),
            'settings_href' => '/account/account-settings',
            'send_verification_route' => '/email/verification-notification',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function reminderPayload(User $user): ?array
    {
        if ($user->hasVerifiedEmail()) {
            return null;
        }

        return [
            'id' => 'verification-reminder',
            'kind' => 'verification',
            'title' => 'Account verification required',
            'message' => 'Please verify your email address to secure your account and complete your profile setup.',
            'type' => 'verification',
            'type_label' => 'Action Required',
            'is_read' => false,
            'published_at' => null,
            'read_at' => null,
            'created_by' => null,
            'created_by_name' => null,
            'created_by_role' => null,
            'source_label' => 'System',
            'settings_href' => '/account/account-settings',
            'send_verification_route' => '/email/verification-notification',
            'send_verification_label' => 'Send Verification Email',
            'secondary_action_label' => 'Go to Account Settings',
        ];
    }
}
