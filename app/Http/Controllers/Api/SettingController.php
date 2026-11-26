<?php

namespace App\Http\Controllers\Api;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends ApiBaseController
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()?->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access to settings.'], 403);
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $group = $request->get('group');
        $settings = Setting::when($group, function($query) use ($group) {
            return $query->where('group', $group);
        })->whereNotIn('key', \App\Services\FallbackMailService::SENSITIVE_SETTING_KEYS)->get();

        return $this->success($settings);
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|exists:settings,key',
            'settings.*.value' => 'nullable',
        ]);

        foreach ($request->settings as $item) {
            Setting::where('key', $item['key'])->update(['value' => $item['value']]);
        }

        return $this->success(null, 'Settings updated successfully');
    }

    public function show($key)
    {
        if (in_array($key, \App\Services\FallbackMailService::SENSITIVE_SETTING_KEYS, true)) {
            return response()->json(['success' => false, 'message' => 'Access to sensitive setting is restricted.'], 403);
        }
        $setting = Setting::where('key', $key)->firstOrFail();
        return $this->success($setting);
    }
}
