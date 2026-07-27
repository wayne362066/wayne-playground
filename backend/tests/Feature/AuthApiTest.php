<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_schema_keeps_only_the_minimum_login_fields(): void
    {
        $this->assertTrue(Schema::hasColumns('users', [
            'id',
            'username',
            'password',
            'created_at',
            'updated_at',
        ]));
        $this->assertFalse(Schema::hasColumn('users', 'name'));
        $this->assertFalse(Schema::hasColumn('users', 'email'));
        $this->assertFalse(Schema::hasColumn('users', 'email_verified_at'));
        $this->assertFalse(Schema::hasColumn('users', 'remember_token'));
        $this->assertFalse(Schema::hasTable('password_reset_tokens'));
        $this->assertContains(
            Schema::getColumnType('users', 'id'),
            ['bpchar', 'string', 'varchar'],
        );
    }

    public function test_a_visitor_can_register_without_storing_a_plaintext_password(): void
    {
        $response = $this->postJsonWithCsrf('/api/auth/register', [
            'username' => '  Wayne_01 ',
            'password' => 'safe-password',
            'password_confirmation' => 'safe-password',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.username', 'wayne_01')
            ->assertJsonPath('data.roles.0', 'member')
            ->assertJsonFragment(['wishes.create'])
            ->assertJsonMissingPath('data.password');

        $user = User::query()->sole();

        $this->assertSame('wayne_01', $user->username);
        $this->assertTrue(Str::isUlid($user->id));
        $this->assertNotSame('safe-password', $user->getRawOriginal('password'));
        $this->assertTrue(Hash::check('safe-password', $user->password));
        $this->assertSame(['member'], $user->roles()->pluck('key')->all());
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_requires_a_unique_valid_username_and_confirmed_password(): void
    {
        User::factory()->create(['username' => 'taken_name']);

        $this->postJsonWithCsrf('/api/auth/register', [
            'username' => 'taken_name',
            'password' => 'safe-password',
            'password_confirmation' => 'different-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['username', 'password']);

        $this->postJsonWithCsrf('/api/auth/register', [
            'username' => 'not allowed',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['username', 'password']);
    }

    public function test_an_account_can_login_restore_its_session_and_logout(): void
    {
        $user = User::factory()->create([
            'username' => 'wayne',
            'password' => 'safe-password',
        ]);

        $this->postJsonWithCsrf('/api/auth/login', [
            'username' => 'WAYNE',
            'password' => 'safe-password',
        ])
            ->assertOk()
            ->assertJsonPath('data.username', 'wayne');

        $this->assertAuthenticatedAs($user);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.username', 'wayne')
            ->assertJsonPath('data.roles', []);

        $this->postJsonWithCsrf('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->assertGuest();

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->getJson('/api/auth/permissions')
            ->assertOk()
            ->assertJsonFragment(['wishes.view']);
    }

    public function test_login_does_not_reveal_which_credential_was_wrong(): void
    {
        User::factory()->create([
            'username' => 'wayne',
            'password' => 'safe-password',
        ]);

        foreach ([
            ['username' => 'unknown', 'password' => 'safe-password'],
            ['username' => 'wayne', 'password' => 'wrong-password'],
        ] as $credentials) {
            $this->postJsonWithCsrf('/api/auth/login', $credentials)
                ->assertUnauthorized()
                ->assertExactJson([
                    'success' => false,
                    'message' => '帳號或密碼錯誤',
                ]);
        }
    }

    public function test_csrf_endpoint_issues_the_frontend_token_cookie(): void
    {
        $this->get('/api/auth/csrf-cookie')
            ->assertOk()
            ->assertJsonStructure(['data' => ['token']])
            ->assertCookie('XSRF-TOKEN');
    }

    private function postJsonWithCsrf(string $uri, array $data = []): TestResponse
    {
        return $this
            ->withSession(['_token' => 'test-csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-csrf-token')
            ->postJson($uri, $data);
    }
}
