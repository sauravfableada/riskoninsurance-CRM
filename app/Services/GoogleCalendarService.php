<?php

namespace App\Services;

use App\Models\Setting;
use GuzzleHttp\Client;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GoogleCalendarService
{
    protected const TOKEN_FILE = 'google-calendar/token.json';
    protected Client $http;
    protected string $clientId;
    protected string $clientSecret;
    protected string $redirectUri;
    protected array $scopes;

    public function __construct()
    {
        $this->http = new Client([
            'timeout' => 20,
            'http_errors' => false,
        ]);

        $this->clientId = $this->settingValue('google_client_id', (string) config('services.google.client_id'));
        $this->clientSecret = $this->settingValue('google_client_secret', (string) config('services.google.client_secret'));
        $this->redirectUri = $this->resolveRedirectUri();
        $this->scopes = (array) config('services.google.scopes', [
            'https://www.googleapis.com/auth/calendar.events',
        ]);
    }

    /**
     * Retry token/API requests without SSL verification only in local/dev
     * environments where the machine CA bundle is commonly misconfigured.
     */
    protected function sendRequest(string $method, string $url, array $options = [])
    {
        try {
            return $this->http->request($method, $url, $options);
        } catch (\Throwable $e) {
            $isLocalSslIssue = app()->environment('local')
                && str_contains($e->getMessage(), 'cURL error 60');

            if (!$isLocalSslIssue) {
                throw $e;
            }

            Log::warning('Google request retried without SSL verification in local environment.', [
                'url' => $url,
                'method' => $method,
            ]);

            $options['verify'] = false;

            return $this->http->request($method, $url, $options);
        }
    }

    /**
     * Prefer explicit env config, but fall back to the named callback route
     * so local/web setups still produce a valid OAuth request.
     */
    protected function resolveRedirectUri(): string
    {
        $configured = $this->normalizeUrl($this->settingValue('google_redirect_uri', (string) config('services.google.redirect_uri')));

        if ($configured !== '') {
            return $configured;
        }

        try {
            return $this->normalizeUrl(route('google.callback'));
        } catch (\Throwable $e) {
            Log::warning('Google redirect URI could not be resolved from route.', [
                'message' => $e->getMessage(),
            ]);

            return '';
        }
    }

    protected function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);

        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return rtrim($url, '/');
        }

        $normalized = $parts['scheme'] . '://' . $parts['host'];

        if (!empty($parts['port'])) {
            $normalized .= ':' . $parts['port'];
        }

        $path = '/' . ltrim((string) ($parts['path'] ?? ''), '/');
        $normalized .= rtrim(preg_replace('#/+#', '/', $path) ?: '/', '/');

        if (!empty($parts['query'])) {
            $normalized .= '?' . $parts['query'];
        }

        if (!empty($parts['fragment'])) {
            $normalized .= '#' . $parts['fragment'];
        }

        return $normalized;
    }

    protected function settingValue(string $key, string $fallback = ''): string
    {
        try {
            $value = Setting::query()->where('key', $key)->value('value');

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        } catch (\Throwable $e) {
            Log::warning('Google setting lookup failed.', [
                'key' => $key,
                'message' => $e->getMessage(),
            ]);
        }

        return trim($fallback);
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== ''
            && $this->clientSecret !== ''
            && $this->redirectUri !== '';
    }

    public function getAuthUrl(): string
    {
        if ($this->clientId === '' || $this->redirectUri === '') {
            Log::error('Google OAuth configuration is incomplete.', [
                'has_client_id' => $this->clientId !== '',
                'has_redirect_uri' => $this->redirectUri !== '',
            ]);

            return '';
        }

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $this->scopes),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
        ]);
    }

    public function handleCallback(string $authCode): bool
    {
        if ($this->clientId === '' || $this->clientSecret === '' || $this->redirectUri === '') {
            Log::error('Google OAuth callback failed due to incomplete configuration.', [
                'has_client_id' => $this->clientId !== '',
                'has_client_secret' => $this->clientSecret !== '',
                'has_redirect_uri' => $this->redirectUri !== '',
            ]);

            return false;
        }

        try {
            $response = $this->sendRequest('POST', 'https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'code' => $authCode,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'redirect_uri' => $this->redirectUri,
                    'grant_type' => 'authorization_code',
                ],
            ]);

            $payload = json_decode((string) $response->getBody(), true);

            if ($response->getStatusCode() >= 400 || !is_array($payload) || isset($payload['error'])) {
                Log::error('Google Calendar OAuth Error', [
                    'status' => $response->getStatusCode(),
                    'payload' => $payload,
                ]);
                return false;
            }

            $payload['created_at'] = time();
            $this->storeToken($payload);

            return true;
        } catch (\Throwable $e) {
            Log::error('Google Calendar OAuth Error: ' . $e->getMessage());
            return false;
        }
    }

    protected function storeToken(array $token): void
    {
        $directory = dirname(self::TOKEN_FILE);
        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }

        // Encrypt token payload before persisting to disk.
        Storage::put(self::TOKEN_FILE, Crypt::encryptString(json_encode($token)));
    }

    protected function getToken(): ?array
    {
        if (!Storage::exists(self::TOKEN_FILE)) {
            return null;
        }

        try {
            $raw = Storage::get(self::TOKEN_FILE);
            $decoded = Crypt::decryptString($raw);
        } catch (DecryptException $e) {
            // Backward compatibility for any legacy plaintext token file.
            $decoded = Storage::get(self::TOKEN_FILE);
        } catch (\Throwable $e) {
            Log::error('Google Calendar Token Read Error', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        $token = json_decode($decoded, true);
        return is_array($token) ? $token : null;
    }

    protected function tokenExpired(array $token): bool
    {
        $createdAt = (int) ($token['created_at'] ?? 0);
        $expiresIn = (int) ($token['expires_in'] ?? 0);

        if ($createdAt === 0 || $expiresIn === 0) {
            return false;
        }

        return ($createdAt + $expiresIn - 60) <= time();
    }

    protected function getValidToken(): ?array
    {
        $token = $this->getToken();
        if (!$token) {
            return null;
        }

        if (!$this->tokenExpired($token)) {
            return $token;
        }

        if (empty($token['refresh_token'])) {
            return null;
        }

        try {
            $response = $this->sendRequest('POST', 'https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $token['refresh_token'],
                    'grant_type' => 'refresh_token',
                ],
            ]);

            $payload = json_decode((string) $response->getBody(), true);

            if ($response->getStatusCode() >= 400 || !is_array($payload) || isset($payload['error'])) {
                Log::error('Google Calendar Refresh Error', [
                    'status' => $response->getStatusCode(),
                    'payload' => $payload,
                ]);

                if (($payload['error'] ?? null) === 'invalid_grant') {
                    // Token revoked/expired, clear stored token to force re-auth.
                    $this->disconnect();
                }

                return null;
            }

            $token = array_merge($token, $payload, [
                'created_at' => time(),
                'refresh_token' => $token['refresh_token'],
            ]);

            $this->storeToken($token);

            return $token;
        } catch (\Throwable $e) {
            Log::error('Google Calendar Refresh Error: ' . $e->getMessage());
            return null;
        }
    }

    protected function authorizedRequest(string $method, string $url, array $options = []): ?array
    {
        $token = $this->getValidToken();
        if (!$token || empty($token['access_token'])) {
            return null;
        }

        $options['headers'] = array_merge($options['headers'] ?? [], [
            'Authorization' => 'Bearer ' . $token['access_token'],
            'Accept' => 'application/json',
        ]);

        if (isset($options['json'])) {
            $options['headers']['Content-Type'] = 'application/json';
        }

        try {
            $response = $this->sendRequest($method, $url, $options);
            $payload = json_decode((string) $response->getBody(), true);

            if ($response->getStatusCode() >= 400) {
                Log::error('Google Calendar API Error', [
                    'method' => $method,
                    'url' => $url,
                    'status' => $response->getStatusCode(),
                    'payload' => $payload,
                ]);
                return null;
            }

            return is_array($payload) ? $payload : [];
        } catch (\Throwable $e) {
            Log::error('Google Calendar API Error: ' . $e->getMessage());
            return null;
        }
    }

    public function isAuthenticated(): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        return $this->getValidToken() !== null;
    }

    public function isAutoSyncEnabled(): bool
    {
        return (bool) config('services.google.auto_sync', true);
    }

    public function getCalendarService(): ?array
    {
        return $this->isAuthenticated() ? ['connected' => true] : null;
    }

    public function listCalendars(): array
    {
        $payload = $this->authorizedRequest('GET', 'https://www.googleapis.com/calendar/v3/users/me/calendarList');
        if (!$payload || empty($payload['items']) || !is_array($payload['items'])) {
            return [];
        }

        return array_map(static function (array $calendar): array {
            return [
                'id' => $calendar['id'] ?? null,
                'summary' => $calendar['summary'] ?? '',
                'primary' => (bool) ($calendar['primary'] ?? false),
            ];
        }, $payload['items']);
    }

    public function getPrimaryCalendarId(): ?string
    {
        // Calendar event operations support the special primary identifier.
        // This avoids requesting broad access merely to discover the same ID.
        return 'primary';
    }

    protected function buildEventPayload($meeting): array
    {
        $payload = [
            'summary' => $meeting->title,
            'location' => $meeting->address ?? '',
            'description' => $meeting->agenda ?? $meeting->notes ?? '',
            'start' => [
                'dateTime' => $meeting->scheduled_at->toRfc3339String(),
                'timeZone' => config('app.timezone', 'UTC'),
            ],
            'end' => [
                'dateTime' => $meeting->scheduled_at->copy()->addHour()->toRfc3339String(),
                'timeZone' => config('app.timezone', 'UTC'),
            ],
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'email', 'minutes' => 24 * 60],
                    ['method' => 'popup', 'minutes' => 30],
                ],
            ],
        ];

        // Add attendees: assigned staff as organizer, customer as guest
        $attendees = [];

        // Add assigned staff as organizer
        if ($meeting->assignedUser && !empty($meeting->assignedUser->email)) {
            $attendees[] = [
                'email' => $meeting->assignedUser->email,
                'displayName' => $meeting->assignedUser->name,
                'organizer' => true,
                'responseStatus' => 'accepted',
            ];
        }

        // Add customer as guest/attendee
        if ($meeting->customer && !empty($meeting->customer->email)) {
            $attendees[] = [
                'email' => $meeting->customer->email,
                'displayName' => $meeting->customer->name,
                'organizer' => false,
                'responseStatus' => 'needsAction',
            ];
        }

        if (!empty($attendees)) {
            $payload['attendees'] = $attendees;
        }

        return $payload;
    }

    public function createEvent($meeting): ?string
    {
        $calendarId = urlencode((string) $this->getPrimaryCalendarId());
        $payload = $this->authorizedRequest('POST', "https://www.googleapis.com/calendar/v3/calendars/{$calendarId}/events", [
            'json' => $this->buildEventPayload($meeting),
        ]);

        return $payload['id'] ?? null;
    }

    public function updateEvent($meeting): bool
    {
        if (!$meeting->google_event_id) {
            return false;
        }

        $calendarId = urlencode((string) $this->getPrimaryCalendarId());
        $eventId = urlencode((string) $meeting->google_event_id);
        $payload = $this->authorizedRequest('PUT', "https://www.googleapis.com/calendar/v3/calendars/{$calendarId}/events/{$eventId}", [
            'json' => $this->buildEventPayload($meeting),
        ]);

        return $payload !== null;
    }

    public function deleteEvent($meeting): bool
    {
        if (!$meeting->google_event_id) {
            return false;
        }

        $calendarId = urlencode((string) $this->getPrimaryCalendarId());
        $eventId = urlencode((string) $meeting->google_event_id);
        $payload = $this->authorizedRequest('DELETE', "https://www.googleapis.com/calendar/v3/calendars/{$calendarId}/events/{$eventId}");

        return $payload !== null;
    }

    public function getEvents(int $maxResults = 10): array
    {
        $calendarId = urlencode((string) $this->getPrimaryCalendarId());
        $payload = $this->authorizedRequest('GET', "https://www.googleapis.com/calendar/v3/calendars/{$calendarId}/events", [
            'query' => [
                'maxResults' => $maxResults,
                'orderBy' => 'startTime',
                'singleEvents' => 'true',
                'timeMin' => now()->toRfc3339String(),
            ],
        ]);

        if (!$payload || empty($payload['items']) || !is_array($payload['items'])) {
            return [];
        }

        return array_map(static function (array $event): array {
            return [
                'id' => $event['id'] ?? null,
                'summary' => $event['summary'] ?? '',
                'description' => $event['description'] ?? '',
                'location' => $event['location'] ?? '',
                'start' => $event['start']['dateTime'] ?? ($event['start']['date'] ?? null),
                'end' => $event['end']['dateTime'] ?? ($event['end']['date'] ?? null),
            ];
        }, $payload['items']);
    }

    public function disconnect(): bool
    {
        try {
            if (Storage::exists(self::TOKEN_FILE)) {
                Storage::delete(self::TOKEN_FILE);
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Google Calendar Disconnect Error: ' . $e->getMessage());
            return false;
        }
    }

    public function syncMeeting($meeting): bool
    {
        if ($meeting->google_event_id) {
            return $this->updateEvent($meeting);
        }

        $eventId = $this->createEvent($meeting);
        if (!$eventId) {
            return false;
        }

        $meeting->google_event_id = $eventId;
        $meeting->save();

        return true;
    }
}
