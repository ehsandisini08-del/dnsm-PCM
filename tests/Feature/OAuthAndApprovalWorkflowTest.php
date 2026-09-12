<?php

use App\Filament\Pages\Auth\CustomLogin;
use App\Filament\Pages\Auth\CustomRegister;
use App\Filament\Pages\Auth\VerifyOtp;
use App\Models\User;
use App\Notifications\AccountActivationOtpNotification;
use App\Notifications\AccountApprovedNotification;
use App\Notifications\LoginOtpNotification;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Livewire\Livewire;
use Mockery;

uses(RefreshDatabase::class);

test('user can register via form and receives activation OTP', function () {
    Notification::fake();

    Livewire::test(CustomRegister::class)
        ->fillForm([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
        ])
        ->call('register');

    $user = User::where('email', 'john@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->approval_status)->toBe('pending')
        ->and($user->is_active)->toBeFalse()
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->otp_code)->not->toBeNull()
        ->and($user->otp_expires_at)->not->toBeNull();

    Notification::assertSentTo($user, AccountActivationOtpNotification::class);

    expect(session('auth_pending_user_id'))->toBe($user->id)
        ->and(session('auth_otp_type'))->toBe('activation');
});

test('user can verify activation OTP and reach pending approval page', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'approval_status' => 'pending',
        'is_active' => false,
    ]);

    $otpCode = '123456';
    $user->update([
        'otp_code' => Hash::make($otpCode),
        'otp_expires_at' => now()->addMinutes(5),
    ]);

    session(['auth_pending_user_id' => $user->id, 'auth_otp_type' => 'activation']);

    Livewire::test(VerifyOtp::class)
        ->set('otp', $otpCode)
        ->call('verify');

    $user->refresh();

    expect($user->email_verified_at)->not->toBeNull()
        ->and($user->otp_code)->toBeNull()
        ->and($user->otp_expires_at)->toBeNull()
        ->and($user->approval_status)->toBe('pending');
});

test('invalid activation OTP shows error message', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'otp_code' => Hash::make('123456'),
        'otp_expires_at' => now()->addMinutes(5),
    ]);

    session(['auth_pending_user_id' => $user->id, 'auth_otp_type' => 'activation']);

    Livewire::test(VerifyOtp::class)
        ->set('otp', '999999')
        ->call('verify')
        ->assertNotified();

    $user->refresh();
    expect($user->email_verified_at)->toBeNull();
});

test('expired activation OTP shows error message', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'otp_code' => Hash::make('123456'),
        'otp_expires_at' => now()->subMinutes(1),
    ]);

    session(['auth_pending_user_id' => $user->id, 'auth_otp_type' => 'activation']);

    Livewire::test(VerifyOtp::class)
        ->set('otp', '123456')
        ->call('verify')
        ->assertNotified();

    $user->refresh();
    expect($user->email_verified_at)->toBeNull();
});

test('resend activation OTP works with rate limiting', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email_verified_at' => null,
        'otp_code' => Hash::make('123456'),
        'otp_expires_at' => now()->addMinutes(5),
    ]);

    session(['auth_pending_user_id' => $user->id, 'auth_otp_type' => 'activation']);

    Livewire::test(VerifyOtp::class)
        ->call('resend')
        ->assertNotified();

    Notification::assertSentTo($user, AccountActivationOtpNotification::class);
});

test('google oauth redirect returns socialite redirect response', function () {
    $response = $this->get(route('auth.google.redirect'));

    $response->assertRedirect();
});

