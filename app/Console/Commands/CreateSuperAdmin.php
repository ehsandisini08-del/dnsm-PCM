<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateSuperAdmin extends Command
{
    protected $signature = 'app:create-super-admin
                            {--email= : Email address for the Super Admin}
                            {--password= : Password for the Super Admin}
                            {--name= : Name for the Super Admin}';

    protected $description = 'Create a new Super Admin user without requiring approval';

    public function handle(): int
    {
        $this->info('╔═══════════════════════════════════════════════════╗');
        $this->info('║   Create Super Admin - DNS Manager                ║');
        $this->info('╚═══════════════════════════════════════════════════╝');
        $this->newLine();

        if ($this->option('email') && $this->option('password')) {
            return $this->handleNonInteractive();
        }

        return $this->handleInteractive();
    }

    protected function handleInteractive(): int
    {
        $name = text(
            label: 'Enter full name',
            placeholder: 'Super Admin',
            default: 'Super Admin',
            required: true
        );

        $email = text(
            label: 'Enter email address',
            placeholder: 'admin@example.com',
            required: true,
            validate: fn (string $value) => $this->validateEmail($value)
        );

        $password = password(
            label: 'Enter password',
            placeholder: 'Minimum 8 characters',
            required: true,
            validate: fn (string $value) => $this->validatePassword($value)
        );

        $passwordConfirmation = password(
            label: 'Confirm password',
            required: true,
            validate: fn (string $value) => $value === $password
                ? null
                : 'Passwords do not match'
        );

        $this->newLine();
        $this->info('Review Super Admin Details:');
        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $name],
                ['Email', $email],
                ['Role', 'super_admin'],
                ['Status', 'approved'],
                ['Active', 'Yes'],
                ['Email Verified', 'Yes'],
            ]
        );

        $confirmed = confirm(
            label: 'Create this Super Admin user?',
            default: true
        );

        if (! $confirmed) {
            $this->warn('Operation cancelled.');

            return self::FAILURE;
        }

        return $this->createSuperAdmin($name, $email, $password);
    }

    protected function handleNonInteractive(): int
    {
        $name = $this->option('name') ?: 'Super Admin';
        $email = $this->option('email');
        $password = $this->option('password');

        if (! $email || ! $password) {
            $this->error('Email and password are required in non-interactive mode.');
            $this->info('Usage: php artisan app:create-super-admin --email=admin@example.com --password=secret --name="Admin Name"');

            return self::FAILURE;
        }

        $emailValidation = $this->validateEmail($email);
        if ($emailValidation !== null) {
            $this->error('Validation Error: '.$emailValidation);

            return self::FAILURE;
        }

        $passwordValidation = $this->validatePassword($password);
        if ($passwordValidation !== null) {
            $this->error('Validation Error: '.$passwordValidation);

            return self::FAILURE;
        }

        return $this->createSuperAdmin($name, $email, $password);
    }

    protected function createSuperAdmin(string $name, string $email, string $password): int
    {
        try {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'super_admin',
                'is_active' => true,
                'approval_status' => 'approved',
                'email_verified_at' => now(),
                'approved_at' => now(),
                'approved_by' => null,
            ]);

            $this->newLine();
            $this->info('✓ Super Admin created successfully!');
            $this->newLine();

            $this->table(
                ['ID', 'Name', 'Email', 'Role'],
                [
                    [$user->id, $user->name, $user->email, $user->role],
                ]
            );

            $this->newLine();
            $this->info('Login Credentials:');
            $this->line("  Email:    {$email}");
            $this->line("  Password: {$password}");
            $this->newLine();
            $this->warn('⚠ Please save these credentials securely and change the password after first login.');
            $this->newLine();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to create Super Admin: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    protected function validateEmail(string $email): ?string
    {
        $validator = Validator::make(
            ['email' => $email],
            [
                'email' => ['required', 'email', 'unique:users,email', 'max:255'],
            ],
            [
                'email.unique' => 'This email is already registered.',
                'email.email' => 'Please enter a valid email address.',
            ]
        );

        if ($validator->fails()) {
            return $validator->errors()->first('email');
        }

        return null;
    }

    protected function validatePassword(string $password): ?string
    {
        if (strlen($password) < 8) {
            return 'Password must be at least 8 characters long.';
        }

        if (strlen($password) < 12) {
            $this->warn('⚠ Warning: Password is less than 12 characters. Consider using a stronger password.');
        }

        return null;
    }
}
