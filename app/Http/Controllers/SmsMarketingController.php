<?php

namespace App\Http\Controllers;

use App\Models\SmsTemplate;
use App\Models\SmsLog;
use App\Models\Setting;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsMarketingController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $search = trim((string) $request->get('search', ''));

            $query = SmsTemplate::query()->orderByDesc('created_at');

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            }

            $templates = $query->paginate(10)->appends($request->query());

            return response()->json([
                'success' => true,
                'message' => 'SMS templates retrieved successfully.',
                'data' => $templates,
            ]);
        }

        $templates = SmsTemplate::orderByDesc('created_at')->get();

        $credentials = [
            'sms_default_service' => Setting::where('key', 'sms_default_service')->first()->value ?? 'twilio',
            'twilio_sid' => Setting::where('key', 'twilio_sid')->first()->value ?? '',
            'twilio_auth_token' => Setting::where('key', 'twilio_auth_token')->first()->value ?? '',
            'twilio_phone_number' => Setting::where('key', 'twilio_phone_number')->first()->value ?? '',
        ];

        return view('sms_marketing.index', compact('templates', 'credentials'));
    }

    public function logs(Request $request)
    {
        if ($request->ajax()) {
            $search = trim((string) $request->get('search', ''));

            $query = SmsLog::with(['customer'])->orderByDesc('send_date');

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('template_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('service', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $logs = $query->paginate(10)->appends($request->query());

            return response()->json([
                'success' => true,
                'message' => 'SMS logs retrieved successfully.',
                'data' => $logs,
            ]);
        }

        $templates = SmsTemplate::where('status', 'active')->get();
        $customers = Customer::where('is_active', 1)->limit(50)->get();

        return view('sms_marketing.logs', compact('templates', 'customers'));
    }

    public function createTemplate()
    {
        return view('sms_marketing.templates.create');
    }

    public function storeTemplate(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
            'content' => 'required|string',
        ]);

        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        SmsTemplate::create($data);

        return response()->json(['success' => true, 'message' => 'SMS template created successfully.']);
    }

    public function editTemplate(SmsTemplate $sms_template)
    {
        return view('sms_marketing.templates.edit', ['record' => $sms_template]);
    }

    public function updateTemplate(Request $request, SmsTemplate $sms_template)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
            'content' => 'required|string',
        ]);

        $data['updated_by'] = Auth::id();

        $sms_template->update($data);

        return response()->json(['success' => true, 'message' => 'SMS template updated successfully.']);

    }

    public function destroyTemplate(SmsTemplate $sms_template)
    {
        $sms_template->deleted_by = Auth::id();
        $sms_template->save();
        $sms_template->delete();

        return response()->json(['success' => true, 'message' => 'SMS template deleted successfully.']);
    }

    public function saveCredentials(Request $request)
    {
        $data = $request->validate([
            'sms_default_service' => 'required|string',
            'twilio_sid' => 'nullable|string',
            'twilio_auth_token' => 'nullable|string',
            'twilio_phone_number' => 'nullable|string',
        ]);

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => 'integrations', 'type' => 'string']
            );
        }

        return response()->json(['success' => true, 'message' => 'SMS credentials saved successfully.']);

    }

    public function sendSms(Request $request)
    {
        $request->validate(
            [
                'customer_ids' => 'required|array',
                'template_id' => 'required|exists:sms_templates,id',
            ],
            [
                'customer_ids.required' => 'Please select at least one customer.',
                'template_id.required' => 'Please select a template.',
            ]
        );

        $template = SmsTemplate::findOrFail($request->template_id);
        $customers = Customer::whereIn('id', $request->customer_ids)->get();

        $sid = Setting::where('key', 'twilio_sid')->first()->value ?? '';
        $token = Setting::where('key', 'twilio_auth_token')->first()->value ?? '';
        $from = Setting::where('key', 'twilio_phone_number')->first()->value ?? '';

        if (empty($sid) || empty($token) || empty($from)) {
            return response()->json(['success' => false, 'message' => 'Twilio credentials are not configured.'], 400);
        }

        $results = [
            'success_count' => 0,
            'fail_count' => 0,
        ];

        foreach ($customers as $customer) {
            if (empty($customer->phone)) {
                $results['fail_count']++;
                continue;
            }

            // Replace shortcodes
            $message = str_replace('[user_name]', $customer->name, $template->content);
            $message = str_replace('[company_name]', $customer->company_name ?? 'Our Company', $message);

            // Send via Twilio
            $sent = $this->sendViaTwilio($customer->phone, $message, $sid, $token, $from);

            if ($sent) {
                $results['success_count']++;
                // Log the entry
                SmsLog::create([
                    'customer_id' => $customer->id,
                    'template_id' => $template->id,
                    'template_name' => $template->name,
                    'customer_phone' => $customer->phone,
                    'message_body' => $message,
                    'send_date' => now(),
                    'service' => 'twilio',
                    'status' => 'sent',
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            } else {
                $results['fail_count']++;
                // Log failed entry
                SmsLog::create([
                    'customer_id' => $customer->id,
                    'template_id' => $template->id,
                    'template_name' => $template->name,
                    'customer_phone' => $customer->phone,
                    'message_body' => $message,
                    'send_date' => now(),
                    'service' => 'twilio',
                    'status' => 'failed',
                    'created_by' => Auth::id(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "SMS sending completed. Success: {$results['success_count']}, Failed: {$results['fail_count']}"
        ]);
    }

    private function sendViaTwilio($to, $message, $sid, $token, $from)
{
    try {
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

        $response = Http::withBasicAuth($sid, $token)->asForm()->post($url, [
            'From' => $from,
            'To'   => '+91' . $to,
            'Body' => $message,
        ]);

        if ($response->successful()) {
            return true;
        }

        Log::error('Twilio API Error: ' . $response->body());
        return false;

    } catch (\Exception $e) {
        Log::error('Twilio API Exception: ' . $e->getMessage());
        return false;
    }
}
    // private function sendViaTwilio($to, $message, $sid, $token, $from)
    // {
    //     try {
    //         $client = new Client($sid, $token);
    //         $client->messages->create('+91' . $to, [
    //             'from' => $from,
    //             'body' => $message
    //         ]);
    //         return true;
    //     } catch (\Exception $e) {
    //         Log::error('Twilio SMS Exception: ' . $e->getMessage());
    //         return false;
    //     }
    // }
    public function destroy(SmsLog $sms_log)
    {
        $sms_log->delete();
        $sms_log->deleted_by = Auth::id();
        $sms_log->save();

        return response()->json(['success' => true, 'message' => 'SMS log deleted successfully.']);
    }

}
