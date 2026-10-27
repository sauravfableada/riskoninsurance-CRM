<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\GoogleCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    private function googleService(): ?GoogleCalendarService
    {
        try {
            return app(GoogleCalendarService::class);
        } catch (\Throwable $e) {
            Log::warning('Google Calendar service unavailable', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function show()
    {
        $user = Auth::user();
        $settings = $this->profileSettings();

        return view('profile.show', compact('user', 'settings'));
    }

    public function apiShow(): JsonResponse
    {
        $user = Auth::user();
        $settings = $this->profileSettings();
        $defaultAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=random&color=fff';
        $defaultLogo = url((env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '') . 'images/template/crmfavicon.png');
        $avatarUrl = !empty($user->avatar_path)
            ? route('users.image', $user) . '?v=' . optional($user->updated_at)->timestamp
            : $defaultAvatar;
        $companyLogoPath = $settings['company_logo_path'] ?? null;
        $companyLogoUrl = $companyLogoPath && Storage::disk('public')->exists($companyLogoPath)
            ? route('profile.company_logo.image') . '?v=' . Storage::disk('public')->lastModified($companyLogoPath)
            : $defaultLogo;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                'settings' => $settings,
                'google_connected' => (bool) app('view')->shared('googleCalendarConnected', false),
                'avatar_url' => $avatarUrl,
                'company_logo_url' => $companyLogoUrl,
            ],
        ]);
    }

    public function disconnectGoogle()
    {
        abort_unless($this->googleConnectionEnabled(), 404);
        $googleService = $this->googleService();

        if (!$googleService) {
            return redirect()->route('profile.show')->with('error', 'Google Calendar integration is not available.');
        }

        if (!$googleService->isAuthenticated()) {
            return redirect()->route('profile.show')->with('error', 'Google Calendar is not connected.');
        }

        $success = $googleService->disconnect();

        return redirect()->route('profile.show')->with(
            $success ? 'success' : 'error',
            $success ? 'Google Calendar disconnected successfully.' : 'Failed to disconnect Google Calendar.'
        );
    }

    public function apiDisconnectGoogle(): JsonResponse
    {
        if (!$this->googleConnectionEnabled()) {
            return response()->json(['success' => false, 'message' => 'Google connection is disabled.'], 404);
        }
        $googleService = $this->googleService();

        if (!$googleService) {
            return response()->json(['success' => false, 'message' => 'Google Calendar integration is not available.'], 422);
        }

        if (!$googleService->isAuthenticated()) {
            return response()->json(['success' => false, 'message' => 'Google Calendar is not connected.'], 422);
        }

        $success = $googleService->disconnect();

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Google Calendar disconnected successfully.' : 'Failed to disconnect Google Calendar.',
        ], $success ? 200 : 500);
    }

    public function companyLogoImage()
    {
        $path = Setting::query()->where('key', 'company_logo_path')->value('value');

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Response::file(Storage::disk('public')->path($path));
    }

    public function shortCompanyLogoImage()
    {
        $path = Setting::query()->where('key', 'short_company_logo_path')->value('value');

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Response::file(Storage::disk('public')->path($path));
    }

    private function googleConnectionEnabled(): bool
    {
        return Setting::query()->where('key', 'google_connection_enabled')->value('value') === '1';
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        try {
            $this->persistProfileUpdate($request, $user);

            return redirect()->route('profile.show')->with('success', 'Profile updated successfully.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Profile update failed', [
                'user_id' => $user?->id,
                'message' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Profile update failed. Please check inputs and try again.');
        }
    }

    public function apiUpdate(Request $request): JsonResponse
    {
        $user = Auth::user();
        $this->persistProfileUpdate($request, $user);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    public function apiUpdatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }

    private function persistProfileUpdate(Request $request, $user): void
    {
        $imageFileRule = static function (string $attribute, $value, \Closure $fail): void {
            if (!$value || !$value->isValid()) {
                return;
            }

            $mimeType = (string) $value->getMimeType();

            if (!str_starts_with($mimeType, 'image/')) {
                $fail('Please upload a valid image file.');
            }
        };

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['required', 'string', 'max:20'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'company_address' => ['required', 'string', 'max:255'],
            'company_tax_id' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'file', $imageFileRule, 'max:2048'],
            'company_logo_path' => ['nullable', 'file', $imageFileRule, 'max:2048'],
            'short_company_logo_path' => ['nullable', 'file', $imageFileRule, 'max:2048'],
        ], [
            'name.required' => 'Name is required!',
            'phone.required' => 'Phone number is required!',
            'company_name.required' => 'Company name is required!',
            'company_address.required' => 'Address is required!',
            'avatar.max' => 'Profile image must not be larger than 2 MB.',
            'company_logo_path.max' => 'Company logo must not be larger than 2 MB.',
            'short_company_logo_path.max' => 'Short company logo must not be larger than 2 MB.',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $data['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        $userData = collect($data)->except(['company_name', 'company_address', 'company_tax_id', 'avatar', 'company_logo_path', 'short_company_logo_path'])->all();
        $user->update($userData);

        Setting::updateOrCreate(
            ['key' => 'company_name'],
            ['value' => $request->input('company_name'), 'group' => 'general', 'type' => 'string']
        );
        Setting::updateOrCreate(
            ['key' => 'company_address'],
            ['value' => $request->input('company_address'), 'group' => 'general', 'type' => 'string']
        );
        Setting::updateOrCreate(
            ['key' => 'company_tax_id'],
            ['value' => $request->input('company_tax_id'), 'group' => 'general', 'type' => 'string']
        );

        if ($request->hasFile('company_logo_path')) {
            $oldLogo = Setting::query()->where('key', 'company_logo_path')->value('value');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }

            $logoPath = $request->file('company_logo_path')->store('company', 'public');
            Setting::updateOrCreate(
                ['key' => 'company_logo_path'],
                ['value' => $logoPath, 'group' => 'general', 'type' => 'string']
            );
        }

        if ($request->hasFile('short_company_logo_path')) {
            $oldShortLogo = Setting::query()->where('key', 'short_company_logo_path')->value('value');
            if ($oldShortLogo && Storage::disk('public')->exists($oldShortLogo)) {
                Storage::disk('public')->delete($oldShortLogo);
            }

            $shortLogoPath = $request->file('short_company_logo_path')->store('company', 'public');
            Setting::updateOrCreate(
                ['key' => 'short_company_logo_path'],
                ['value' => $shortLogoPath, 'group' => 'general', 'type' => 'string']
            );
        }
    }

    private function profileSettings()
    {
        return Setting::query()->whereIn('key', [
            'company_name',
            'company_address',
            'company_tax_id',
            'company_logo_path',
            'short_company_logo_path',
        ])->pluck('value', 'key');
    }
}
