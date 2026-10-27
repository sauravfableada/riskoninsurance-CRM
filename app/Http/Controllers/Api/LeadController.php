<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiBaseController;
use App\Models\Lead;
use App\Models\LeadStatusHistory;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class LeadController extends ApiBaseController
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            Log::error('LeadController: No authenticated user');
            return $this->error('Unauthorized', 401);
        }

        Log::info('LeadController.index called', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'is_admin' => $user->isAdmin(),
            'bearer_token' => request()->bearerToken() ? substr(request()->bearerToken(), 0, 20) . '...' : 'NONE',
        ]);

        $search = $request->get('search');

        $status = $request->get('status');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $perPage = max(1, min(100, (int) $request->get('per_page', 10)));

        $leads = $this->scopeOwnedRecords(
            Lead::query()->with(['leadSource', 'stage', 'assignedUser'])
        )
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('group', 'like', "%{$search}%")
                        ->orWhere('source', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('sic_code', 'like', "%{$search}%");
                });
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($request->get('name'), function ($query, $name) {
                $query->where('name', 'like', "%{$name}%");
            })
            ->when($request->get('phone'), function ($query, $phone) {
                $query->where('phone', 'like', "%{$phone}%");
            })
            ->when($request->get('group_id'), function ($query, $groupId) {
                $group = \App\Models\FamilyGroup::find($groupId);
                if ($group) {
                    $query->where('group', $group->group_name);
                }
            })
            ->when($request->get('source_id'), function ($query, $sourceId) {
                $query->where('lead_source_id', $sourceId);
            })
            ->when($request->get('created_by'), function ($query, $createdBy) {
                $query->where(function ($q) use ($createdBy) {
                    $q->where('assigned_user_id', $createdBy)->orWhere('created_by', $createdBy);
                });
            })
            ->when($fromDate, function ($query) use ($fromDate) {
                $query->where('created_at', '>=', $fromDate . ' 00:00:00');
            })
            ->when($toDate, function ($query) use ($toDate) {
                $query->where('created_at', '<=', $toDate . ' 23:59:59');
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        Log::info('LeadController.index result', [
            'total_leads' => $leads->total(),
            'per_page' => $leads->perPage(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leads retrieved successfully',
            'data' => $leads,
        ], 200);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $this->authorize('create', Lead::class);

        $this->mergeInternationalContactFields($request);
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:leads,email'],
            'country_code' => ['required', 'regex:/^\+[0-9]{1,4}$/'],
            'phone' => ['required', 'regex:/^\+[0-9]{5,19}$/', 'unique:leads,phone'],
            'whatsapp_country_code' => ['required_with:whatsapp', 'regex:/^\+[0-9]{1,4}$/'],
            'whatsapp' => ['nullable', 'regex:/^\+[0-9]{5,19}$/'],
            'address' => ['nullable', 'string'],
            'image' => ['nullable', 'mimes:jpg,jpeg,png,gif,bmp,webp,avif,svg', 'max:2048'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'sic_code' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'in:new,qualified,working,ready_to_close,won,lost'],
            'source' => ['nullable', 'string', 'max:255'],
            'lead_source_id' => ['nullable', 'exists:lead_sources,id'],
            'lead_stage_id' => ['nullable', 'exists:stages,id'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status_comment' => ['nullable', 'string', 'max:2000'],
        ], $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        if (empty($data['status'])) {
            $data['status'] = 'new';
        }

        $this->ensureAssignableUser($data['assigned_user_id'] ?? null, $user);
        if (Lead::supportsOwnedByUserColumn()) {
            $data['user_id'] = $this->resolveOwnedUserId($data['assigned_user_id'] ?? null, $user->id);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('leads', 'public');
        }

        $lead = Lead::create($data);

        $historyEntry = $this->recordStatusHistory($lead, $data['status'] ?? null, $data['status_comment'] ?? null);
        app(\App\Services\UserLogService::class)->created($lead);

        if ($request->has('custom_fields')) {
            $lead->saveCustomFields($request->get('custom_fields'));
        }

        $this->createAutoTaskForLead($lead);

        // WhatsApp notification — fire & forget, never breaks main flow
        try {
            $phone = $lead->whatsapp ?: $lead->phone;
            if ($phone) {
                app(\App\Services\WhatsAppService::class)->sendForModule(
                    'customer_created',
                    $phone,
                    [$lead->name],
                    $lead->id
                );
            }
        } catch (\Throwable) {
        }

        return response()->json([
            'success' => true,
            'message' => 'Lead created successfully.',
            'data' => $lead->fresh(['leadSource', 'stage', 'assignedUser']),
            'history_entry' => $this->serializeHistoryEntry($historyEntry),
            'redirect' => route('leads.index'),
        ], 201);
    }

    public function import(Request $request)
    {
        $this->authorize('create', Lead::class);
        @set_time_limit(300);

        $request->validate([
            'import_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ], [
            'import_file.mimes' => 'Please upload a CSV file exported from Excel.',
        ]);

        $handle = fopen($request->file('import_file')->getRealPath(), 'r');
        if ($handle === false) {
            return response()->json(['success' => false, 'message' => 'Unable to read the uploaded file.'], 422);
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return response()->json(['success' => false, 'message' => 'The CSV file is empty.'], 422);
        }

        $delimiterCounts = [',' => substr_count($firstLine, ','), ';' => substr_count($firstLine, ';'), "\t" => substr_count($firstLine, "\t")];
        arsort($delimiterCounts);
        $delimiter = (string) array_key_first($delimiterCounts);
        rewind($handle);

        $headers = fgetcsv($handle, 0, $delimiter);
        $headers = array_map(fn ($header) => strtolower(trim((string) $header, " \t\n\r\0\x0B\xEF\xBB\xBF")), $headers ?: []);
        $nameIndex = array_search('name', $headers, true);
        $phoneIndex = array_search('phone', $headers, true);
        $groupIndex = array_search('groups', $headers, true);
        if ($groupIndex === false) {
            $groupIndex = array_search('group', $headers, true);
        }

        if ($nameIndex === false || $phoneIndex === false || $groupIndex === false) {
            fclose($handle);
            return response()->json([
                'success' => false,
                'message' => 'CSV headers must include name, phone, and groups.',
            ], 422);
        }

        $knownPhones = DB::table('leads')->pluck('phone')->mapWithKeys(function ($phone) {
            $key = $this->phoneDuplicateKey((string) $phone);
            return $key !== '' ? [$key => true] : [];
        })->all();

        $imported = 0;
        $duplicates = 0;
        $skipped = 0;
        $userId = auth()->id();
        $timestamp = now();

        DB::transaction(function () use ($handle, $delimiter, $nameIndex, $phoneIndex, $groupIndex, &$knownPhones, &$imported, &$duplicates, &$skipped, $userId, $timestamp) {
            $batch = [];

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $name = trim((string) ($row[$nameIndex] ?? ''));
                $rawPhone = trim((string) ($row[$phoneIndex] ?? ''));
                $group = trim((string) ($row[$groupIndex] ?? ''));
                $digits = $this->normalizeImportedPhone($rawPhone);

                if ($name === '' || $digits === '' || strlen($digits) < 10) {
                    $skipped++;
                    continue;
                }

                $duplicateKey = $this->phoneDuplicateKey($digits);
                if (isset($knownPhones[$duplicateKey])) {
                    $duplicates++;
                    continue;
                }

                $localPhone = strlen($digits) === 12 && str_starts_with($digits, '91') ? substr($digits, 2) : $digits;
                $countryCode = strlen($localPhone) === 10 ? '+91' : '+' . substr($digits, 0, max(1, strlen($digits) - 10));
                $fullPhone = strlen($localPhone) === 10 ? '+91' . $localPhone : '+' . $digits;

                $batch[] = [
                    'name' => $name,
                    'phone' => $fullPhone,
                    'country_code' => $countryCode,
                    'group' => $group !== '' ? $group : null,
                    'status' => 'new',
                    'assigned_user_id' => $userId,
                    'user_id' => $userId,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];

                $knownPhones[$duplicateKey] = true;
                $imported++;

                if (count($batch) >= 500) {
                    DB::table('leads')->insert($batch);
                    $batch = [];
                }
            }

            if ($batch !== []) {
                DB::table('leads')->insert($batch);
            }
        });

        fclose($handle);

        return response()->json([
            'success' => true,
            'message' => "{$imported} lead(s) imported. {$duplicates} duplicate phone number(s) skipped. {$skipped} invalid row(s) skipped.",
            'summary' => compact('imported', 'duplicates', 'skipped'),
        ]);
    }

    private function normalizeImportedPhone(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^[+-]?[0-9]+(?:\.[0-9]+)?[eE][+-]?[0-9]+$/', $value)) {
            $value = sprintf('%.0f', (float) $value);
        }

        return preg_replace('/\D+/', '', $value) ?: '';
    }

    private function phoneDuplicateKey(string $value): string
    {
        $digits = $this->normalizeImportedPhone($value);
        return strlen($digits) >= 10 ? substr($digits, -10) : $digits;
    }

    public function show(string $id)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $lead = Lead::with(['leadSource', 'stage', 'assignedUser'])->findOrFail($id);
        $this->authorize('view', $lead);

        return response()->json([
            'success' => true,
            'message' => 'Lead retrieved successfully',
            'data' => $lead,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $lead = Lead::findOrFail($id);
        $this->authorize('update', $lead);
        $originalStatus = $lead->status;
        $this->mergeInternationalContactFields($request, $lead);
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:leads,email,' . $lead->id],
            'country_code' => ['required', 'regex:/^\+[0-9]{1,4}$/'],
            'phone' => ['required', 'regex:/^\+[0-9]{5,19}$/', 'unique:leads,phone,' . $lead->id],
            'whatsapp_country_code' => ['required_with:whatsapp', 'regex:/^\+[0-9]{1,4}$/'],
            'whatsapp' => ['nullable', 'regex:/^\+[0-9]{5,19}$/'],
            'address' => ['nullable', 'string'],
            'image' => ['nullable', 'mimes:jpg,jpeg,png,gif,bmp,webp,avif,svg', 'max:2048'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'sic_code' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'in:new,qualified,working,ready_to_close,won,lost'],
            'source' => ['nullable', 'string', 'max:255'],
            'lead_source_id' => ['nullable', 'exists:lead_sources,id'],
            'lead_stage_id' => ['nullable', 'exists:stages,id'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status_comment' => ['nullable', 'string', 'max:2000'],
        ], $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            $this->ensureAssignableUser($data['assigned_user_id'] ?? null, $user);
            if (Lead::supportsOwnedByUserColumn()) {
                $data['user_id'] = $this->resolveOwnedUserId(
                    $data['assigned_user_id'] ?? $lead->assigned_user_id,
                    $lead->created_by ?? $lead->user_id ?? $user->id
                );
            }

            if ($request->hasFile('image')) {
                if ($lead->image) {
                    Storage::disk('public')->delete($lead->image);
                }

                $data['image'] = $request->file('image')->store('leads', 'public');
            }

            $lead->update($data);

            if (
                (array_key_exists('status', $data) && $data['status'] !== $originalStatus)
                || filled($data['status_comment'] ?? null)
            ) {
                $historyEntry = $this->recordStatusHistory($lead, $data['status'] ?? $lead->status, $data['status_comment'] ?? null);
            }
            app(\App\Services\UserLogService::class)->updated($lead);

            if ($request->has('custom_fields')) {
                $lead->saveCustomFields($request->get('custom_fields'));
            }

            return response()->json([
                'success' => true,
                'message' => 'Lead updated successfully.',
                'data' => $lead->fresh(['leadSource', 'stage', 'assignedUser']),
                'history_entry' => $this->serializeHistoryEntry($historyEntry ?? null),
                'redirect' => route('leads.index', $lead),
            ]);
        } catch (\Throwable $e) {
            Log::error('Lead update failed', [
                'lead_id' => $lead->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to update lead right now.',
                'errors' => null,
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        $lead = Lead::findOrFail($id);
        $this->authorize('delete', $lead);

        if ($lead->image) {
            Storage::disk('public')->delete($lead->image);
        }

        app(\App\Services\UserLogService::class)->deleted($lead);
        $lead->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lead deleted successfully.',
        ]);
    }

    private function createAutoTaskForLead(Lead $lead): void
    {
        Task::create([
            'title' => 'Follow up lead',
            'description' => 'Auto task for new lead: ' . $lead->name,
            'related_type' => 'lead',
            'related_id' => $lead->id,
            'assigned_user_id' => $lead->assigned_user_id,
            'due_date' => Carbon::today()->addDays(1)->toDateString(),
            'status' => 'pending',
        ]);
    }

    private function ensureAssignableUser(?int $assignedUserId, $user): void
    {
        if (!$assignedUserId) {
            return;
        }

        if ($user->isAdmin()) {
            return;
        }

        abort_unless((int) $assignedUserId === (int) $user->id, 403, 'You can only assign records to yourself.');
    }

    private function recordStatusHistory(Lead $lead, ?string $status, ?string $comment): ?LeadStatusHistory
    {
        if (!$status && !filled($comment)) {
            return null;
        }

        try {
            return LeadStatusHistory::create([
                'lead_id' => $lead->id,
                'status' => $status ?? $lead->status,
                'comment' => filled($comment) ? $comment : null,
                'updated_by' => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Lead status history save skipped.', [
                'lead_id' => $lead->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function serializeHistoryEntry(?LeadStatusHistory $history): ?array
    {
        if (!$history) {
            return null;
        }

        return [
            'status_label' => match (strtolower((string) $history->status)) {
                'ready_to_close' => 'Ready to Close',
                'won' => 'Closed Won',
                'lost' => 'Closed Lost',
                default => $history->status ? ucwords(str_replace('_', ' ', $history->status)) : '--',
            },
            'comment' => $history->comment ?: '--',
            'updated_by' => $history->updater?->name ?? auth()->user()?->name ?? 'System',
            'created_at' => $history->created_at?->timezone('Asia/Kolkata')->format('d M Y h:i A') ?? now()->timezone('Asia/Kolkata')->format('d M Y h:i A'),
        ];
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        $this->authorize('update', $lead);

        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:new,qualified,working,ready_to_close,won,lost'],
            'status_comment' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided.',
                'errors' => $validator->errors()
            ], 422);
        }

        $status = $request->input('status');
        $comment = $request->input('status_comment');

        if ($lead->status !== $status) {
            $lead->status = $status;
            $lead->save();

            $this->recordStatusHistory($lead, $status, $comment ?: 'Status updated from inline edit');
            
            // Log user action if applicable
            if (class_exists(\App\Services\UserLogService::class)) {
                app(\App\Services\UserLogService::class)->updated($lead);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'status' => $status
        ], 200);
    }

    private function messages(): array
    {
        return [
            'image.mimes' => 'Please select a valid image! Allowed types: AVIF, WEBP, JPG, JPEG, PNG, GIF, BMP, SVG.',
            'image.max' => 'Please select an image smaller than 2MB!',
        ];
    }

    private function mergeInternationalContactFields(Request $request, ?Lead $lead = null): void
    {
        $countryCode = (string) $request->input('country_code', $lead?->country_code ?: '+91');
        $whatsappCountryCode = (string) $request->input('whatsapp_country_code', $lead?->whatsapp_country_code ?: '+91');
        $phone = preg_replace('/\D+/', '', (string) $request->input('phone', ''));
        $whatsapp = preg_replace('/\D+/', '', (string) $request->input('whatsapp', ''));

        $request->merge([
            'country_code' => $countryCode,
            'phone' => $phone !== '' ? $countryCode . $phone : null,
            'whatsapp_country_code' => $whatsappCountryCode,
            'whatsapp' => $whatsapp !== '' ? $whatsappCountryCode . $whatsapp : null,
        ]);
    }
}
