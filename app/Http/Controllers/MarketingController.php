<?php

namespace App\Http\Controllers;

use App\Mail\MarketingMail;
use App\Models\MarketingTemplate;
use App\Models\MarketingCampaign;
use App\Models\CampaignLog;
use App\Models\Lead;
use App\Models\Customer;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class MarketingController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_templates' => MarketingTemplate::count(),
            'total_campaigns' => MarketingCampaign::count(),
            'total_sent' => CampaignLog::where('status', 'Sent')->count(),
            'recent_logs' => CampaignLog::with('campaign')->latest()->take(5)->get(),
        ];
        return view('crm.marketing.dashboard', compact('stats'));
    }

    public function templatesIndex(Request $request)
    {
        if ($request->ajax()) {
            $search = trim((string) $request->get('search', ''));

            $query = MarketingTemplate::query()->latest();

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('template_name', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            }

            $templates = $query->paginate(10)->appends($request->query());

            return response()->json([
                'success' => true,
                'message' => 'Templates retrieved successfully.',
                'data' => $templates,
            ]);
        }

        return view('crm.marketing.templates.index');
    }

    public function templatesCreate()
    {
        return view('crm.marketing.templates.create');
    }

    public function templatesStore(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:255',
                'template_name' => 'required|string|max:255',
                'status' => 'required|in:active,inactive',
                'image_1' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'image_2' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'image_3' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ],
            [
                'template_name.required' => 'Please select a template.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Create template first
        $template = MarketingTemplate::create([
            'name' => $request->name,
            'template_name' => $request->template_name,
            'status' => $request->status,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        // Handle image uploads
        $imageFields = ['image_1', 'image_2', 'image_3'];

        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {

                $image = $request->file($field);
                $imageName = uniqid() . '_' . time() . '.' . $image->getClientOriginalExtension();

                $image->move(public_path('images/template'), $imageName);

                $template->update([
                    $field => $imageName
                ]);
            }
        }
        return response()->json([
            'message' => 'Template created successfully.'
        ], 200);
    }


    public function templatesEdit(MarketingTemplate $template)
    {
        return view('crm.marketing.templates.edit', compact('template'));
    }

    public function templatesUpdate(Request $request, MarketingTemplate $template)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:255',
                'template_name' => 'required|string|max:255',
                'status' => 'required|in:active,inactive',

                'image_1' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'image_2' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'image_3' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ],
            [
                'template_name.required' => 'Please select a template.',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Update basic fields
        $template->update([
            'name' => $request->name,
            'template_name' => $request->template_name,
            'status' => $request->status,
            'updated_by' => auth()->id(),
        ]);

        // Image keys
        $imageFields = ['image_1', 'image_2', 'image_3'];

        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {

                // delete old image if exists
                if (!empty($template->$field)) {
                    $oldPath = public_path('images/template/' . $template->$field);
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                // upload new image
                $image = $request->file($field);
                $imageName = uniqid() . '_' . time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/template'), $imageName);

                // update field
                $template->update([$field => $imageName]);
            }
        }

        return response()->json(['message' => 'Template updated successfully.'], 200);
    }


    public function templatesDestroy(MarketingTemplate $template)
    {
        $template->delete();
        $template->update(['deleted_by' => auth()->id()]);
        return response()->json(['message' => 'Template deleted successfully.'], 200);
    }

    public function bulkSendMail(Request $request)
    {
        setMailConfig();
        // Validate input
        $validator = Validator::make(
            $request->all(),
            [
                'subject' => 'required|string|max:255',
                'customers' => 'required|array',
                'customers.*' => 'exists:customers,id',
                'template_id' => 'required|exists:marketing_templates,id',
            ],
            [
                'customers.required' => 'Please select at least one customer.',
                'template_id.required' => 'Please select a template.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Fetch customers
        if (in_array('all', $request->customers)) {
            $customerList = Customer::whereNotNull('email')
                ->where('email', '!=', '')
                ->select('id', 'name', 'email')
                ->get();
        } else {
            $customerList = Customer::whereIn('id', $request->customers)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->select('id', 'name', 'email')
                ->get();
        }

        // Fetch template
        $template = MarketingTemplate::find($request->template_id);

        if (!$template) {
            return response()->json([
                'status' => 'error',
                'message' => 'Template not found!'
            ], 404);
        }

        // Template file path
        $templateView = 'crm.marketing.email.' . $template->template_name;

        if (!view()->exists($templateView)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email template file not found!'
            ], 404);
        }

        // Send email to each customer
        foreach ($customerList as $customer) {

            $messageImages = [];

            // Prepare image embedding
            foreach (['image_1', 'image_2', 'image_3'] as $field) {
                if (!empty($template->$field)) {

                    $path = public_path('images/template/' . $template->$field);

                    if (file_exists($path)) {
                        $messageImages[$field] = $path;
                    }
                }
            }

            Mail::send([], [], function ($message) use ($customer, $request, $templateView, $messageImages) {

                // Embed images and pass CID to blade
                $embedded = [];
                foreach ($messageImages as $key => $path) {
                    $embedded[$key] = $message->embed($path);
                }

                // Render email template with EMBEDDED image CIDs
                $emailHtml = view($templateView, [
                    'company_name' => config('app.name'),
                    'user_name' => $customer->name,
                    'image_1' => $embedded['image_1'] ?? null,
                    'image_2' => $embedded['image_2'] ?? null,
                    'image_3' => $embedded['image_3'] ?? null,
                ])->render();

                $fromAddress = config('mail.from.address') ?: config('mail.mailers.smtp.username');
                $fromName = config('mail.from.name') ?: config('app.name');

                if (empty($fromAddress)) {
                    \Log::error('Mail sending failed: From address is not configured.');
                    return;
                }

                $message->from($fromAddress, $fromName)
                    ->to($customer->email)
                    ->subject($request->subject)
                    ->html($emailHtml);

                $template = MarketingTemplate::find($request->template_id);
                MarketingCampaign::create([
                    'name' => $customer->name,
                    'audience_type' => 'Customers',
                    'marketing_template_id' => $template->id,
                    'sent_at' => now(),
                    'status' => 'Sent',
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            });

            // usleep(6000000);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Emails sent successfully!'
        ]);
    }

    public function templatesShow(Request $request, MarketingTemplate $template)
    {
        // dd($template);  
        return view('crm.marketing.templates.show', compact('template'));
    }

    public function campaignsIndex(Request $request)
    {
        if ($request->ajax()) {
            $search = trim((string) $request->get('search', ''));

            $query = MarketingCampaign::with('template')->latest();

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('audience_type', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('template', function ($templateQuery) use ($search) {
                            $templateQuery->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $campaigns = $query->paginate(10)->appends($request->query());

            return response()->json([
                'success' => true,
                'message' => 'Campaigns retrieved successfully.',
                'data' => $campaigns,
            ]);
        }
        $customers = Customer::query()->where('user_id', auth()->id())->orderBy('name')->get();
        $templates = MarketingTemplate::where('status', 'active')->get();
        return view('crm.marketing.campaigns.index', compact('templates', 'customers'));
    }

    public function campaignsDestroy(Request $request, MarketingCampaign $campaign)
    {
        $campaign->delete();
        $campaign->update(['deleted_by' => auth()->id()]);
        return response()->json(['message' => 'Campaign deleted successfully.'], 200);
    }

    public function campaignsCreate()
    {
        $templates = MarketingTemplate::where('is_active', true)->get();
        return view('crm.marketing.campaigns.create', compact('templates'));
    }

    public function campaignsStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'marketing_template_id' => 'required|exists:marketing_templates,id',
            'audience_type' => 'required|in:Leads,Customers,Agents',
        ]);

        MarketingCampaign::create($request->all());

        return redirect()->route('marketing.campaigns.index')->with('success', 'Campaign created successfully.');
    }

    public function sendCampaign(MarketingCampaign $campaign)
    {
        $campaign->update(['status' => 'Sending']);
        $template = $campaign->template;
        $recipients = [];

        // Determine Audience - Use query() to respect global scopes
        if ($campaign->audience_type === 'Leads') {
            $recipients = Lead::query()->get();
        } elseif ($campaign->audience_type === 'Customers') {
            $recipients = Customer::query()->get();
        } elseif ($campaign->audience_type === 'Agents') {
            $recipients = Agent::query()->get();
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($recipients as $recipient) {
            try {
                // Shortcode Replacement Logic
                $content = $template->content;
                $subject = $template->subject;

                $placeholders = [
                    '{customer_name}' => $recipient->name,
                    '{email}' => $recipient->email,
                    '{phone}' => $recipient->phone ?? $recipient->mobile ?? '--',
                ];

                $finalContent = str_replace(array_keys($placeholders), array_values($placeholders), $content);
                $finalSubject = str_replace(array_keys($placeholders), array_values($placeholders), $subject);

                // Simulation: In a real app, use Mail::send() or SMS API here
                // For now, we log the success to CampaignLog
                CampaignLog::create([
                    'marketing_campaign_id' => $campaign->id,
                    'recipient_email' => $recipient->email,
                    'recipient_phone' => $recipient->phone ?? $recipient->mobile,
                    'status' => 'Sent'
                ]);

                $successCount++;
            } catch (\Exception $e) {
                CampaignLog::create([
                    'marketing_campaign_id' => $campaign->id,
                    'recipient_email' => $recipient->email,
                    'recipient_phone' => $recipient->phone ?? $recipient->mobile,
                    'status' => 'Failed',
                    'error_message' => $e->getMessage()
                ]);
                $failCount++;
            }
        }

        $campaign->update([
            'status' => 'Completed',
            'sent_at' => now()
        ]);

        return redirect()->back()->with('success', "Campaign processed: {$successCount} sent, {$failCount} failed.");
    }
}
