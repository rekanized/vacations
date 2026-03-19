<?php

namespace App\Support;

use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class AzureAuthenticationService
{
    public const SETTING_TENANT_ID = 'azure_auth_tenant_id';
    public const SETTING_CLIENT_ID = 'azure_auth_client_id';
    public const SETTING_CLIENT_SECRET = 'azure_auth_client_secret';
    public const SETTING_DEPARTMENT_FIELD = 'azure_auth_department_field';
    public const SETTING_SITE_FIELD = 'azure_auth_site_field';

    private const DEFAULT_DEPARTMENT_FIELD = 'department';
    private const DEFAULT_SITE_FIELD = 'officeLocation';

    private const PROFILE_FIELD_OPTIONS = [
        'id' => 'Object ID',
        'displayName' => 'Display name',
        'givenName' => 'Given name',
        'surname' => 'Surname',
        'mail' => 'Mail',
        'userPrincipalName' => 'User principal name',
        'mailNickname' => 'Mail nickname',
        'jobTitle' => 'Job title',
        'department' => 'Department',
        'officeLocation' => 'Office location',
        'city' => 'City',
        'state' => 'State',
        'country' => 'Country',
        'companyName' => 'Company name',
        'employeeId' => 'Employee ID',
        'employeeType' => 'Employee type',
        'preferredLanguage' => 'Preferred language',
        'streetAddress' => 'Street address',
        'postalCode' => 'Postal code',
        'mobilePhone' => 'Mobile phone',
    ];

    /**
     * @return array<string, string>
     */
    public static function profileFieldOptions(): array
    {
        return self::PROFILE_FIELD_OPTIONS;
    }

    public function hasConfiguration(): bool
    {
        return filled($this->tenantId())
            && filled($this->clientId())
            && filled($this->clientSecret());
    }

    public function maskedConfiguration(): array
    {
        $tenantId = $this->tenantId();
        $clientId = $this->clientId();

        return [
            'tenant_id' => $tenantId,
            'client_id' => $clientId,
            'client_secret_mask' => $this->maskValue($this->clientSecret()),
            'configured' => $this->hasConfiguration(),
            'redirect_uri' => route('auth.azure.callback'),
            'tenant_id_mask' => $this->maskValue($tenantId),
            'client_id_mask' => $this->maskValue($clientId),
            'department_field' => $this->departmentField(),
            'site_field' => $this->siteField(),
            'field_options' => self::profileFieldOptions(),
        ];
    }

    public function storeConfiguration(string $tenantId, string $clientId, string $clientSecret, ?string $departmentField = null, ?string $siteField = null): array
    {
        $openIdConfiguration = $this->fetchOpenIdConfiguration($tenantId);
        $resolvedDepartmentField = $this->normalizeProfileField($departmentField, self::DEFAULT_DEPARTMENT_FIELD);
        $resolvedSiteField = $this->normalizeProfileField($siteField, self::DEFAULT_SITE_FIELD);

        $this->verifySignInConfiguration(
            tokenEndpoint: (string) Arr::get($openIdConfiguration, 'token_endpoint', ''),
            clientId: $clientId,
            clientSecret: $clientSecret,
        );

        Setting::writeValue(self::SETTING_TENANT_ID, $tenantId);
        Setting::writeValue(self::SETTING_CLIENT_ID, $clientId);
        Setting::writeEncryptedValue(self::SETTING_CLIENT_SECRET, $clientSecret);
        Setting::writeValue(self::SETTING_DEPARTMENT_FIELD, $resolvedDepartmentField);
        Setting::writeValue(self::SETTING_SITE_FIELD, $resolvedSiteField);

        return [
            'issuer' => (string) Arr::get($openIdConfiguration, 'issuer'),
            'authorization_endpoint' => (string) Arr::get($openIdConfiguration, 'authorization_endpoint'),
            'token_endpoint' => (string) Arr::get($openIdConfiguration, 'token_endpoint'),
        ];
    }

    public function authorizationUrl(string $state, ?string $nonce = null): string
    {
        $query = http_build_query([
            'client_id' => $this->clientId(),
            'response_type' => 'code',
            'redirect_uri' => route('auth.azure.callback'),
            'response_mode' => 'query',
            'scope' => 'openid profile email offline_access User.Read User.Read.All',
            'state' => $state,
            'nonce' => $nonce,
            'prompt' => 'select_account',
        ], '', '&', PHP_QUERY_RFC3986);

        return sprintf('%s/oauth2/v2.0/authorize?%s', $this->authorityBaseUrl(), $query);
    }

    public function resolveIdentityFromAuthorizationCode(string $code, ?string $expectedNonce = null): array
    {
        $response = Http::asForm()
            ->acceptJson()
            ->timeout(15)
            ->post(sprintf('%s/oauth2/v2.0/token', $this->authorityBaseUrl()), [
                'client_id' => $this->clientId(),
                'client_secret' => $this->clientSecret(),
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => route('auth.azure.callback'),
            ]);

        if ($response->failed()) {
            $message = trim((string) Arr::get($response->json(), 'error_description', 'Azure sign-in could not complete.'));

            throw ValidationException::withMessages([
                'azure_auth' => $message,
            ]);
        }

        $idToken = (string) Arr::get($response->json(), 'id_token', '');
        $accessToken = (string) Arr::get($response->json(), 'access_token', '');

        if ($idToken === '') {
            throw ValidationException::withMessages([
                'azure_auth' => 'Azure sign-in did not return an ID token.',
            ]);
        }

        if ($accessToken === '') {
            throw ValidationException::withMessages([
                'azure_auth' => 'Azure sign-in did not return an access token for Microsoft Graph.',
            ]);
        }

        return $this->resolveIdentityFromTokens($idToken, $accessToken, $expectedNonce);
    }

    public function tenantId(): ?string
    {
        return Setting::valueFor(self::SETTING_TENANT_ID);
    }

    public function clientId(): ?string
    {
        return Setting::valueFor(self::SETTING_CLIENT_ID);
    }

    public function clientSecret(): ?string
    {
        return Setting::encryptedValueFor(self::SETTING_CLIENT_SECRET);
    }

    public function departmentField(): string
    {
        return $this->normalizeProfileField(Setting::valueFor(self::SETTING_DEPARTMENT_FIELD), self::DEFAULT_DEPARTMENT_FIELD);
    }

    public function siteField(): string
    {
        return $this->normalizeProfileField(Setting::valueFor(self::SETTING_SITE_FIELD), self::DEFAULT_SITE_FIELD);
    }

    private function authorityBaseUrl(?string $tenantId = null): string
    {
        return sprintf('https://login.microsoftonline.com/%s', rawurlencode($tenantId ?? (string) $this->tenantId()));
    }

    private function fetchOpenIdConfiguration(string $tenantId): array
    {
        $response = Http::acceptJson()
            ->timeout(10)
            ->get(sprintf('%s/v2.0/.well-known/openid-configuration', $this->authorityBaseUrl($tenantId)));

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'tenant_id' => 'Azure tenant discovery failed. Confirm the tenant ID and try again.',
            ]);
        }

        $payload = $response->json();

        if (! is_array($payload) || ! isset($payload['authorization_endpoint'], $payload['token_endpoint'])) {
            throw ValidationException::withMessages([
                'tenant_id' => 'Azure tenant discovery returned an incomplete OpenID configuration.',
            ]);
        }

        return $payload;
    }

    private function verifySignInConfiguration(string $tokenEndpoint, string $clientId, string $clientSecret): void
    {
        if ($tokenEndpoint === '') {
            throw ValidationException::withMessages([
                'azure_auth' => 'Azure tenant discovery did not return a token endpoint.',
            ]);
        }

        $response = Http::asForm()
            ->acceptJson()
            ->timeout(15)
            ->post($tokenEndpoint, [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'authorization_code',
                'code' => 'leaveboard-verification-probe',
                'redirect_uri' => route('auth.azure.callback'),
            ]);

        $payload = $response->json();
        $error = trim((string) Arr::get($payload, 'error', ''));
        $errorDescription = trim((string) Arr::get($payload, 'error_description', ''));

        if ($error === 'invalid_grant') {
            return;
        }

        throw ValidationException::withMessages([
            'azure_auth' => $errorDescription !== ''
                ? $errorDescription
                : 'Azure sign-in verification failed. Confirm the tenant ID, client ID, client secret, redirect URI, and app registration settings.',
        ]);
    }

    private function resolveIdentityFromTokens(string $idToken, string $accessToken, ?string $expectedNonce = null): array
    {
        $tokenIdentity = $this->parseIdentityFromIdToken($idToken, $expectedNonce);
        $profile = $this->fetchGraphProfile($accessToken);
        $manager = $this->fetchGraphManager($accessToken);

        $firstName = trim((string) ($profile['givenName'] ?? ''));
        $lastName = trim((string) ($profile['surname'] ?? ''));
        $displayName = trim((string) ($profile['displayName'] ?? $tokenIdentity['name'] ?? 'Azure user'));
        $email = trim((string) ($profile['mail'] ?? $profile['userPrincipalName'] ?? $tokenIdentity['email'] ?? ''));
        $departmentName = $this->mappedProfileValue($profile, $this->departmentField(), 'Unassigned');
        $siteName = $this->mappedProfileValue($profile, $this->siteField(), 'Unassigned');

        return [
            'azure_oid' => $tokenIdentity['azure_oid'],
            'name' => $displayName !== '' ? $displayName : trim($firstName.' '.$lastName),
            'first_name' => $firstName !== '' ? $firstName : null,
            'last_name' => $lastName !== '' ? $lastName : null,
            'email' => $email !== '' ? mb_strtolower($email) : $tokenIdentity['email'],
            'department_name' => $departmentName,
            'site_name' => $siteName,
            'manager' => $manager,
        ];
    }

    private function parseIdentityFromIdToken(string $idToken, ?string $expectedNonce = null): array
    {
        $segments = explode('.', $idToken);

        if (count($segments) < 2) {
            throw ValidationException::withMessages([
                'azure_auth' => 'Azure sign-in returned an invalid ID token.',
            ]);
        }

        $payload = json_decode($this->decodeJwtSegment($segments[1]), true);

        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'azure_auth' => 'Azure sign-in returned unreadable account details.',
            ]);
        }

        $this->assertTokenClaimsAreValid($payload, $expectedNonce);

        $azureOid = trim((string) ($payload['oid'] ?? $payload['sub'] ?? ''));
        $name = trim((string) ($payload['name'] ?? 'Azure user'));
        $email = trim((string) ($payload['preferred_username'] ?? $payload['email'] ?? $payload['upn'] ?? ''));

        if ($azureOid === '') {
            throw ValidationException::withMessages([
                'azure_auth' => 'Azure sign-in did not include a stable account identifier.',
            ]);
        }

        return [
            'azure_oid' => $azureOid,
            'name' => $name !== '' ? $name : 'Azure user',
            'email' => $email !== '' ? mb_strtolower($email) : null,
        ];
    }

    private function assertTokenClaimsAreValid(array $payload, ?string $expectedNonce): void
    {
        $now = CarbonImmutable::now()->timestamp;
        $expiresAt = (int) ($payload['exp'] ?? 0);
        $notBefore = (int) ($payload['nbf'] ?? 0);
        $audience = $payload['aud'] ?? null;
        $issuer = trim((string) ($payload['iss'] ?? ''));
        $tenantId = trim((string) ($payload['tid'] ?? ''));
        $nonce = trim((string) ($payload['nonce'] ?? ''));
        $expectedAudience = (string) $this->clientId();
        $expectedIssuer = sprintf('https://login.microsoftonline.com/%s/v2.0', (string) $this->tenantId());

        if ($expiresAt <= $now) {
            throw ValidationException::withMessages([
                'azure_auth' => 'Azure sign-in returned an expired ID token.',
            ]);
        }

        if ($notBefore !== 0 && $notBefore > $now + 60) {
            throw ValidationException::withMessages([
                'azure_auth' => 'Azure sign-in returned an ID token that is not yet valid.',
            ]);
        }

        if (! $this->tokenAudienceMatches($audience, $expectedAudience)) {
            throw ValidationException::withMessages([
                'azure_auth' => 'Azure sign-in returned an ID token for a different application.',
            ]);
        }

        if ($tenantId !== '' && ! hash_equals((string) $this->tenantId(), $tenantId)) {
            throw ValidationException::withMessages([
                'azure_auth' => 'Azure sign-in returned an ID token from an unexpected tenant.',
            ]);
        }

        if ($issuer !== '' && ! hash_equals($expectedIssuer, $issuer)) {
            throw ValidationException::withMessages([
                'azure_auth' => 'Azure sign-in returned an ID token with an unexpected issuer.',
            ]);
        }

        if (filled($expectedNonce) && ($nonce === '' || ! hash_equals((string) $expectedNonce, $nonce))) {
            throw ValidationException::withMessages([
                'azure_auth' => 'The Microsoft sign-in response could not be verified. Please try again.',
            ]);
        }
    }

    private function tokenAudienceMatches(mixed $audience, string $expectedAudience): bool
    {
        if (is_string($audience)) {
            return hash_equals($expectedAudience, $audience);
        }

        if (! is_array($audience)) {
            return false;
        }

        return collect($audience)
            ->filter(fn (mixed $value) => is_string($value) && $value !== '')
            ->contains(fn (string $value) => hash_equals($expectedAudience, $value));
    }

    private function fetchGraphProfile(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(15)
            ->get('https://graph.microsoft.com/v1.0/me', [
                '$select' => implode(',', array_keys(self::profileFieldOptions())),
            ]);

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'azure_auth' => 'Microsoft Graph profile lookup failed. Ensure the app registration has delegated access to User.Read and User.Read.All.',
            ]);
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'azure_auth' => 'Microsoft Graph returned unreadable user profile details.',
            ]);
        }

        return $payload;
    }

    private function fetchGraphManager(string $accessToken): ?array
    {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(15)
            ->get('https://graph.microsoft.com/v1.0/me/manager', [
                '$select' => implode(',', array_keys(self::profileFieldOptions())),
            ]);

        if ($response->status() === 404) {
            return null;
        }

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'azure_auth' => 'Microsoft Graph manager lookup failed. Ensure the app registration has delegated access to User.Read.All.',
            ]);
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            return null;
        }

        $managerOid = trim((string) ($payload['id'] ?? ''));

        if ($managerOid === '') {
            return null;
        }

        $firstName = trim((string) ($payload['givenName'] ?? ''));
        $lastName = trim((string) ($payload['surname'] ?? ''));
        $email = trim((string) ($payload['mail'] ?? $payload['userPrincipalName'] ?? ''));

        return [
            'azure_oid' => $managerOid,
            'name' => trim((string) ($payload['displayName'] ?? trim($firstName.' '.$lastName))) ?: 'Azure manager',
            'first_name' => $firstName !== '' ? $firstName : null,
            'last_name' => $lastName !== '' ? $lastName : null,
            'email' => $email !== '' ? mb_strtolower($email) : null,
            'department_name' => $this->mappedProfileValue($payload, $this->departmentField(), 'Unassigned'),
            'site_name' => $this->mappedProfileValue($payload, $this->siteField(), 'Unassigned'),
        ];
    }

    private function mappedProfileValue(array $profile, string $field, string $default): string
    {
        $value = trim((string) Arr::get($profile, $field, ''));

        return $value !== '' ? $value : $default;
    }

    private function normalizeProfileField(?string $field, string $default): string
    {
        $field = trim((string) $field);

        return array_key_exists($field, self::profileFieldOptions()) ? $field : $default;
    }

    private function decodeJwtSegment(string $value): string
    {
        $padding = strlen($value) % 4;

        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        if ($decoded === false) {
            throw ValidationException::withMessages([
                'azure_auth' => 'Azure sign-in returned unreadable token content.',
            ]);
        }

        return $decoded;
    }

    private function maskValue(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        if (strlen($value) <= 8) {
            return str_repeat('•', strlen($value));
        }

        return sprintf('%s%s', str_repeat('•', strlen($value) - 4), substr($value, -4));
    }
}