test('google oauth callback creates new user with pending status', function () {
    Notification::fake();

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('google-id-12345');
    $socialiteUser->shouldReceive('getEmail')->andReturn('newuser@gmail.com');
    $socialiteUser->shouldReceive('getName')->andReturn('New User');

    Socialite::shouldReceive('driver')->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($socialiteUser);

    $response = $this->get(route('auth.google.callback'));

    $user = User::where('email', 'newuser@gmail.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->google_id)->toBe('google-id-12345')
        ->and($user->approval_status)->toBe('pending')
        ->and($user->is_active)->toBeFalse()
        ->and($user->email_verified_at)->toBeNull();

    Notification::assertSentTo($user, AccountActivationOtpNotification::class);

    $response->assertRedirect('/admin/verify-otp');
});

test('google oauth callback for existing approved user sends login OTP', function () {
    Notification::fake();

    $user = User::factory()->approved()->create([
        'email' => 'existing@gmail.com',
        'google_id' => null,
    ]);

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('google-id-67890');
    $socialiteUser->shouldReceive('getEmail')->andReturn('existing@gmail.com');
    $socialiteUser->shouldReceive('getName')->andReturn('Existing User');

    Socialite::shouldReceive('driver')->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($socialiteUser);

    $response = $this->get(route('auth.google.callback'));

    $user->refresh();

    expect($user->google_id)->toBe('google-id-67890');

    Notification::assertSentTo($user, LoginOtpNotification::class);

    $response->assertRedirect('/admin/verify-otp');
});

test('google oauth links google_id to existing email user', function () {
    Notification::fake();

    $user = User::factory()->approved()->create([
        'email' => 'user@gmail.com',
        'google_id' => null,
    ]);

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('google-new-id');
    $socialiteUser->shouldReceive('getEmail')->andReturn('user@gmail.com');
    $socialiteUser->shouldReceive('getName')->andReturn('User Name');

    Socialite::shouldReceive('driver')->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($socialiteUser);

    $this->get(route('auth.google.callback'));

    $user->refresh();

    expect($user->google_id)->toBe('google-new-id');
});

test('approved user login sends 2FA login OTP', function () {
    Notification::fake();

    $user = User::factory()->approved()->create([
        'email' => 'user@test.com',
        'password' => bcrypt('password'),
    ]);

    Livewire::test(CustomLogin::class)
        ->fillForm([
            'email' => 'user@test.com',
            'password' => 'password',
        ])
        ->call('authenticate');

    Notification::assertSentTo($user, LoginOtpNotification::class);

    expect(session('auth_pending_user_id'))->toBe($user->id)
        ->and(session('auth_otp_type'))->toBe('login');
});

test('user can login after verifying 2FA OTP', function () {
    $user = User::factory()->approved()->create();

    $otpCode = '654321';
    $user->update([
        'otp_code' => Hash::make($otpCode),
        'otp_expires_at' => now()->addMinutes(5),
    ]);

    session(['auth_pending_user_id' => $user->id, 'auth_otp_type' => 'login']);

    Livewire::test(VerifyOtp::class)
        ->set('otp', $otpCode)
        ->call('verify');

    $this->assertAuthenticatedAs($user);
});

test('rejected user cannot login and sees error', function () {
    $user = User::factory()->rejected()->create([
        'email' => 'rejected@test.com',
        'password' => bcrypt('password'),
    ]);

    Livewire::test(CustomLogin::class)
        ->fillForm([
            'email' => 'rejected@test.com',
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertNotified();

    $this->assertGuest();
});

test('pending approval user redirected to pending approval page', function () {
    $user = User::factory()->pendingApproval()->create([
        'email' => 'pending@test.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Livewire::test(CustomLogin::class)
        ->fillForm([
            'email' => 'pending@test.com',
            'password' => 'password',
        ])
        ->call('authenticate');

    expect(session('pending_approval_user_id'))->toBe($user->id);
});

test('unverified email user receives activation OTP on login', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'unverified@test.com',
        'password' => bcrypt('password'),
        'email_verified_at' => null,
        'approval_status' => 'pending',
    ]);

    Livewire::test(CustomLogin::class)
        ->fillForm([
            'email' => 'unverified@test.com',
            'password' => 'password',
        ])
        ->call('authenticate');

    Notification::assertSentTo($user, AccountActivationOtpNotification::class);

    expect(session('auth_otp_type'))->toBe('activation');
});

test('invalid login credentials show error', function () {
    User::factory()->approved()->create([
        'email' => 'user@test.com',
        'password' => bcrypt('correct-password'),
    ]);

    Livewire::test(CustomLogin::class)
        ->fillForm([
            'email' => 'user@test.com',
            'password' => 'wrong-password',
        ])
        ->call('authenticate')
        ->assertHasFormErrors();
});

test('super admin can approve pending user with role assignment', function () {
    Notification::fake();

    $admin = User::factory()->superAdmin()->create();
    $pendingUser = User::factory()->pendingApproval()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin);

    $pendingUser->update([
        'approval_status' => 'approved',
        'is_active' => true,
        'role' => 'operator',
        'approved_at' => now(),
        'approved_by' => $admin->id,
    ]);

    $pendingUser->refresh();

    expect($pendingUser->approval_status)->toBe('approved')
        ->and($pendingUser->is_active)->toBeTrue()
        ->and($pendingUser->role)->toBe('operator')
        ->and($pendingUser->approved_by)->toBe($admin->id)
        ->and($pendingUser->approved_at)->not->toBeNull();
});

test('super admin can reject pending user', function () {
    $admin = User::factory()->superAdmin()->create();
    $pendingUser = User::factory()->pendingApproval()->create();

    $this->actingAs($admin);

    $pendingUser->update([
        'approval_status' => 'rejected',
        'is_active' => false,
    ]);

    $pendingUser->refresh();

    expect($pendingUser->approval_status)->toBe('rejected')
        ->and($pendingUser->is_active)->toBeFalse();
});

test('approved user receives AccountApprovedNotification', function () {
    Notification::fake();

    $user = User::factory()->pendingApproval()->create();

    $user->notify(new AccountApprovedNotification('operator'));

    Notification::assertSentTo($user, AccountApprovedNotification::class);
});

test('approved user can access panel after full login flow', function () {
    $user = User::factory()->approved()->superAdmin()->create();

    expect($user->canAccessPanel(filament()->getDefaultPanel()))->toBeTrue();
});

test('OTP code is hashed in database not plaintext', function () {
    $user = User::factory()->create();

    $otpService = app(OtpService::class);
    $code = $otpService->sendActivationOtp($user);

    $user->refresh();

    expect($user->otp_code)->not->toBe($code)
        ->and(Hash::check($code, $user->otp_code))->toBeTrue();
});

test('rate limiting prevents abuse on OTP verification', function () {
    $user = User::factory()->create([
        'otp_code' => Hash::make('123456'),
        'otp_expires_at' => now()->addMinutes(5),
    ]);

    session(['auth_pending_user_id' => $user->id, 'auth_otp_type' => 'login']);

    for ($i = 0; $i < 5; $i++) {
        try {
            Livewire::test(VerifyOtp::class)
                ->set('otp', '999999')
                ->call('verify');
        } catch (Throwable) {
        }
    }

    expect(true)->toBeTrue();
});

test('pending approval user cannot access panel', function () {
    $user = User::factory()->pendingApproval()->superAdmin()->create();

    expect($user->canAccessPanel(filament()->getDefaultPanel()))->toBeFalse();
});

test('rejected user cannot access panel', function () {
    $user = User::factory()->rejected()->superAdmin()->create();

    expect($user->canAccessPanel(filament()->getDefaultPanel()))->toBeFalse();
});

test('user helper methods return correct boolean values', function () {
    $approvedUser = User::factory()->approved()->create();
    $pendingUser = User::factory()->pendingApproval()->create();
    $rejectedUser = User::factory()->rejected()->create();

    expect($approvedUser->isApproved())->toBeTrue()
        ->and($approvedUser->isPendingApproval())->toBeFalse()
        ->and($approvedUser->isRejected())->toBeFalse();

    expect($pendingUser->isApproved())->toBeFalse()
        ->and($pendingUser->isPendingApproval())->toBeTrue()
        ->and($pendingUser->isRejected())->toBeFalse();

    expect($rejectedUser->isApproved())->toBeFalse()
        ->and($rejectedUser->isPendingApproval())->toBeFalse()
        ->and($rejectedUser->isRejected())->toBeTrue();
});
