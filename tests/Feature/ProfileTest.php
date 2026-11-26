<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\MemberRegister;
use App\Filament\Resources\UserResource\Pages\ManageUsers;
use App\Models\User;
use App\Notifications\WelcomeUserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_filament_breezy_my_profile_livewire_update_succeeds_without_419(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        $pageResponse = $this->actingAs($user)->get('/admin/my-profile');
        $pageResponse->assertOk();
        $pageResponse->assertDontSee('My Membership Plan');

        preg_match('/wire:snapshot="([^"]+)"/', $pageResponse->getContent(), $matches);
        $this->assertNotEmpty($matches);

        $snapshotJson = html_entity_decode($matches[1], ENT_QUOTES);

        $updateResponse = $this->actingAs($user)->postJson('/livewire/update', [
            '_token' => csrf_token(),
            'components' => [
                [
                    'snapshot' => $snapshotJson,
                    'updates' => [],
                    'calls' => [],
                ],
            ],
        ]);

        $updateResponse->assertOk();
    }

    public function test_my_subscription_plan_section_is_only_visible_to_subscribed_members(): void
    {
        $subscribedRole = Role::firstOrCreate(['name' => 'subscribed', 'guard_name' => 'web']);
        $member = User::factory()->create(['is_active' => true]);
        $member->assignRole($subscribedRole);

        $this->actingAs($member)
            ->get('/member/my-profile')
            ->assertOk()
            ->assertSee('My Membership Plan');

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole($adminRole);

        $this->actingAs($admin)
            ->get('/admin/my-profile')
            ->assertOk()
            ->assertDontSee('My Membership Plan');
    }

    public function test_system_admin_user_resource_does_not_have_membership_plan_field(): void
    {
        Role::firstOrCreate(['name' => 'subscribed', 'guard_name' => 'web']);
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole($superAdminRole);

        Livewire::actingAs($superAdmin)
            ->test(ManageUsers::class)
            ->assertTableColumnDoesNotExist('plan.name_en')
            ->mountAction('create')
            ->assertFormFieldExists('roles', 'mountedActionForm')
            ->assertFormFieldExists('preferred_language', 'mountedActionForm')
            ->assertFormFieldDoesNotExist('plan_id', 'mountedActionForm');
    }

    public function test_creating_system_admin_sends_welcome_email_in_selected_language(): void
    {
        Notification::fake();

        Role::firstOrCreate(['name' => 'subscribed', 'guard_name' => 'web']);
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole($superAdminRole);

        Livewire::actingAs($superAdmin)
            ->test(ManageUsers::class)
            ->callAction('create', data: [
                'name' => 'Carlos Admin',
                'email' => 'carlos@millyfit.test',
                'preferred_language' => 'es',
                'password' => 'SecretPass123!',
                'roles' => [$adminRole->id],
                'is_active' => true,
            ])
            ->assertHasNoActionErrors();

        $createdAdmin = User::where('email', 'carlos@millyfit.test')->firstOrFail();
        $this->assertSame('es', $createdAdmin->preferred_language);

        Notification::assertSentTo(
            $createdAdmin,
            WelcomeUserNotification::class,
            function (WelcomeUserNotification $notification, array $channels) use ($createdAdmin): bool {
                $mail = $notification->toMail($createdAdmin);

                return $notification->language === 'es'
                    && $notification->panel === 'admin'
                    && $notification->plainPassword === 'SecretPass123!'
                    && str_contains($mail->subject, '¡Bienvenido(a)')
                    && str_contains($mail->actionUrl, '/admin/login');
            }
        );
    }

    public function test_member_registration_sends_welcome_email_in_selected_language(): void
    {
        Notification::fake();

        Livewire::test(MemberRegister::class)
            ->fillForm([
                'name' => 'Jane Member',
                'email' => 'jane@millyfit.test',
                'preferred_language' => 'en',
                'password' => 'MemberPass123!',
                'passwordConfirmation' => 'MemberPass123!',
            ])
            ->call('register')
            ->assertHasNoFormErrors();

        $member = User::where('email', 'jane@millyfit.test')->firstOrFail();
        $this->assertSame('en', $member->preferred_language);
        $this->assertTrue($member->hasRole('subscribed'));

        Notification::assertSentTo(
            $member,
            WelcomeUserNotification::class,
            function (WelcomeUserNotification $notification) use ($member): bool {
                $mail = $notification->toMail($member);

                return $notification->language === 'en'
                    && $notification->panel === 'member'
                    && $notification->plainPassword === 'MemberPass123!'
                    && str_contains($mail->subject, 'Welcome to')
                    && str_contains($mail->actionUrl, '/member/login');
            }
        );
    }
}
