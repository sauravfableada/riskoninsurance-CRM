<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Policy;
use App\Models\PolicyPdfHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class CustomerPortalController extends Controller
{
    /**
     * Show customer login page.
     */
    public function showLogin()
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('customer.portal.dashboard');
        }

        return view('portal.customer.login');
    }

    /**
     * Handle customer login attempt.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required' => 'Please enter your email or mobile number.',
            'password.required' => 'Password is required.',
        ]);

        $loginInput = trim($request->input('login'));
        $password = $request->input('password');
        $remember = (bool) $request->boolean('remember');

        // Extract digits only for phone matching
        $digitsOnly = preg_replace('/[^0-9]/', '', $loginInput);

        // Find customer by email or phone
        $customer = Customer::where(function ($query) use ($loginInput, $digitsOnly) {
            $query->where('email', $loginInput);

            if (!empty($digitsOnly)) {
                $query->orWhere('phone', $loginInput)
                      ->orWhere('phone', 'like', '%' . $digitsOnly)
                      ->orWhere('whatsapp', $loginInput)
                      ->orWhere('whatsapp', 'like', '%' . $digitsOnly);
            }
        })->first();

        if (!$customer) {
            return back()->withErrors([
                'login' => 'No customer account found with this email or mobile number.',
            ])->withInput($request->only('login'));
        }

        // Allow initial login if password is not set yet (Default initial password: phone or welcome123)
        if (empty($customer->password)) {
            $defaultPassword = $customer->phone ? preg_replace('/[^0-9]/', '', $customer->phone) : 'welcome123';
            if ($password === $defaultPassword || $password === 'welcome123') {
                $customer->password = Hash::make($password);
                $customer->save();
            }
        }

        // Validate password against hashed customer password
        if ($customer->password && Hash::check($password, $customer->password)) {
            Auth::guard('customer')->login($customer, $remember);
            $request->session()->regenerate();
            return redirect()->intended(route('customer.portal.dashboard'));
        }

        return back()->withErrors([
            'login' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('login'));
    }

    /**
     * Customer portal logout.
     */
    public function logout(Request $request)
    {
        $token = $request->cookie('crm_auth_token');
        if ($token) {
            try {
                \Laravel\Sanctum\PersonalAccessToken::findToken($token)?->delete();
            } catch (\Exception $e) {
                // Ignore
            }
        }

        if (Auth::guard('customer')->check()) {
            try {
                Auth::guard('customer')->user()?->tokens()->delete();
            } catch (\Exception $e) {
                // Ignore
            }
        }

        Auth::guard('customer')->logout();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $response = redirect()->route('login')->with('success', 'You have been successfully logged out.');
        $response->cookie(\Illuminate\Support\Facades\Cookie::forget('crm_auth_token'));

        return $response;
    }

    /**
     * Customer Dashboard & Policies list.
     */
    public function dashboard(Request $request)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user() ?: (Auth::user() instanceof Customer ? Auth::user() : $request->user());

        if (!$customer || !($customer instanceof Customer)) {
            return redirect()->route('login');
        }

        $search = $request->query('search');

        $policiesQuery = Policy::query()
            ->where('holder_id', $customer->id)
            ->with(['policyType', 'company', 'branch', 'pdfHistories']);

        if (!empty($search)) {
            $policiesQuery->where(function ($q) use ($search) {
                $q->where('policy_number', 'like', "%{$search}%")
                  ->orWhere('policy_number_no', 'like', "%{$search}%")
                  ->orWhere('vehicle_registration_number', 'like', "%{$search}%");
            });
        }

        $policies = $policiesQuery->latest()->paginate(10)->withQueryString();

        // Calculate summary cards
        $totalPolicies = Policy::where('holder_id', $customer->id)->count();
        $activePolicies = Policy::where('holder_id', $customer->id)
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', now());
            })->count();
        $totalPremium = Policy::where('holder_id', $customer->id)->sum('premium_amount');

        return view('portal.customer.dashboard', compact(
            'customer',
            'policies',
            'totalPolicies',
            'activePolicies',
            'totalPremium',
            'search'
        ));
    }

    /**
     * Customer Policy Detail View.
     */
    public function showPolicy($id)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $policy = Policy::where('holder_id', $customer->id)
            ->with([
                'policyType',
                'policySubType',
                'segment',
                'familyGroup',
                'company',
                'branch',
                'user',
                'creator',
                'pdfHistories.uploader',
                'kycDocument'
            ])
            ->findOrFail($id);

        $kyc = $policy->kycDocument;

        return view('portal.customer.policy_detail', compact('customer', 'policy', 'kyc'));
    }

    /**
     * Customer Profile Page.
     */
    public function profile()
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user() ?: (Auth::user() instanceof Customer ? Auth::user() : request()->user());

        if (!$customer || !($customer instanceof Customer)) {
            return redirect()->route('login');
        }

        // Fetch customer policies with relations
        $policies = Policy::where('holder_id', $customer->id)
            ->with(['company', 'policyType', 'pdfHistories'])
            ->orderBy('id', 'DESC')
            ->get();

        // Calculate pending payments (policies with pending renewal or unpaid premium)
        $today = \Carbon\Carbon::today();
        $pendingPayments = $policies->filter(function ($p) use ($today) {
            $endDate = $p->end_date ? \Carbon\Carbon::parse($p->end_date)->startOfDay() : null;
            $isRenewalDue = $endDate && $endDate->diffInDays($today, false) <= 0 && $today->diffInDays($endDate, false) <= 30;
            $hasPendingAmount = empty($p->payment_date) || empty($p->payment_amount) || ($p->premium_amount > 0 && empty($p->payment_mode));
            return $isRenewalDue || $hasPendingAmount;
        });

        // Payment history (policies with recorded payments or completed premiums)
        $paymentHistory = $policies->filter(function ($p) {
            return !empty($p->payment_date) || !empty($p->payment_amount) || !empty($p->payment_mode);
        });

        return view('portal.customer.profile', compact('customer', 'policies', 'pendingPayments', 'paymentHistory'));
    }

    /**
     * Update Customer Profile.
     */
    public function updateProfile(Request $request)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user() ?: (Auth::user() instanceof Customer ? Auth::user() : request()->user());

        if (!$customer || !($customer instanceof Customer)) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? $customer->phone,
            'whatsapp' => $validated['whatsapp'] ?? $customer->whatsapp,
            'address' => $validated['address'] ?? $customer->address,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $customer->update($updateData);

        return back()->with('success', 'Your profile details have been updated successfully.');
    }

    /**
     * Download or view policy document.
     */
    public function downloadDocument($documentId)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $document = PolicyPdfHistory::with('policy')->findOrFail($documentId);

        // Security check: ensure policy belongs to the logged-in customer
        if (!$document->policy || (int)$document->policy->holder_id !== (int)$customer->id) {
            abort(403, 'Unauthorized access to this document.');
        }

        $filePath = 'policies/' . basename($document->file_name);

        if (!Storage::disk('public')->exists($filePath)) {
            // Check direct storage / alternative paths
            if (Storage::exists($document->file_name)) {
                return response()->download(Storage::path($document->file_name), basename($document->original_file_name ?: $document->file_name));
            }
            abort(404, 'Policy document file not found on server.');
        }

        $fullPath = Storage::disk('public')->path($filePath);
        $downloadName = basename($document->original_file_name ?: $document->file_name);

        return response()->download($fullPath, $downloadName);
    }

    /**
     * Serve authenticated customer's profile avatar/image.
     */
    public function profileImage()
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user() ?: (Auth::user() instanceof Customer ? Auth::user() : null);

        if (!$customer || !$customer->image) {
            abort(404);
        }

        $path = ltrim((string) $customer->image, '/');
        $path = preg_replace('#^(storage/|public/)#', '', $path) ?? $path;

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($path));
    }
}
