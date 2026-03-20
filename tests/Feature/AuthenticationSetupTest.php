<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_run_setup_can_create_a_manual_admin_without_azure(): void
    {
        $response = $this->post(route('setup.manual-admin.store'), [
            'first_name' => 'Asta',
            'last_name' => 'Admin',
            'email' => 'asta@example.test',
            'password' => 'very-secure-password',
            'password_confirmation' => 'very-secure-password',
        ]);

        $response
            ->assertRedirect(route('planner'))
            ->assertSessionHas('status', 'Manual admin account created. Azure can be configured later from the admin workspace.');

        $this->assertDatabaseHas('users', [
            'email' => 'asta@example.test',
            'name' => 'Asta Admin',
            'is_admin' => true,
            'is_active' => true,
        ]);
    }

    public function test_first_run_manual_admin_requires_a_stronger_password(): void
    {
        $response = $this->from(route('setup.show'))->post(route('setup.manual-admin.store'), [
            'first_name' => 'Asta',
            'last_name' => 'Admin',
            'email' => 'asta@example.test',
            'password' => '12345',
            'password_confirmation' => '12345',
        ]);

        $response
            ->assertRedirect(route('setup.show'))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', [
            'email' => 'asta@example.test',
        ]);
    }

    public function test_failed_azure_verification_returns_to_setup_with_error_and_keeps_input(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*/.well-known/openid-configuration' => Http::response([
                'issuer' => 'https://login.microsoftonline.com/example/v2.0',
                'authorization_endpoint' => 'https://login.microsoftonline.com/example/oauth2/v2.0/authorize',
                'token_endpoint' => 'https://login.microsoftonline.com/example/oauth2/v2.0/token',
            ]),
            'https://login.microsoftonline.com/*/oauth2/v2.0/token' => Http::response([
                'error' => 'invalid_client',
                'error_description' => 'AADSTS7000215: Invalid client secret is provided.',
            ], 401),
        ]);

        $response = $this
            ->from(route('setup.show'))
            ->post(route('setup.store'), [
                'tenant_id' => '11111111-1111-1111-1111-111111111111',
                'client_id' => '22222222-2222-2222-2222-222222222222',
                'client_secret' => 'super-secret-value',
            ]);

        $response
            ->assertRedirect(route('setup.show'))
            ->assertSessionHasErrors('azure_auth')
            ->assertSessionHasInput('tenant_id', '11111111-1111-1111-1111-111111111111')
            ->assertSessionHasInput('client_id', '22222222-2222-2222-2222-222222222222')
            ->assertSessionMissing('_old_input.client_secret');
    }

    public function test_initialized_app_rejects_public_setup_configuration_updates(): void
    {
        Department::create(['name' => 'Operations'])
            ->users()
            ->create([
                'name' => 'Existing User',
                'email' => 'existing@example.test',
                'password' => 'very-secure-password',
                'location' => 'Stockholm',
            ]);

        $this->post(route('setup.store'), [
            'tenant_id' => '11111111-1111-1111-1111-111111111111',
            'client_id' => '22222222-2222-2222-2222-222222222222',
            'client_secret' => 'super-secret-value',
        ])->assertForbidden();
    }

    public function test_successful_azure_verification_returns_to_setup_with_status(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*/.well-known/openid-configuration' => Http::response([
                'issuer' => 'https://login.microsoftonline.com/example/v2.0',
                'authorization_endpoint' => 'https://login.microsoftonline.com/example/oauth2/v2.0/authorize',
                'token_endpoint' => 'https://login.microsoftonline.com/example/oauth2/v2.0/token',
            ]),
            'https://login.microsoftonline.com/*/oauth2/v2.0/token' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'AADSTS9002313: Invalid request. Verification probe used a placeholder authorization code.',
            ], 400),
        ]);

        $response = $this->post(route('setup.store'), [
            'tenant_id' => '11111111-1111-1111-1111-111111111111',
            'client_id' => '22222222-2222-2222-2222-222222222222',
            'client_secret' => 'super-secret-value',
        ]);

        $response
            ->assertRedirect(route('setup.show'))
            ->assertSessionHas('status', 'Azure authentication is configured. Tenant sign-in endpoints were verified. Continue with Microsoft sign-in to finish setup.');
    }

    public function test_manual_users_can_sign_in_from_the_landing_page(): void
    {
        $department = Department::create(['name' => 'Operations']);
        $user = $department->users()->create([
            'name' => 'Manual Person',
            'email' => 'manual@example.test',
            'password' => 'very-secure-password',
            'location' => 'Stockholm',
        ]);

        $response = $this->post(route('login.manual'), [
            'email' => 'manual@example.test',
            'password' => 'very-secure-password',
        ]);

        $response->assertRedirect(route('planner'));
        $response->assertSessionHas('current_user_id', $user->id);
    }

    public function test_manual_login_is_rate_limited_after_repeated_failures(): void
    {
        RateLimiter::clear('manual-login|manual@example.test|127.0.0.1');

        $department = Department::create(['name' => 'Operations']);
        $department->users()->create([
            'name' => 'Manual Person',
            'email' => 'manual@example.test',
            'password' => 'very-secure-password',
            'location' => 'Stockholm',
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from(route('login.manual.form'))
                ->post(route('login.manual'), [
                    'email' => 'manual@example.test',
                    'password' => 'wrong-password',
                ])
                ->assertSessionHasErrors('manual_auth');
        }

        $this->from(route('login.manual.form'))
            ->post(route('login.manual'), [
                'email' => 'manual@example.test',
                'password' => 'wrong-password',
            ])
            ->assertSessionHasErrors('manual_auth');
    }

    public function test_manual_login_page_is_accessible_even_when_no_manual_accounts_exist(): void
    {
        $this
            ->get(route('login.manual.form'))
            ->assertOk()
            ->assertSeeText('Manual sign-in')
            ->assertSeeText('No manual sign-in accounts are available yet.');
    }

    public function test_admin_can_add_a_manual_user_and_delegate_admin_access(): void
    {
        $department = Department::create(['name' => 'Operations']);
        $admin = $department->users()->create([
            'name' => 'Asta Admin',
            'email' => 'asta@example.test',
            'password' => 'very-secure-password',
            'location' => 'Stockholm',
            'is_admin' => true,
        ]);
        $manager = $department->users()->create([
            'name' => 'Maja Manager',
            'email' => 'maja@example.test',
            'password' => 'very-secure-password',
            'location' => 'Gothenburg',
        ]);

        $response = $this
            ->withSession(['current_user_id' => $admin->id])
            ->post(route('admin.manual-users.store'), [
                'first_name' => 'Nils',
                'last_name' => 'Newman',
                'email' => 'nils@example.test',
                'password' => 'another-secure-password',
                'password_confirmation' => 'another-secure-password',
                'department_name' => 'Finance',
                'location' => 'Malmo',
                'manager_id' => (string) $manager->id,
                'is_admin' => '1',
            ]);

        $response
            ->assertRedirect(route('admin.users'))
            ->assertSessionHas('status', 'Nils Newman can now sign in with email and password.');

        $this->assertDatabaseHas('users', [
            'email' => 'nils@example.test',
            'name' => 'Nils Newman',
            'is_admin' => true,
            'manager_id' => $manager->id,
            'location' => 'Malmo',
        ]);
    }

    public function test_admin_manual_user_creation_requires_a_stronger_password(): void
    {
        $department = Department::create(['name' => 'Operations']);
        $admin = $department->users()->create([
            'name' => 'Asta Admin',
            'email' => 'asta@example.test',
            'password' => 'very-secure-password',
            'location' => 'Stockholm',
            'is_admin' => true,
        ]);

        $response = $this
            ->from(route('admin.users'))
            ->withSession(['current_user_id' => $admin->id])
            ->post(route('admin.manual-users.store'), [
                'first_name' => 'Nils',
                'last_name' => 'Newman',
                'email' => 'nils@example.test',
                'password' => '12345',
                'password_confirmation' => '12345',
            ]);

        $response
            ->assertRedirect(route('admin.users'))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', [
            'email' => 'nils@example.test',
        ]);
    }

    public function test_manual_user_modal_validation_error_is_only_rendered_inside_the_modal(): void
    {
        $department = Department::create(['name' => 'Operations']);
        $admin = $department->users()->create([
            'name' => 'Asta Admin',
            'email' => 'asta@example.test',
            'password' => 'very-secure-password',
            'location' => 'Stockholm',
            'is_admin' => true,
        ]);

        $response = $this
            ->from(route('admin.users'))
            ->withSession(['current_user_id' => $admin->id])
            ->followingRedirects()
            ->post(route('admin.manual-users.store'), [
                '_manual_user_form' => '1',
                'first_name' => 'Nils',
                'last_name' => 'Newman',
                'email' => 'nils@example.test',
                'password' => '12345',
                'password_confirmation' => '12345',
            ]);

        $response
            ->assertOk()
            ->assertSee('isManualUserModalOpen: true', false)
            ->assertSeeText('The password field must be at least 6 characters.');

        $this->assertSame(
            1,
            substr_count($response->getContent(), 'The password field must be at least 6 characters.')
        );
    }

    public function test_admin_can_update_azure_profile_field_mapping(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*/.well-known/openid-configuration' => Http::response([
                'issuer' => 'https://login.microsoftonline.com/example/v2.0',
                'authorization_endpoint' => 'https://login.microsoftonline.com/example/oauth2/v2.0/authorize',
                'token_endpoint' => 'https://login.microsoftonline.com/example/oauth2/v2.0/token',
            ]),
            'https://login.microsoftonline.com/*/oauth2/v2.0/token' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'AADSTS9002313: Invalid request. Verification probe used a placeholder authorization code.',
            ], 400),
        ]);

        $department = Department::create(['name' => 'Operations']);
        $admin = $department->users()->create([
            'name' => 'Asta Admin',
            'email' => 'asta@example.test',
            'password' => 'very-secure-password',
            'location' => 'Stockholm',
            'is_admin' => true,
        ]);

        $response = $this
            ->withSession(['current_user_id' => $admin->id])
            ->post(route('admin.azure-auth.update'), [
                'tenant_id' => '11111111-1111-1111-1111-111111111111',
                'client_id' => '22222222-2222-2222-2222-222222222222',
                'client_secret' => 'super-secret-value',
                'department_field' => 'companyName',
                'site_field' => 'city',
            ]);

        $response
            ->assertRedirect(route('admin.authentication'))
            ->assertSessionHas('status', 'Azure authentication updated. Tenant sign-in endpoints were verified.');

        $this->assertSame('companyName', Setting::valueFor('azure_auth_department_field'));
        $this->assertSame('city', Setting::valueFor('azure_auth_site_field'));
    }

    public function test_azure_sign_in_uses_configured_profile_fields_for_department_and_location(): void
    {
        Setting::writeValue('azure_auth_tenant_id', '11111111-1111-1111-1111-111111111111');
        Setting::writeValue('azure_auth_client_id', '22222222-2222-2222-2222-222222222222');
        Setting::writeEncryptedValue('azure_auth_client_secret', 'super-secret-value');
        Setting::writeValue('azure_auth_department_field', 'companyName');
        Setting::writeValue('azure_auth_site_field', 'city');

        Http::fake([
            'https://login.microsoftonline.com/*/oauth2/v2.0/token' => Http::response([
                'id_token' => $this->fakeJwt([
                    'oid' => 'azure-user-oid',
                    'name' => 'Azure Person',
                    'preferred_username' => 'azure@example.test',
                    'aud' => '22222222-2222-2222-2222-222222222222',
                    'iss' => 'https://login.microsoftonline.com/11111111-1111-1111-1111-111111111111/v2.0',
                    'tid' => '11111111-1111-1111-1111-111111111111',
                    'nonce' => 'expected-nonce',
                    'exp' => now()->addMinutes(10)->timestamp,
                ]),
                'access_token' => 'graph-access-token',
            ]),
            'https://graph.microsoft.com/v1.0/me*' => Http::response([
                'id' => 'azure-user-oid',
                'displayName' => 'Azure Person',
                'givenName' => 'Azure',
                'surname' => 'Person',
                'mail' => 'azure@example.test',
                'userPrincipalName' => 'azure@example.test',
                'department' => 'Ignored Department',
                'officeLocation' => 'Ignored Site',
                'city' => 'Uppsala',
                'companyName' => 'Mapped Company',
            ]),
            'https://graph.microsoft.com/v1.0/me/manager*' => Http::response([], 404),
        ]);

        $response = $this
            ->withSession([
                'azure_auth_state' => 'expected-state',
                'azure_auth_nonce' => 'expected-nonce',
            ])
            ->get(route('auth.azure.callback', [
                'state' => 'expected-state',
                'code' => 'valid-auth-code',
            ]));

        $response
            ->assertRedirect(route('planner'))
            ->assertSessionHas('current_user_id');

        $department = Department::query()->where('name', 'Mapped Company')->first();

        $this->assertNotNull($department);

        $this->assertDatabaseHas('users', [
            'azure_oid' => 'azure-user-oid',
            'email' => 'azure@example.test',
            'department_id' => $department->id,
            'location' => 'Uppsala',
        ]);
    }

    public function test_azure_sign_in_rejects_a_callback_with_the_wrong_nonce(): void
    {
        Setting::writeValue('azure_auth_tenant_id', '11111111-1111-1111-1111-111111111111');
        Setting::writeValue('azure_auth_client_id', '22222222-2222-2222-2222-222222222222');
        Setting::writeEncryptedValue('azure_auth_client_secret', 'super-secret-value');

        Http::fake([
            'https://login.microsoftonline.com/*/oauth2/v2.0/token' => Http::response([
                'id_token' => $this->fakeJwt([
                    'oid' => 'azure-user-oid',
                    'name' => 'Azure Person',
                    'preferred_username' => 'azure@example.test',
                    'aud' => '22222222-2222-2222-2222-222222222222',
                    'iss' => 'https://login.microsoftonline.com/11111111-1111-1111-1111-111111111111/v2.0',
                    'tid' => '11111111-1111-1111-1111-111111111111',
                    'nonce' => 'unexpected-nonce',
                    'exp' => now()->addMinutes(10)->timestamp,
                ]),
                'access_token' => 'graph-access-token',
            ]),
        ]);

        $this->withSession([
            'azure_auth_state' => 'expected-state',
            'azure_auth_nonce' => 'expected-nonce',
        ])->get(route('auth.azure.callback', [
            'state' => 'expected-state',
            'code' => 'valid-auth-code',
        ]))->assertSessionHasErrors('azure_auth');
    }

    public function test_non_admin_user_cannot_open_the_admin_workspace(): void
    {
        $department = Department::create(['name' => 'Operations']);
        $user = $department->users()->create([
            'name' => 'Standard User',
            'email' => 'user@example.test',
            'password' => 'very-secure-password',
            'location' => 'Stockholm',
            'is_admin' => false,
        ]);

        $this
            ->withSession(['current_user_id' => $user->id])
            ->get(route('admin.index'))
            ->assertForbidden();
    }

    public function test_inactive_session_user_is_redirected_to_the_landing_page(): void
    {
        $department = Department::create(['name' => 'Finance']);
        $inactiveUser = $department->users()->create([
            'name' => 'Inactive Ingrid',
            'email' => 'inactive@example.test',
            'password' => 'very-secure-password',
            'location' => 'Stockholm',
            'is_active' => false,
        ]);

        $this
            ->withSession(['current_user_id' => $inactiveUser->id])
            ->get(route('planner'))
            ->assertRedirect(route('home'));
    }

    public function test_azure_sign_in_respects_department_and_location_overrides(): void
    {
        Setting::writeValue('azure_auth_tenant_id', '11111111-1111-1111-1111-111111111111');
        Setting::writeValue('azure_auth_client_id', '22222222-2222-2222-2222-222222222222');
        Setting::writeEncryptedValue('azure_auth_client_secret', 'super-secret-value');

        $originalDepartment = Department::create(['name' => 'Original Dept']);
        $overriddenDepartment = Department::create(['name' => 'Overridden Dept']);

        $user = $originalDepartment->users()->create([
            'name' => 'Azure Person',
            'email' => 'azure@example.test',
            'azure_oid' => 'azure-user-oid',
            'location' => 'Original Location',
            'is_department_overridden' => true,
            'is_location_overridden' => true,
        ]);

        $user->update(['department_id' => $overriddenDepartment->id, 'location' => 'Overridden Location']);

        Http::fake([
            'https://login.microsoftonline.com/*/oauth2/v2.0/token' => Http::response([
                'id_token' => $this->fakeJwt([
                    'oid' => 'azure-user-oid',
                    'name' => 'Azure Person',
                    'preferred_username' => 'azure@example.test',
                    'aud' => '22222222-2222-2222-2222-222222222222',
                    'iss' => 'https://login.microsoftonline.com/11111111-1111-1111-1111-111111111111/v2.0',
                    'tid' => '11111111-1111-1111-1111-111111111111',
                    'nonce' => 'expected-nonce',
                    'exp' => now()->addMinutes(10)->timestamp,
                ]),
                'access_token' => 'graph-access-token',
            ]),
            'https://graph.microsoft.com/v1.0/me*' => Http::response([
                'id' => 'azure-user-oid',
                'displayName' => 'Azure Person',
                'mail' => 'azure@example.test',
                'companyName' => 'New Sync Dept', // Different from DB
                'city' => 'New Sync Location',     // Different from DB
            ]),
        ]);

        $this->withSession([
            'azure_auth_state' => 'expected-state',
            'azure_auth_nonce' => 'expected-nonce',
        ])->get(route('auth.azure.callback', [
            'state' => 'expected-state',
            'code' => 'valid-auth-code',
        ]))->assertRedirect(route('planner'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'department_id' => $overriddenDepartment->id,
            'location' => 'Overridden Location',
        ]);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
            'location' => 'New Sync Location',
        ]);
    }

    private function fakeJwt(array $payload): string
    {
        $header = ['alg' => 'none', 'typ' => 'JWT'];

        return implode('.', [
            $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR)),
            'signature',
        ]);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}