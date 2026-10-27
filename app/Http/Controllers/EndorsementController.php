<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Endorsement;
use App\Models\EndorsementPolicyLink;
use App\Models\EndorsementFieldChange;
use App\Models\FamilyGroup;
use App\Models\Customer;
use App\Models\Policy;
use App\Models\User;
use App\Models\LifeInsurance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class EndorsementController extends Controller
{
    public function index($tab = 'general')
    {
        $endorsementsQuery = Endorsement::where('endorsement_type', $tab);
        
        if (!auth()->user()->isAdmin()) {
            $endorsementsQuery->where('created_by', auth()->id());
        }

        $endorsements = $endorsementsQuery->orderBy('created_at', 'desc')->get();

        // Map user names for created_by and updated_by
        $userIds = $endorsements->pluck('created_by')->merge($endorsements->pluck('updated_by'))->unique()->filter();
        $userMap = User::whereIn('id', $userIds)->pluck('name', 'id')->toArray();

        // For simplicity in the view, we might need to join or load names
        // But for now, we'll pass the userMap as the original CI controller did
        
        // Joining data as done in CI
        $endorsementsDataQuery = DB::table('endorsements as e')
            ->select('e.*', 'fg.group_name as family_group_name', 'ph.name as policy_holder_name', 'p.policy_number')
            ->leftJoin('family_groups as fg', 'e.family_group_id', '=', 'fg.id')
            ->leftJoin('customers as ph', 'e.policy_holder_id', '=', 'ph.id')
            ->leftJoin('policies as p', 'e.policy_id', '=', 'p.id')
            ->where('e.endorsement_type', $tab);

        if (!auth()->user()->isAdmin()) {
            $endorsementsDataQuery->where('e.created_by', auth()->id());
        }

        $endorsementsData = $endorsementsDataQuery->orderBy('e.created_at', 'desc')
            ->get()
            ->map(function($item) {
                return (array)$item;
            })
            ->toArray();

        return view('endorsement-management.index', [
            'activeTab' => $tab,
            'endorsements' => $endorsementsData,
            'userMap' => $userMap
        ]);
    }

    public function life()
    {
        return $this->index('life');
    }

    public function client()
    {
        return $this->index('client');
    }

    public function create()
    {
        return view('endorsement-management.create');
    }

    public function createGeneral()
    {
        return view('endorsement-management.create-general');
    }

    public function createClient()
    {
        return view('endorsement-management.create-client');
    }

    public function getFamilyGroups()
    {
        $groups = FamilyGroup::select('id', 'group_name')->get();
        return response()->json($groups);
    }

    public function getHolders($groupId)
    {
        // Customers are linked to a family group via the policies table (policies.family_group_id)
        // We collect all distinct holder_ids from policies in this group,
        // plus the family group's own primary holder_id.
        $holderIdsFromPolicies = DB::table('policies')
            ->where('family_group_id', $groupId)
            ->whereNotNull('holder_id')
            ->distinct()
            ->pluck('holder_id');

        $primaryHolderId = DB::table('family_groups')
            ->where('id', $groupId)
            ->value('holder_id');

        $holderIds = $holderIdsFromPolicies->push($primaryHolderId)
            ->filter()
            ->unique()
            ->values();

        $holders = Customer::whereIn('id', $holderIds)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($holders);
    }

    public function getPolicies($holderId)
    {
        // holderId here refers to the selected customer (holder); filter policies by holder_id
        $policies = DB::table('policies as p')
            ->leftJoin('insurance_policy_types as it', 'p.policy_type_id', '=', 'it.id')
            ->where('p.holder_id', $holderId)
            ->whereNull('p.deleted_at')
            ->select('p.id', 'p.policy_number', 'it.name as type_name')
            ->get();
        return response()->json($policies);
    }

    public function getPolicyDetails($policyId)
    {
        $policy = DB::table('policies as p')
            ->select(
                'p.*',
                'it.name as policy_type_name',
                'ist.name as policy_sub_type_name',
                's.name as segment_name',
                'cm.name as insurance_company_name'
            )
            ->leftJoin('insurance_policy_types as it', 'p.policy_type_id', '=', 'it.id')
            ->leftJoin('insurance_policy_sub_types as ist', 'p.policy_sub_type_id', '=', 'ist.id')
            ->leftJoin('segments as s', 'p.segment_id', '=', 's.id')
            ->leftJoin('company_master as cm', 'p.company_id', '=', 'cm.id')
            ->where('p.id', $policyId)
            ->whereNull('p.deleted_at')
            ->first();

        if (!$policy) {
            return response()->json(['error' => 'Policy not found']);
        }

        // Get vehicle details if available (now mapped from the policies table directly)
        $vehicle = [
            'vehicle_registration_number' => $policy->vehicle_registration_number ?? null,
            'vehicle_model' => $policy->vehicle_model ?? null,
            'gross_vehicle_weight' => $policy->gross_vehicle_weight ?? null,
            'vehicle_idv' => $policy->vehicle_idv ?? null,
        ];

        return response()->json([
            // Division Name = Policy Type (no separate division column on policies)
            'division_name'       => $policy->policy_type_name,
            'segment_name'        => $policy->segment_name,
            // Optional Cover = Policy Sub Type
            'optional_cover_name' => $policy->policy_sub_type_name,
            'insurance_company'   => $policy->insurance_company_name,
            // Policy Mode stored as payment_mode on the table
            'policy_mode'         => $policy->payment_mode,
            'sum_assured'         => $policy->sum_assured,
            // Dates stored as start_date / end_date on the table
            'risk_start_date'     => $policy->start_date,
            'risk_end_date'       => $policy->end_date,
            'vehicle_details'     => $vehicle,
        ]);
    }

    public function getHolderDetails(Request $request)
    {
        $holderId = $request->input('holder_id');
        $holder = Customer::find($holderId);

        if (!$holder) {
            return response()->json(['status' => false, 'message' => 'Holder not found']);
        }

        // Try to get more specific details from the latest Life Insurance policy
        $latestLife = LifeInsurance::where('policy_holder_id', $holderId)
            ->latest()
            ->first();

        $firstName = $latestLife->first_name ?? '';
        $middleName = $latestLife->middle_name ?? '';
        $lastName = $latestLife->last_name ?? '';
        
        // Fallback: Split full name if first_name is empty
        if (empty($firstName) && !empty($holder->name)) {
            $nameParts = explode(' ', trim($holder->name));
            if (count($nameParts) == 1) {
                $firstName = $nameParts[0];
            } elseif (count($nameParts) == 2) {
                $firstName = $nameParts[0];
                $lastName = $nameParts[1];
            } else {
                $firstName = $nameParts[0];
                $lastName = $nameParts[count($nameParts) - 1];
                array_shift($nameParts);
                array_pop($nameParts);
                $middleName = implode(' ', $nameParts);
            }
        }

        return response()->json([
            'status' => true,
            'data' => [
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'dob' => $latestLife->dob ?? $holder->dob,
                'relation_with_head' => $latestLife->relation_with_head ?? '',
                'gender' => $latestLife->gender ?? '',
                'address_1' => $latestLife->address_line1 ?? $holder->address,
                'address_2' => $latestLife->address_line2 ?? '',
                'city' => $latestLife->city ?? '',
                'area' => $latestLife->area ?? '',
                'mobile_number' => $latestLife->mobile_number ?? $holder->phone,
                'whatsapp_number' => $holder->whatsapp,
                'email' => $latestLife->email_address ?? $holder->email,
                'aadhaar_card' => $holder->aadhaar_card ?? '',
                'pan_card' => $holder->pan_card ?? '',
                'gst_number' => $holder->gst_number ?? $holder->tax_number,
            ]
        ]);
    }

    public function saveGeneral(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_date' => 'required|date',
            'family_group_id' => 'required',
            'policy_holder_id' => 'required',
            'policy_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()]);
        }

        $endorsementLetterPath = null;
        if ($request->hasFile('endorsement_letter')) {
            $file = $request->file('endorsement_letter');
            $endorsementLetterPath = $file->store('endorsements', 'public');
        }

        $data = $request->all();
        $data['endorsement_type'] = 'general';
        $data['endorsement_letter'] = $endorsementLetterPath;
        $data['created_by'] = Auth::id();
        $data['status'] = 'pending';

        $endorsement = Endorsement::create($data);

        if ($endorsement) {
            return response()->json([
                'status' => true,
                'message' => 'General endorsement saved successfully',
                'endorsement_id' => $endorsement->id
            ]);
        }

        return response()->json(['status' => false, 'message' => 'Failed to save general endorsement']);
    }

    public function saveClient(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_date' => 'required|date',
            'family_group_id' => 'required',
            'policy_holder_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()]);
        }

        $endorsementLetterPath = null;
        if ($request->hasFile('endorsement_letter')) {
            $file = $request->file('endorsement_letter');
            $endorsementLetterPath = $file->store('endorsements', 'public');
        }

        $data = $request->all();
        $data['endorsement_type'] = 'client';
        $data['endorsement_letter'] = $endorsementLetterPath;
        $data['created_by'] = Auth::id();
        $data['status'] = 'pending';

        $endorsement = Endorsement::create($data);

        if ($endorsement) {
            return response()->json([
                'status' => true,
                'message' => 'Client endorsement saved successfully',
                'endorsement_id' => $endorsement->id
            ]);
        }

        return response()->json(['status' => false, 'message' => 'Failed to save client endorsement']);
    }

    public function view($id)
    {
        $endorsementQuery = DB::table('endorsements as e')
            ->select('e.*', 'fg.group_name as family_group_name', 'ph.name as policy_holder_name', 
                    'p.policy_number', 'u_c.name as created_by_name', 'u_u.name as updated_by_name')
            ->leftJoin('family_groups as fg', 'e.family_group_id', '=', 'fg.id')
            ->leftJoin('customers as ph', 'e.policy_holder_id', '=', 'ph.id')
            ->leftJoin('policies as p', 'e.policy_id', '=', 'p.id')
            ->leftJoin('users as u_c', 'e.created_by', '=', 'u_c.id')
            ->leftJoin('users as u_u', 'e.updated_by', '=', 'u_u.id')
            ->where('e.id', $id);

        if (!auth()->user()->isAdmin()) {
            $endorsementQuery->where('e.created_by', auth()->id());
        }

        $endorsement = $endorsementQuery->first();

        if (!$endorsement) {
            return Redirect::route('endorsement.index')->with('error', 'Endorsement not found');
        }

        return view('endorsement-management.view', [
            'endorsement' => (array)$endorsement
        ]);
    }
}
