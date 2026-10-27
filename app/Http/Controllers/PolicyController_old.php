<?php

namespace App\Http\Controllers;

use App\Models\Policy;
use App\Models\PolicyDocumentModel;
use App\Models\KycDocumentsModel;
use App\Models\User;
use App\Models\InsurancePolicyType;
use App\Models\InsurancePolicySubType;
use App\Models\Segment;
use App\Models\CompanyMaster;
use App\Models\Branch;
use App\Models\VehiclePolicyModel;
use App\Models\FamilyGroupModel;
use App\Models\FamilyGroupMemberModel;
use App\Models\CommissionEntriesModel;
use App\Models\CommissionRulesModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PolicyController extends Controller
{
    public function index()
    {
        $currentUser = Auth::user();
        $currentUserId = $currentUser->id;
        $currentUserRole = $currentUser->role;

        // Base query with relations
        $policiesQuery = Policy::with([
            'holder', 'user', 'policyType', 'policySubType', 
            'segment', 'company', 'branch'
        ]);

        // Role-based filtering
        if ($currentUserRole == 3) {
            // Admin: all policies
            $policies = $policiesQuery->orderBy('created_at', 'DESC')->get();
            $holders = User::where('role', 2)->where('created_by', $currentUserId)->get();
            $policyTypes = InsurancePolicyType::orderBy('created_at', 'DESC')->get();
        } elseif ($currentUserRole == 1) {
            // Agent: own policies
            $policies = $policiesQuery->where('user_id', $currentUserId)->orderBy('created_at', 'DESC')->get();
            $holders = User::where('role', 2)->where('created_by', $currentUserId)->get();
            $policyTypes = InsurancePolicyType::orderBy('created_at', 'DESC')->get();
        } else {
            $users = User::where('role', 2)->where('created_by', $currentUserId)->get();
            $holders = $users;
            $policyTypes = InsurancePolicyType::orderBy('created_at', 'DESC')->get();
            $userIds = $users->pluck('id')->toArray();
            $policies = !empty($userIds)
                ? $policiesQuery->whereIn('user_id', $userIds)->orderBy('created_at', 'DESC')->get()
                : collect([]);
        }

        // --- Prepare display-ready policies ---
        foreach ($policies as &$policy) {
            // Policy Holder Name
            $holder = $userModel->find($policy['holder_id']);
            $policy['policy_holder_name'] = $holder['name'] ?? null;

            // Staff Name & Role
            $user = $userModel->find($policy['user_id']);
            $policy['staff_name'] = $user['name'] ?? null;
            $policy['role'] = $user['role'] ?? null;

            // Policy Type Name
            $type = $insuranceTypeModel->find($policy['policy_type_id']);
            $policy['policy_type_name'] = $type['name'] ?? null;

            // Segment Name
            $segment = $segmentModel->find($policy['segment_id']);
            $policy['segment_name'] = $segment['name'] ?? null;

            // Company Name
            $company = $companyModel->find($policy['company_id']);
            $policy['company_name'] = $company['name'] ?? null;
        }

        $staffList = $userModel->where('role', 1)->findAll();
        $data['staffList'] = $staffList ?: [];
        $data['policyTypes'] = $policyTypes ?: [];

        $myPolicies = [];
        $staffPolicies = [];

        // Separate staff and my policies
        foreach ($policies as $policy1) {
            if ($policy1['role'] == 1) {
                // die('sad');
                $staffPolicies[] = $policy1;
            } else {
                // die('sad123');
                $myPolicies[] = $policy1;
            }
        }

        $filtered_myPolicies = array_filter($myPolicies, function ($policy) {
            return (
                (empty($policy['renew_id']) || is_null($policy['renew_id'])) &&
                (empty($policy['renew_status']) || is_null($policy['renew_status'])) &&
                isset($policy['is_renew']) && (int)$policy['is_renew'] === 0
            );
        });
        $filtered_myPolicies = array_values($filtered_myPolicies);

        // ✅ Renewed Policies: renew_id != null, renew_status != null, is_renew == 0
        $renewed_myPolicies = array_filter($myPolicies, function ($policy) {
            return (
                !empty($policy['renew_id']) &&
                !empty($policy['renew_status']) &&
                isset($policy['is_renew']) && (int)$policy['is_renew'] === 0
            );
        });
        $renewed_myPolicies = array_values($renewed_myPolicies);

        // ✅ Assign to view data
        $data['myPolicies'] = $filtered_myPolicies ?: [];
        $data['staffPolicies'] = $staffPolicies ?: [];
        $data['renewed_myPolicies'] = $renewed_myPolicies ?: [];
        $data['companies'] = $companyModel->findAll() ?: [];
        $data['holders'] = $holders ?: [];

        return view('Policy/index', $data);
    }




    public function create()
    {
        $session = session();
        $currentUserId = $session->get('id');
        $currentUserRole = $session->get('role');

        // Merge with BaseController data (includes notifications)
        $data = array_merge($this->data, []);

        $userModel = new UserModel();
        $policyTypeModel = new InsuranceTypeModel();
        $subTypeModel = new InsuranceSubTypeModel();
        $segmentModel = new SegmentModel();
        $companyModel = new InsuranceCompanyModel();
        $familyGroupModel = new FamilyGroupModel();

        if ($currentUserRole == 3) {
            // ✅ Admin: get both Staff (1) and Customers (2)
            $users = $userModel
                ->select('id, name, role')
                ->where('role', 2)
                ->findAll();

            $staffs = $userModel
                ->select('id, name, role')
                ->whereIn('role', [1, 3])
                ->findAll();

            $familyGroups = $familyGroupModel->findAll();
            $policyTypes = $policyTypeModel->findAll();
            $subTypes = $subTypeModel->findAll();
            $segments = $segmentModel->findAll();
            $companies = $companyModel->findAll();
        } elseif ($currentUserRole == 1) {
            // ✅ Staff: only their created customers
            $users = $userModel
                ->select('id, name, role')
                ->where('role', 2)
                ->where('created_by', $currentUserId)
                ->findAll();

            $staffs = $userModel
                ->select('id, name, role')
                ->where('id', $currentUserId)
                ->findAll();

            $familyGroups = $familyGroupModel->findAll();
            $policyTypes = $policyTypeModel->findAll();
            $subTypes = $subTypeModel->findAll();
            $segments = $segmentModel->findAll();
            $companies = $companyModel->findAll();
        } else {
            // Other roles → no access
            $users = [];
            $staffs = [];
            $familyGroups = [];
            $policyTypes = [];
            $subTypes = [];
            $segments = [];
            $companies = [];
        }

        $branches = [];

        $viewData = array_merge($this->data, [
            'users' => $users,
            'familyGroups' => $familyGroups,
            'staffs' => $staffs,
            'policyTypes' => $policyTypes,
            'subTypes' => $subTypes,
            'segments' => $segments,
            'companies' => $companies,
            'branches' => $branches
        ]);

        return view('Policy/create', $viewData);
    }

    public function store()
    {
        $session = session();
        $userId = $session->get('id');
        $userRole = $session->get('user_role');
        $request = service('request');
        $policyModel = new PolicyModel();
        $userModel = new UserModel();


        // Generate unique policy number
        $lastPolicy = $policyModel->orderBy('id', 'DESC')->first();
        if ($lastPolicy) {
            $lastNumber = (int) substr($lastPolicy['policy_number'], -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        $policyNumber = 'POL-' . date('Ymd') . '-' . $newNumber;

        // Main Policy Data
        $data = [
            'user_id'         => $request->getPost('staff_id') ?? $userId,
            'policy_number'   => $policyNumber,
            'holder_id'  => $request->getPost('user_id'),
            'policy_number_no'  => $request->getPost('policy_number'),
            'policy_type_id'  => $request->getPost('policy_type_id'),
            'family_group_id'  => $request->getPost('family_group_id'),
            'policy_type_name' => $request->getPost('policy_type_name'), // ✅ Save name too
            'sub_type_id'     => $request->getPost('sub_type_id'),
            'segment_id'      => $request->getPost('segment_id'),
            'company_id'      => $request->getPost('company_id'),
            'branch_id'       => $request->getPost('branch_id'),
            'policy_mode'     => $request->getPost('policy_mode'),
            'start_date'      => $request->getPost('start_date'),
            'end_date'        => $request->getPost('end_date'),
            'nominee_name'    => $request->getPost('nominee_name'),
            'nominee_contact' => $request->getPost('nominee_contact'),
            'sum_assured'     => $request->getPost('sum_assured'),
            'vehicle_IDV'     => $request->getPost('vehicle_idv'),
            'CNG_LPG'         => $request->getPost('cng_lpg_unit'),
            'premium'        => $request->getPost('premium'),
            'tax_amount'      => $request->getPost('tax_amount'),
            'payment_date'    => $request->getPost('payment_date'),
            'payment_mode'    => $request->getPost('payment_mode'),
            'amount'          => $request->getPost('amount'),
            'remarks'         => $request->getPost('remarks'),
            'created_at'      => date('Y-m-d H:i:s')
        ];


        try {
            // Insert Policy
            $policyId = $policyModel->insert($data);

            if ($userRole == 1) {
                $commissionRulesModel = new CommissionRulesModel();
                $commissionEntriesModel = new CommissionEntriesModel();

                $policy = $policyModel->find($policyId);

                // Fetch the commission rule for this policy type and staff
                $commissionRule = $commissionRulesModel
                    ->where('policy_type_id', $policy['policy_type_id']) // use actual policy type ID
                    // ->where('commission_for', $userId)
                    ->first();


                // Calculate commission amount
                $commissionAmount = null;
                if ($commissionRule) {
                    if ($commissionRule['commission_type'] == 'percentage') {
                        $commissionAmount = ($data['amount'] ?? 0) * ($commissionRule['commission_value'] / 100);
                    } elseif ($commissionRule['commission_type'] == 'fixed') {
                        $commissionAmount = $commissionRule['commission_value'];
                    }
                }

                $data = [
                    'policy_id' => $policyId,
                    'customer_id' => $policy['holder_id'] ?? null,
                    'staff_id' => $userId,
                    'commission_type' => $commissionRule['commission_type'] ?? null,
                    'commission_value' => $commissionRule['commission_value'] ?? null,
                    'commission_amount' => $commissionAmount,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $commissionEntriesModel->insert($data);
            }



            // ✅ Check if Vehicle Policy by NAME
            if (strtolower($request->getPost('policy_type_name')) === 'vehicle') {
                $vehicleModel = new VehiclePolicyModel();

                $vehicleData = [
                    'policy_id'           => $policyId,
                    'vehicle_reg_no'      => $request->getPost('vehicle_reg_no'),
                    'vehicle_model'       => $request->getPost('vehicle_model'),
                    'engine_no'           => $request->getPost('engine_no'),
                    'chassis_no'          => $request->getPost('chassis_no'),
                    'fuel_type'           => $request->getPost('fuel_type'),
                    'registration_date'   => $request->getPost('registration_date'),
                    'cc'                  => $request->getPost('cc'),
                    'gvw'                 => $request->getPost('gvw'),
                    'ncb'                 => $request->getPost('ncb'),
                    'year_of_manufacture' => $request->getPost('year_of_manufacture'),
                    'created_at'          => date('Y-m-d H:i:s')
                ];

                $vehicleModel->insert($vehicleData);
            }

            // ✅ Handle optional Policy PDF upload
            $pdfFile = $request->getFile('policy_pdf');
            if ($pdfFile && $pdfFile->isValid() && !$pdfFile->hasMoved()) {
                $uploadDir = FCPATH . 'uploads/policies/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $pdfName = $pdfFile->getRandomName();
                $pdfFile->move($uploadDir, $pdfName);
                $policyModel->update($policyId, ['document' => $pdfName]);
            }

            return $this->response->setJSON([
                'status'  => true,
                'message' => 'Policy saved successfully!',
                'policy_number' => $policyNumber
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status'  => false,
                'message' => 'Failed to save policy: ' . $e->getMessage()
            ]);
        }
    }


    public function edit($id)
    {
        $session = session();
        $currentUserId = $session->get('id');
        $currentUserRole = $session->get('role');

        // Merge with BaseController data (includes notifications)
        $data = array_merge($this->data, []);

        $policyModel = new PolicyModel();
        $policy = $policyModel->find($id);
        // print_r($policy);die;
        if (!$policy) {
            return redirect()->back()->with('error', 'Policy not found.');
        }

        // --- Users and Policy Types based on role ---
        $userModel = new UserModel();
        $policyTypeModel = new InsuranceTypeModel();
        $subTypeModel = new InsuranceSubTypeModel();
        $segmentModel = new SegmentModel();
        $companyModel = new InsuranceCompanyModel();
        $familyGroupModel = new FamilyGroupModel();

        if ($currentUserRole == 3) {
            // Role 3: all users with role=2, all types/subtypes/segments/companies
            $users = $userModel
                ->select('id, name, role,email')
                ->where('role', 2)
                ->findAll();

            $staffs = $userModel
                ->select('id, name, role')
                ->whereIn('role', [1, 3])
                ->findAll();
            $familyGroups = $familyGroupModel->findAll();
            $policyTypes = $policyTypeModel->findAll();
            $subTypes = $subTypeModel->findAll();
            $segments = $segmentModel->findAll();
            $companies = $companyModel->findAll();
        } elseif ($currentUserRole == 1) {
            // Role 1: only users created by current user + related types/subtypes/segments
            $users = $userModel
                ->select('id, name, role,email')
                ->findAll();

            $staffs = $userModel
                ->select('id, name, role')
                ->where('id', $currentUserId)
                ->findAll();

            $familyGroups = $familyGroupModel->findAll();
            $policyTypes = $policyTypeModel->findAll();
            $subTypes = $subTypeModel->findAll();
            $segments = $segmentModel->findAll();
            $companies = $companyModel->findAll(); // usually all
        } else {
            $users = [];
            $staffs = [];
            $familyGroups = [];
            $policyTypes = [];
            $subTypes = [];
            $segments = [];
            $companies = [];
        }

        // --- Fetch branches for selected company ---
        $branchModel = new BranchModel();
        $branches = $branchModel->where('company_id', $policy['company_id'])->findAll();

        // --- Fetch Family Groups for selected user ---
        $familyGroupModel = new FamilyGroupModel();
        $familyGroups = [];
        if (!empty($policy)) {
            $familyGroups = $familyGroupModel->findAll();
        }

        // ✅ Vehicle Details
        $vehicleModel = new VehiclePolicyModel();
        $vehicleDetails = $vehicleModel->first();
        // echo "<pre>";
        // print_r($policy);die;
        $viewData = array_merge($this->data, [
            'policy' => $policy,
            'users' => $users,
            'staffs' => $staffs,
            'familyGroups' => $familyGroups,
            'policyTypes' => $policyTypes,
            'subTypes' => $subTypes,
            'segments' => $segments,
            'companies' => $companies,
            'branches' => $branches,
            'familyGroups' => $familyGroups,
            'vehicle' => $vehicleDetails
        ]);
        return view('Policy/edit', $viewData);
    }


    public function update()
    {
        $policyModel = new PolicyModel();
        $request = service('request');

        $id = $request->getPost('id');

        // Check if policy exists
        $policy = $policyModel->find($id);
        if (!$policy) {
            return $this->response->setJSON([
                'status'  => false,
                'message' => 'Policy not found.'
            ]);
        }

        $data = [
            'user_id'        =>  $request->getPost('staff_id'),
            'holder_id'        =>  $request->getPost('user_id'),
            'policy_number_no'        =>  $request->getPost('policy_number'),
            'family_group_id' => $request->getPost('family_group_id'),
            'policy_type_id' => $request->getPost('policy_type_id'),
            'sub_type_id'    => $request->getPost('sub_type_id'),
            'segment_id'     => $request->getPost('segment_id'),
            'company_id'     => $request->getPost('company_id'),
            'branch_id'      => $request->getPost('branch_id'),

            'policy_mode'    => $request->getPost('policy_mode'),
            'start_date'     => $request->getPost('start_date'),
            'end_date'       => $request->getPost('end_date'),
            'nominee_name'   => $request->getPost('nominee_name'),
            'nominee_contact' => $request->getPost('nominee_contact'),

            'sum_assured'    => $request->getPost('sum_assured'),
            'vehicle_IDV'    => $request->getPost('vehicle_idv'),
            'CNG_LPG'        => $request->getPost('cng_lpg_unit'),
            'premium'        => $request->getPost('premium'),
            'tax_amount'     => $request->getPost('tax_amount'),
            'payment_date'   => $request->getPost('payment_date'),
            'payment_mode'   => $request->getPost('payment_mode'),
            'amount'         => $request->getPost('amount'),
            'remarks'        => $request->getPost('remarks'),
            'updated_at'     => date('Y-m-d H:i:s')
        ];

        if ($policyModel->update($id, $data)) {
            return $this->response->setJSON([
                'status'  => true,
                'message' => 'Policy updated successfully.',
                'data'    => $data
            ]);
        }

        return $this->response->setJSON([
            'status'  => false,
            'message' => 'Failed to update policy.'
        ]);
    }

    public function delete($id)
    {
        $policyModel = new PolicyModel();
        $policy = $policyModel->find($id);

        if (!$policy) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Policy not found.'
            ]);
        }

        if ($policyModel->delete($id)) {
            return $this->response->setJSON([
                'status' => true,
                'message' => 'Policy deleted successfully.'
            ]);
        }

        return $this->response->setJSON([
            'status' => false,
            'message' => 'Failed to delete policy.'
        ]);
    }
    public function view($id)
    {
        // Merge with BaseController data (includes notifications)
        $data = array_merge($this->data, []);

        $policyModel = new PolicyModel();
        $policy = $policyModel->find($id);

        if (!$policy) {
            return redirect()->back()->with('error', 'Policy not found.');
        }

        $userModel = new UserModel();
        $users = $userModel->where('id', $policy['user_id'])->first();
        $policy_holder = $userModel->where('id', $policy['holder_id'])->first();

        $policyTypeModel = new InsuranceTypeModel();
        $policyTypes = $policyTypeModel->where('id', $policy['policy_type_id'])->first();

        $subTypeModel = new InsuranceSubTypeModel();
        $subTypes = $subTypeModel->where('id', $policy['sub_type_id'])->first();

        $segmentModel = new SegmentModel();
        $segments = $segmentModel->where('id', $policy['segment_id'])->first();

        $companyModel = new InsuranceCompanyModel();
        $companies = $companyModel->where('id', $policy['company_id'])->first();

        $branchModel = new BranchModel();
        $branches = $branchModel->where('id', $policy['branch_id'])->first();

        // ✅ Vehicle Details
        $vehicleModel = new VehiclePolicyModel();
        $vehicleDetails = $vehicleModel->where('policy_id', $policy['id'])->first();

        $viewData = array_merge($this->data, [
            'policy' => $policy,
            'users' => $users,
            'policyTypes' => $policyTypes,
            'subTypes' => $subTypes,
            'segments' => $segments,
            'companies' => $companies,
            'branches' => $branches,
            'vehicleDetails' => $vehicleDetails,
            'policy_holder' => $policy_holder
        ]);
        return view('Policy/view', $viewData);
    }
    public function renew($id)
    {
        // Merge with BaseController data (includes notifications)
        $data = array_merge($this->data, []);

        $policyModel = new PolicyModel();
        $policy = $policyModel->find($id);

        if (!$policy) {
            return redirect()->back()->with('error', 'Policy not found.');
        }

        $userModel = new UserModel();
        $users = $userModel->findAll();
        $staffs = $userModel
            ->select('id, name, role,email')
            ->whereIn('role', [1, 3])
            ->findAll();
        $policyTypeModel = new InsuranceTypeModel();
        $policyTypes = $policyTypeModel->findAll();

        $subTypeModel = new InsuranceSubTypeModel();
        $subTypes = $subTypeModel->findAll();

        $segmentModel = new SegmentModel();
        $segments = $segmentModel->findAll();

        $companyModel = new InsuranceCompanyModel();
        $companies = $companyModel->findAll();

        // Fetch branches **of the selected company only**
        $branchModel = new BranchModel();
        $branches = $branchModel->where('company_id', $policy['company_id'])->findAll();

        $vehicleModel = new VehiclePolicyModel();
        $vehicleDetails = $vehicleModel->where('policy_id', $policy['id'])->first();

        $familyGroupModel = new FamilyGroupModel();
        $familyGroups = $familyGroupModel->findAll();

        $viewData = array_merge($this->data, [
            'policy'        => $policy,
            'familyGroups'  => $familyGroups,
            'users'         => $users,
            'staffs'        => $staffs,
            'policyTypes'   => $policyTypes,
            'subTypes'      => $subTypes,
            'segments'      => $segments,
            'companies'     => $companies,
            'branches'      => $branches,
            'vehicle'       => $vehicleDetails
        ]);
        return view('Policy/renew', $viewData);
    }

    public function renew_policy($id)
    {
        $session = session();
        $userId = $session->get('id');
        $policyModel = new PolicyModel();

        $policy = $policyModel
            ->where('id', $id)
            ->first();

        if (!$policy) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Policy not found or not renewable.'
            ]);
        }

        $policyModel->update($id, ['is_renew' => 1]);


        // Generate unique policy number
        $lastPolicy = $policyModel->orderBy('id', 'DESC')->first();
        if ($lastPolicy) {
            $lastNumber = (int) substr($lastPolicy['policy_number'], -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        $policyNumber = 'POL-' . date('Ymd') . '-' . $newNumber;



        $request = service('request');
        $newPolicyData['user_id']     = $request->getPost('staff_id') ?? $userId;
        $newPolicyData['policy_number']     = $policyNumber;
        $newPolicyData['holder_id']     = $request->getPost('user_id');
        $newPolicyData['policy_number_no']     = $request->getPost('policy_number');
        $newPolicyData['family_group_id']     = $request->getPost('family_group_id');
        $newPolicyData['policy_type_id']     = $request->getPost('policy_type_id');
        $newPolicyData['sub_type_id']     = $request->getPost('sub_type_id');
        $newPolicyData['sub_type_id']     = $request->getPost('sub_type_id');
        $newPolicyData['segment_id']     = $request->getPost('segment_id');
        $newPolicyData['company_id']     = $request->getPost('company_id');
        $newPolicyData['branch_id']     = $request->getPost('branch_id');
        $newPolicyData['nominee_name']     = $request->getPost('nominee_name');
        $newPolicyData['nominee_contact']     = $request->getPost('nominee_contact');
        $newPolicyData['start_date']     = $request->getPost('start_date');
        $newPolicyData['end_date']       = $request->getPost('end_date');
        $newPolicyData['policy_mode']       = $request->getPost('policy_mode');
        $newPolicyData['sum_assured']       = $request->getPost('sum_assured');
        $newPolicyData['premium']       = $request->getPost('premium');
        $newPolicyData['tax_amount']       = $request->getPost('tax_amount');
        $newPolicyData['payment_date']   = $request->getPost('payment_date');
        $newPolicyData['payment_mode'] = $request->getPost('payment_mode');
        $newPolicyData['amount'] = $request->getPost('amount');
        $newPolicyData['status']         = 'active';
        $newPolicyData['renew_status']   = 'renewed';
        $newPolicyData['renew_id']   = $id;


        // 4. Insert new policy
        $newPolicyId = $policyModel->insert($newPolicyData);

        if ($newPolicyId) {
            return $this->response->setJSON([
                'status'       => 'success',
                'message'      => 'Policy renewed successfully.',
                'new_policy_id' => $newPolicyId,
                'data'         => $newPolicyData
            ]);
        } else {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Failed to renew policy.'
            ]);
        }
    }
    public function deletePolicyDocument()
    {
        $request = service('request');
        $policyId = $request->getPost('policy_id');

        $policyModel = new PolicyModel();
        $policy = $policyModel->find($policyId);

        if (!$policy || empty($policy['document'])) {
            return json_encode([
                'status' => 'error',
                'message' => 'No document to delete'
            ]);
        }

        $filePath = FCPATH . 'uploads/policies/' . $policy['document'];

        // Delete file from server
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Update DB — remove document name
        $policyModel->update($policyId, ['document' => null]);

        return json_encode([
            'status' => 'success',
            'message' => 'Document deleted successfully'
        ]);
    }


    public function viewPolicyDocument($id)
    {
        $policyModel = new PolicyModel();
        $policy = $policyModel->find($id);

        if (!$policy || empty($policy['document'])) {
            return $this->response->setStatusCode(404)->setBody('Document not found');
        }

        $filePath = FCPATH . 'uploads/policies/' . $policy['document'];

        if (!file_exists($filePath)) {
            return $this->response->setStatusCode(404)->setBody('File missing');
        }

        // Detect MIME
        $mimeType = mime_content_type($filePath);

        // For IMAGE: Return actual file URL using base_url
        if (strpos($mimeType, 'image/') === 0) {
            $url = base_url('uploads/policies/' . $policy['document']);
            return redirect()->to($url);
        }

        // For PDF, audio, video → serve inline
        return $this->response
            ->setHeader('Content-Type', $mimeType)
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($filePath) . '"')
            ->setBody(file_get_contents($filePath));
    }






    public function getPolicyDocuments()
    {
        $request = service('request');
        $policy_id = $request->getGet('policy_id');

        $policyModel = new PolicyModel();
        $policy = $policyModel->find($policy_id); // Only one policy

        if ($policy) {
            return $this->response->setJSON([
                'status' => 'success',
                'document' => $policy['document'] ?? null,
                'name' => !empty($policy['document']) ? basename($policy['document']) : null
            ]);
        } else {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Policy not found'
            ]);
        }
    }



    // Upload policy document
    public function uploadPolicyDocument()
    {
        $request = service('request');
        $policy_id = $request->getPost('policy_id');
        $file = $request->getFile('document');

        // ❌ If no file uploaded
        if (!$file || $file->getError() == UPLOAD_ERR_NO_FILE) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Please upload a document.'
            ]);
        }

        // ❌ If invalid file or upload issue
        if (!$file->isValid() || $file->hasMoved()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid file or upload error.'
            ]);
        }

        // ✅ Upload logic
        $uploadDir = FCPATH . 'uploads/policies/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newName = $file->getRandomName();
        $file->move($uploadDir, $newName);

        // Update database
        $policyModel = new PolicyModel();
        $policyModel->update($policy_id, [
            'document' => $newName
        ]);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function download_policy_invoice($id)
    {
        // Check if GD extension is available (required for image processing in PDF)
        if (!extension_loaded('gd')) {
            log_message('error', 'PHP GD extension is not installed. PDF generation with images requires GD extension.');
            return redirect()->back()->with('error', 'PDF generation failed: PHP GD extension is required but not installed. Please contact your system administrator to enable the GD extension in PHP.');
        }

        $session = session();
        $userId = $session->get('id');
        $policyModel = new PolicyModel();
        $policy = $policyModel->find($id);

        if (!$policy) {
            return redirect()->back()->with('error', 'Policy not found.');
        }

        // Fetch related data
        $users        = (new UserModel())->find($policy['holder_id']);
        $company_details        = (new UserModel())->where('role', 3)->first();
        $policyTypes  = (new InsuranceTypeModel())->find($policy['policy_type_id']);
        $subTypes     = (new InsuranceSubTypeModel())->find($policy['sub_type_id']);
        $segments     = (new SegmentModel())->find($policy['segment_id']);
        $companies    = (new InsuranceCompanyModel())->find($policy['company_id']);
        $branches     = (new BranchModel())->find($policy['branch_id']);
        $vehicle      = (new VehiclePolicyModel())->where('policy_id', $policy['id'])->first();
        $members      = (new FamilyGroupMemberModel())->where('user_id', $userId)->findAll();
        
        // Fetch family group if family_group_id exists
        $familyGroup = null;
        if (!empty($policy['family_group_id'])) {
            $familyGroup = (new FamilyGroupModel())->find($policy['family_group_id']);
        }
        
        // print_r($company_details);die;
        // Generate HTML from view
        $html = view('Policy/policy_invoice_pdf', [
            'policy'      => $policy,
            'users'       => $users,
            'company_details'       => $company_details,
            'policyTypes' => $policyTypes,
            'subTypes'    => $subTypes,
            'segments'    => $segments,
            'companies'   => $companies,
            'branches'    => $branches,
            'vehicle'     => $vehicle,
            'members'     => $members,
            'familyGroup' => $familyGroup,
        ]);

        // Debug (optional)
        // file_put_contents(WRITEPATH . 'debug_policy_invoice.html', $html);

        // Clear buffer
        if (ob_get_length()) {
            ob_end_clean();
        }

        // ✅ Initialize Dompdf with remote enabled
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);

        try {
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // Stream the PDF
            $dompdf->stream('policy_invoice_' . $policy['policy_number'] . '.pdf', [
                'Attachment' => false
            ]);

            exit;
        } catch (\Exception $e) {
            log_message('error', 'PDF generation error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to generate PDF. Error: ' . $e->getMessage());
        }
    }

    public function exportStaffPolicies()
    {
        $request = service('request');
        $from_date = $request->getGet('from_date');
        $to_date = $request->getGet('to_date');
        $staff_id = $request->getGet('staff_id');
        $year = $request->getGet('year');

        $policyModel = new PolicyModel();
        $userModel = new UserModel();
        $insuranceTypeModel = new InsuranceTypeModel();
        $companyModel = new InsuranceCompanyModel();

        // 🔹 Fetch staff only (role = 1)
        $staffBuilder = $userModel->where('role', 1);
        if (!empty($staff_id)) {
            $staffBuilder->where('id', $staff_id);
        }
        $staffList = $staffBuilder->findAll();
        $staffIds = array_column($staffList, 'id');

        if (empty($staffIds)) {
            // No staff found, return empty CSV
            $filename = 'staff_policies_export_' . date('Ymd_His') . '.csv';
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Sr No.', 'Policy Holder', 'Policy Number', 'Policy Type', 'Company', 'Start Date', 'End Date', 'Premium', 'Status']);
            fclose($output);
            exit();
        }

        // 🔹 Fetch policies for selected staff
        $builder = $policyModel->builder();
        $builder->whereIn('user_id', $staffIds);

        if ($year) {
            $builder->where('YEAR(created_at)', $year);
        }
        if ($from_date) {
            $builder->where('DATE(start_date) >=', date('Y-m-d', strtotime($from_date)));
        }
        if ($to_date) {
            $builder->where('DATE(end_date) <=', date('Y-m-d', strtotime($to_date)));
        }

        $policies = $builder->get()->getResultArray();

        // 🔹 CSV Export
        $filename = 'staff_policies_export_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // CSV Headers
        fputcsv($output, ['Sr No.', 'Staff Name', 'Policy Holder', 'Policy Number', 'Policy Type', 'Company', 'Start Date', 'End Date', 'Premium', 'Status']);


        $i = 1;
        foreach ($policies as $policy) {
            $holder = $userModel->find($policy['holder_id']);
            $staff = $userModel->find($policy['user_id']); // Fetch staff who created policy
            $policyType = $insuranceTypeModel->find($policy['policy_type_id']);
            $company = $companyModel->find($policy['company_id']);

            fputcsv($output, [
                $i++,                              // Sr No.
                $staff['name'] ?? '',              // Staff Name (Created By)
                $holder['name'] ?? '',             // Policy Holder Name
                $policy['policy_number'],          // Policy Number
                $policyType['name'] ?? '',         // Policy Type
                $company['name'] ?? '',            // Company Name
                $policy['start_date'],             // Start Date
                $policy['end_date'],               // End Date
                $policy['premium'],                // Premium
                $policy['status']                  // Status
            ]);
        }


        fclose($output);
        exit();
    }

    public function getExpiringPoliciesAjax()
    {
        $policyModel = new \App\Models\PolicyModel();
        $policies = $policyModel->getExpiringPolicies(); // model function we created earlier

        if (empty($policies)) {
            return $this->response->setJSON([
                "data" => [],
                "status" => false,
                "message" => "No expiring policies found."
            ]);
        }
        return $this->response->setJSON([
            "data" => $policies,
            "status" => true,
            "message" => "Expiring policies fetched successfully."
        ]);
    }

    public function getUpcomingFollowupsAjax()
    {
        $followUpModel = new FollowUp_Model();
        $followups = $followUpModel->getUpcomingFollowups();

        if (empty($followups)) {
            return $this->response->setJSON([
                "data" => [],
                "status" => false,
                "message" => "No upcoming follow-ups found."
            ]);
        }
        return $this->response->setJSON([
            "data" => $followups,
            "status" => true,
            "message" => "Upcoming follow-ups fetched successfully."
        ]);
    }

    public function getUpcomingMeetingsAjax()
    {
        $meetingModel = new MeetingModel();
        $meetings = $meetingModel->getUpcomingMeetings();

        if (empty($meetings)) {
            return $this->response->setJSON([
                "data" => [],
                "status" => false,
                "message" => "No upcoming meetings found."
            ]);
        }
        return $this->response->setJSON([
            "data" => $meetings,
            "status" => true,
            "message" => "Upcoming meetings fetched successfully."
        ]);
    }

    public function renewList()
    {
        $data = array_merge($this->data, []);
        return view('renew/index', $data);
    }

    public function renewListAjax()
    {
        $session = session();
        $currentUserId = $session->get('id');
        $currentUserRole = $session->get('role');

        $policyModel = new PolicyModel();
        $userModel = new UserModel();
        $insuranceTypeModel = new InsuranceTypeModel();
        $companyModel = new InsuranceCompanyModel();
        $branchModel = new BranchModel();

        // Get policies that need renewal (expiring within next 30 days and not yet renewed)
        $today = date('Y-m-d');
        $next30Days = date('Y-m-d', strtotime('+30 days', strtotime($today)));

        // Build base query - simplified to ensure policies show up
        $query = $policyModel
            ->select('policies.*, user_master.name as holder_name, insurance_companies.name as company_name, branches.name as branch_name, insurance_types.name as policy_type_name')
            ->join('user_master', 'user_master.id = policies.holder_id', 'left')
            ->join('insurance_companies', 'insurance_companies.id = policies.company_id', 'left')
            ->join('branches', 'branches.id = policies.branch_id', 'left')
            ->join('insurance_types', 'insurance_types.id = policies.policy_type_id', 'left')
            ->where('policies.end_date IS NOT NULL', null, false)
            ->where("policies.end_date >= '$today'", null, false)
            ->where("policies.end_date <= '$next30Days'", null, false);

        // Only exclude policies that are explicitly marked as renewed (is_renew = 1)
        // Include all other policies (is_renew = 0, NULL, or not set)
        // Using a simpler approach - just exclude is_renew = 1
        $query->where("(policies.is_renew IS NULL OR policies.is_renew = 0 OR policies.is_renew != 1)", null, false);
        
        // Also exclude if renew_id is set (not null and not 0)
        $query->where("(policies.renew_id IS NULL OR policies.renew_id = 0)", null, false);

        // Filter by user role (only for non-admin users)
        if ($currentUserRole != 3) {
            $query->where('policies.user_id', $currentUserId);
        }

        // Debug: Get the compiled SQL query
        // $sql = $query->getCompiledSelect(false);
        // log_message('debug', 'Renew Query: ' . $sql);
        
        $policies = $query->orderBy('policies.end_date', 'ASC')->findAll();
        
        // Debug: Log query details for troubleshooting
        log_message('debug', 'Renew List Query - Today: ' . $today . ', Next30Days: ' . $next30Days . ', Policies Found: ' . count($policies) . ', User Role: ' . $currentUserRole . ', User ID: ' . $currentUserId);
        
        // If no policies found, try a test query without renew filters to see what's available
        if (count($policies) == 0) {
            $testQuery = $policyModel
                ->select('policies.id, policies.end_date, policies.is_renew, policies.renew_id, policies.user_id, policies.policy_number')
                ->where('policies.end_date IS NOT NULL', null, false)
                ->where("policies.end_date >= '$today'", null, false)
                ->where("policies.end_date <= '$next30Days'", null, false);
            
            if ($currentUserRole != 3) {
                $testQuery->where('policies.user_id', $currentUserId);
            }
            
            $testPolicies = $testQuery->limit(5)->findAll();
            log_message('debug', 'Test Query (without renew filters) - Found: ' . count($testPolicies) . ' policies');
            if (count($testPolicies) > 0) {
                foreach ($testPolicies as $testPolicy) {
                    log_message('debug', 'Policy ID: ' . $testPolicy['id'] . ', End Date: ' . $testPolicy['end_date'] . ', is_renew: ' . ($testPolicy['is_renew'] ?? 'NULL') . ', renew_id: ' . ($testPolicy['renew_id'] ?? 'NULL') . ', user_id: ' . $testPolicy['user_id']);
                }
            }
        }

        $data = [];
        foreach ($policies as $key => $policy) {
            // Calculate days remaining
            $diffDays = 0;
            if (!empty($policy['end_date'])) {
                try {
                    $endDate = new \DateTime($policy['end_date']);
                    $todayDate = new \DateTime($today);
                    $diff = $todayDate->diff($endDate);
                    $diffDays = (int)$diff->format('%r%a'); // %r gives sign, %a gives absolute days
                    if ($diffDays < 0) {
                        $diffDays = 0; // Already expired
                    }
                } catch (\Exception $e) {
                    $diffDays = 0;
                }
            }
            
            $data[] = [
                'id' => $policy['id'],
                'sr_no' => $key + 1,
                'policy_number' => $policy['policy_number'] ?? '--',
                'policy_number_no' => $policy['policy_number_no'] ?? '--',
                'holder_name' => $policy['holder_name'] ?? '--',
                'company_name' => $policy['company_name'] ?? '--',
                'branch_name' => $policy['branch_name'] ?? '--',
                'policy_type_name' => $policy['policy_type_name'] ?? '--',
                'policy_mode' => $policy['policy_mode'] ?? '--',
                'sum_assured' => $policy['sum_assured'] ?? '0',
                'premium' => $policy['premium'] ?? '0',
                'status' => $policy['status'] ?? 'Inforce',
                'start_date' => $policy['start_date'] ?? '--',
                'end_date' => $policy['end_date'] ?? '--',
                'days_remaining' => $diffDays,
                'created_at' => $policy['created_at'] ?? '--'
            ];
        }

        return $this->response->setJSON([
            'status' => true,
            'data' => $data,
            'debug_info' => [
                'today' => $today,
                'next30Days' => $next30Days,
                'total_found' => count($policies),
                'records_returned' => count($data),
                'user_role' => $currentUserRole,
                'user_id' => $currentUserId
            ]
        ]);
    }

    public function exportPolicies()
    {
        $request = service('request');
        $from_date = $request->getGet('from_date');
        $to_date = $request->getGet('to_date');
        $year = $request->getGet('year');
        $staff_id = $request->getGet('staff_id');

        $policyModel = new PolicyModel();
        $userModel = new UserModel();
        $insuranceTypeModel = new InsuranceTypeModel();
        $companyModel = new InsuranceCompanyModel();

        // 🔹 Fetch all policies
        $builder = $policyModel->builder();

        if (!empty($staff_id)) {
            $builder->where('user_id', $staff_id);
        }

        if ($year) {
            $builder->where('YEAR(start_date)', $year);
        }
        if ($from_date) {
            $builder->where('DATE(start_date) >=', date('Y-m-d', strtotime($from_date)));
        }
        if ($to_date) {
            $builder->where('DATE(end_date) <=', date('Y-m-d', strtotime($to_date)));
        }

        $policies = $builder->get()->getResultArray();

        // 🔹 CSV Export
        $filename = 'policies_export_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // CSV Headers
        fputcsv($output, ['Sr No.', 'Policy Number', 'Policy Type', 'Sum Assured', 'Premium', 'Start Date', 'End Date', 'Status']);

        $i = 1;
        foreach ($policies as $policy) {
            $policyType = $insuranceTypeModel->find($policy['policy_type_id']);

            fputcsv($output, [
                $i++,                              // Sr No.
                $policy['policy_number'],          // Policy Number
                $policyType['name'] ?? '',         // Policy Type
                $policy['sum_assured'],            // Sum Assured
                $policy['premium'],                // Premium
                $policy['start_date'],             // Start Date
                $policy['end_date'],               // End Date
                $policy['status']                  // Status
            ]);
        }

        fclose($output);
        exit();
    }


    // public function submit()
    // {
    //     $request = service('request');
    //     $uploadPath = FCPATH . 'uploads/kyc/';

    //     // Ensure upload directory exists
    //     if (!is_dir($uploadPath)) {
    //         mkdir($uploadPath, 0755, true);
    //     }

    //     $files = $request->getFiles();
    //     $errors = [];

    //     $allowedDocTypes = ['jpg', 'jpeg', 'png', 'pdf', 'gif', 'jfif'];
    //     $allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif', 'jfif'];

    //     // Helper for multiple files
    //     $saveMultipleFiles = function ($fileArray, $fieldName, $min = 0, $max = 2) use ($uploadPath, $allowedDocTypes, &$errors) {
    //         $saved = [];

    //         if (!$fileArray) return null; // optional

    //         if (!is_array($fileArray)) $fileArray = [$fileArray];

    //         $count = count($fileArray);
    //         if ($min > 0 && ($count < $min || $count > $max)) {
    //             $errors[$fieldName] = "Please upload $min to $max files for $fieldName.";
    //             return null;
    //         }

    //         foreach ($fileArray as $file) {
    //             if ($file && $file->isValid() && !$file->hasMoved()) {
    //                 $ext = strtolower($file->getClientExtension());
    //                 if (!in_array($ext, $allowedDocTypes)) {
    //                     $errors[$fieldName] = "Invalid file type for $fieldName. Allowed types: " . implode(', ', $allowedDocTypes);
    //                     continue;
    //                 }
    //                 $newName = $file->getRandomName();
    //                 $file->move($uploadPath, $newName);
    //                 $saved[] = $newName;
    //             }
    //         }

    //         return !empty($saved) ? json_encode($saved) : null;
    //     };

    //     // Helper for single file
    //     $saveSingleFile = function ($fileArray, $fieldName) use ($uploadPath, $allowedImageTypes, &$errors) {
    //         if (!$fileArray) return null; // optional

    //         $file = is_array($fileArray) ? $fileArray[0] : $fileArray;

    //         if ($file && $file->isValid() && !$file->hasMoved()) {
    //             $ext = strtolower($file->getClientExtension());
    //             if (!in_array($ext, $allowedImageTypes)) {
    //                 $errors[$fieldName] = "Invalid file type for $fieldName. Allowed types: " . implode(', ', $allowedImageTypes);
    //                 return null;
    //             }
    //             $newName = $file->getRandomName();
    //             $file->move($uploadPath, $newName);
    //             return $newName;
    //         }

    //         return null; // optional
    //     };

    //     $session = session();
    //     $currentUserId = $session->get('id');
    //     $policyId = $request->getPost('policy_id');

    //     // Prepare data
    //     $data = [
    //         'user_id' => $currentUserId,
    //         'policy_id' => $policyId,
    //         'aadhaar_card' => $saveMultipleFiles($files['aadhaar_card'] ?? null, 'aadhaar_card', 1, 2),
    //         'pan_card' => $saveMultipleFiles($files['pan_card'] ?? null, 'pan_card', 1, 1),
    //         'driving_licence' => $saveMultipleFiles($files['driving_licence'] ?? null, 'driving_licence', 1, 1),
    //         'addr_aadhaar' => $saveMultipleFiles($files['addr_aadhaar'] ?? null, 'addr_aadhaar', 1, 1),
    //         'addr_driving_licence' => $saveMultipleFiles($files['addr_driving_licence'] ?? null, 'addr_driving_licence', 1, 1),
    //         'photo' => $saveSingleFile($files['photo'] ?? null, 'photo'),
    //         'signature' => $saveSingleFile($files['signature'] ?? null, 'signature'),
    //         'kyc_status' => 'pending',
    //     ];

    //     // Return errors if any
    //     if (!empty($errors)) {
    //         return $this->response->setStatusCode(422)->setJSON([
    //             'status' => 'error',
    //             'errors' => $errors
    //         ]);
    //     }

    //     $kycModel = new \App\Models\KycDocumentsModel();

    //     try {
    //         // Check if policy already exists
    //         $existing = $kycModel->where('user_id', $currentUserId)
    //             ->where('policy_id', $policyId)
    //             ->first();

    //         if ($existing) {
    //             // Update
    //             $kycModel->update($existing['id'], $data);
    //             $message = 'KYC updated successfully';
    //         } else {
    //             // Insert
    //             $kycModel->insert($data);
    //             $message = 'KYC submitted successfully';
    //         }

    //         return $this->response->setJSON([
    //             'status' => 'success',
    //             'message' => $message,
    //             'data' => $data
    //         ]);
    //     } catch (\Exception $e) {
    //         return $this->response->setJSON([
    //             'status' => 'error',
    //             'message' => 'Failed to submit KYC: ' . $e->getMessage()
    //         ]);
    //     }
    // }

    public function submit()
    {
        $request = service('request');
        $uploadPath = FCPATH . 'uploads/kyc/';

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $files = $request->getFiles();
        $errors = [];

        $allowedDocTypes = ['jpg', 'jpeg', 'png', 'pdf', 'gif', 'jfif'];
        $allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif', 'jfif'];

        $saveMultipleFiles = function ($fileArray, $fieldName, $oldFiles = null) use ($uploadPath, $allowedDocTypes, &$errors) {
            if (!$fileArray) return null;

            if (!is_array($fileArray)) $fileArray = [$fileArray];

            // Remove old files if exist
            if ($oldFiles) {
                $oldFilesArr = json_decode($oldFiles, true);
                if ($oldFilesArr) {
                    foreach ($oldFilesArr as $f) {
                        $oldPath = $uploadPath . $f;
                        if (file_exists($oldPath)) unlink($oldPath);
                    }
                }
            }

            $saved = [];
            foreach ($fileArray as $file) {
                if ($file && $file->isValid() && !$file->hasMoved()) {
                    $ext = strtolower($file->getClientExtension());
                    if (!in_array($ext, $allowedDocTypes)) {
                        $errors[$fieldName] = "Invalid file type for $fieldName.";
                        continue;
                    }
                    $newName = $file->getRandomName();
                    $file->move($uploadPath, $newName);
                    $saved[] = $newName;
                }
            }

            return !empty($saved) ? json_encode($saved) : null;
        };

        $saveSingleFile = function ($fileArray, $fieldName, $oldFile = null) use ($uploadPath, $allowedImageTypes, &$errors) {
            if (!$fileArray) return null;

            $file = is_array($fileArray) ? $fileArray[0] : $fileArray;

            // Remove old file if exists
            if ($oldFile) {
                $oldPath = $uploadPath . $oldFile;
                if (file_exists($oldPath)) unlink($oldPath);
            }

            if ($file && $file->isValid() && !$file->hasMoved()) {
                $ext = strtolower($file->getClientExtension());
                if (!in_array($ext, $allowedImageTypes)) {
                    $errors[$fieldName] = "Invalid file type for $fieldName.";
                    return null;
                }
                $newName = $file->getRandomName();
                $file->move($uploadPath, $newName);
                return $newName;
            }

            return null;
        };

        $currentUserId = session()->get('id');
        $policyId = $request->getPost('policy_id');

        $kycModel = new \App\Models\KycDocumentsModel();

        // Check if record exists
        $existing = $kycModel->where('user_id', $currentUserId)
            ->where('policy_id', $policyId)
            ->first();

        $updateData = [];

        // Multiple file fields
        $fieldsMultiple = ['aadhaar_card', 'pan_card', 'driving_licence', 'addr_aadhaar', 'addr_driving_licence'];
        foreach ($fieldsMultiple as $field) {
            $oldFiles = $existing[$field] ?? null;
            $uploaded = $saveMultipleFiles($files[$field] ?? null, $field, $oldFiles);
            if ($uploaded !== null) {
                $updateData[$field] = $uploaded;
            } elseif (!$existing) {
                $updateData[$field] = null; // For new record
            }
        }

        // Single file fields
        $fieldsSingle = ['photo', 'signature'];
        foreach ($fieldsSingle as $field) {
            $oldFile = $existing[$field] ?? null;
            $uploaded = $saveSingleFile($files[$field] ?? null, $field, $oldFile);
            if ($uploaded !== null) {
                $updateData[$field] = $uploaded;
            } elseif (!$existing) {
                $updateData[$field] = null; // For new record
            }
        }

        $updateData['user_id'] = $currentUserId;
        $updateData['policy_id'] = $policyId;
        $updateData['kyc_status'] = 'pending';

        if (!empty($errors)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'errors' => $errors
            ]);
        }

        try {
            if ($existing) {
                // Update existing record
                $kycModel->update($existing['id'], $updateData);
                $message = 'KYC updated successfully';
            } else {
                // Insert new record
                $kycModel->insert($updateData);
                $message = 'KYC submitted successfully';
            }

            return $this->response->setJSON([
                'status' => 'success',
                'message' => $message,
                'data' => $updateData
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to submit KYC: ' . $e->getMessage()
            ]);
        }
    }

    public function viewKyc()
    {
        $request = service('request');
        $policyId = $request->getPost('policy_id'); // Accept via POST
        $userId = session()->get('id'); // Optional: restrict to current user

        if (!$policyId) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => 'Policy ID is required.'
            ]);
        }

        $kycModel = new \App\Models\KycDocumentsModel();

        // Fetch KYC record for the given policy_id and current user
        $kyc = $kycModel->where('policy_id', $policyId)
            ->where('user_id', $userId) // Optional: remove if admin can view all
            ->first();

        if (!$kyc) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => 'KYC not found for this policy.'
            ]);
        }

        // Base URL for uploaded files
        $baseUrl = base_url('uploads/kyc/');

        // Fields that contain multiple files
        $multiFileFields = ['aadhaar_card', 'pan_card', 'driving_licence', 'addr_aadhaar', 'addr_driving_licence'];

        foreach ($multiFileFields as $field) {
            $kyc[$field] = $kyc[$field] ? json_decode($kyc[$field], true) : [];
            // Prepend base URL for each file
            $kyc[$field] = array_map(function ($file) use ($baseUrl) {
                return $baseUrl . $file;
            }, $kyc[$field]);
        }

        // Single file fields: photo and signature
        $kyc['photo'] = $kyc['photo'] ? $baseUrl . $kyc['photo'] : null;
        $kyc['signature'] = $kyc['signature'] ? $baseUrl . $kyc['signature'] : null;

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $kyc
        ]);
    }

    /**
     * Submit a single KYC document
     */
    public function submitSingle()
    {
        $request = service('request');
        $uploadPath = FCPATH . 'uploads/kyc/';

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $files = $request->getFiles();
        $errors = [];

        $allowedDocTypes = ['jpg', 'jpeg', 'png', 'pdf', 'gif', 'jfif'];
        $allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif', 'jfif'];

        $saveMultipleFiles = function ($fileArray, $fieldName, $oldFiles = null) use ($uploadPath, $allowedDocTypes, &$errors) {
            if (!$fileArray) return null;

            if (!is_array($fileArray)) $fileArray = [$fileArray];

            // Remove old files if exist
            if ($oldFiles) {
                $oldFilesArr = json_decode($oldFiles, true);
                if ($oldFilesArr) {
                    foreach ($oldFilesArr as $f) {
                        $oldPath = $uploadPath . $f;
                        if (file_exists($oldPath)) unlink($oldPath);
                    }
                }
            }

            $saved = [];
            foreach ($fileArray as $file) {
                if ($file && $file->isValid() && !$file->hasMoved()) {
                    $ext = strtolower($file->getClientExtension());
                    if (!in_array($ext, $allowedDocTypes)) {
                        $errors[$fieldName] = "Invalid file type for $fieldName.";
                        continue;
                    }
                    $newName = $file->getRandomName();
                    $file->move($uploadPath, $newName);
                    $saved[] = $newName;
                }
            }

            return !empty($saved) ? json_encode($saved) : null;
        };

        $saveSingleFile = function ($fileArray, $fieldName, $oldFile = null) use ($uploadPath, $allowedImageTypes, &$errors) {
            if (!$fileArray) return null;

            $file = is_array($fileArray) ? $fileArray[0] : $fileArray;

            // Remove old file if exists
            if ($oldFile) {
                $oldPath = $uploadPath . $oldFile;
                if (file_exists($oldPath)) unlink($oldPath);
            }

            if ($file && $file->isValid() && !$file->hasMoved()) {
                $ext = strtolower($file->getClientExtension());
                if (!in_array($ext, $allowedImageTypes)) {
                    $errors[$fieldName] = "Invalid file type for $fieldName.";
                    return null;
                }
                $newName = $file->getRandomName();
                $file->move($uploadPath, $newName);
                return $newName;
            }

            return null;
        };

        $currentUserId = session()->get('id');
        $policyId = $request->getPost('policy_id');

        $kycModel = new \App\Models\KycDocumentsModel();

        // Check if record exists
        $existing = $kycModel->where('user_id', $currentUserId)
            ->where('policy_id', $policyId)
            ->first();

        $updateData = [];

        // Process uploaded files
        $fieldsProcessed = false;

        // Handle ID Proof documents
        if (isset($files['aadhaar_card']) && $files['aadhaar_card']) {
            $oldFiles = $existing['aadhaar_card'] ?? null;
            $uploaded = $saveMultipleFiles($files['aadhaar_card'], 'aadhaar_card', $oldFiles);
            if ($uploaded !== null) {
                $updateData['aadhaar_card'] = $uploaded;
            }
            $fieldsProcessed = true;
        }

        if (isset($files['pan_card']) && $files['pan_card']) {
            $oldFiles = $existing['pan_card'] ?? null;
            $uploaded = $saveMultipleFiles($files['pan_card'], 'pan_card', $oldFiles);
            if ($uploaded !== null) {
                $updateData['pan_card'] = $uploaded;
            }
            $fieldsProcessed = true;
        }

        if (isset($files['driving_licence']) && $files['driving_licence']) {
            $oldFiles = $existing['driving_licence'] ?? null;
            $uploaded = $saveMultipleFiles($files['driving_licence'], 'driving_licence', $oldFiles);
            if ($uploaded !== null) {
                $updateData['driving_licence'] = $uploaded;
            }
            $fieldsProcessed = true;
        }

        // Handle Address Proof documents
        if (isset($files['addr_aadhaar']) && $files['addr_aadhaar']) {
            $oldFiles = $existing['addr_aadhaar'] ?? null;
            $uploaded = $saveMultipleFiles($files['addr_aadhaar'], 'addr_aadhaar', $oldFiles);
            if ($uploaded !== null) {
                $updateData['addr_aadhaar'] = $uploaded;
            }
            $fieldsProcessed = true;
        }

        if (isset($files['addr_driving_licence']) && $files['addr_driving_licence']) {
            $oldFiles = $existing['addr_driving_licence'] ?? null;
            $uploaded = $saveMultipleFiles($files['addr_driving_licence'], 'addr_driving_licence', $oldFiles);
            if ($uploaded !== null) {
                $updateData['addr_driving_licence'] = $uploaded;
            }
            $fieldsProcessed = true;
        }

        // Handle Photo & Signature documents
        if (isset($files['photo']) && $files['photo']) {
            $oldFile = $existing['photo'] ?? null;
            $uploaded = $saveSingleFile($files['photo'], 'photo', $oldFile);
            if ($uploaded !== null) {
                $updateData['photo'] = $uploaded;
            }
            $fieldsProcessed = true;
        }

        if (isset($files['signature']) && $files['signature']) {
            $oldFile = $existing['signature'] ?? null;
            $uploaded = $saveSingleFile($files['signature'], 'signature', $oldFile);
            if ($uploaded !== null) {
                $updateData['signature'] = $uploaded;
            }
            $fieldsProcessed = true;
        }

        if (!$fieldsProcessed) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No files were uploaded.'
            ]);
        }

        if (!empty($errors)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $errors
            ]);
        }

        try {
            $updateData['user_id'] = $currentUserId;
            $updateData['policy_id'] = $policyId;
            $updateData['kyc_status'] = 'pending';

            if ($existing) {
                // Update existing record
                $kycModel->update($existing['id'], $updateData);
                $message = 'Document updated successfully';
            } else {
                // Insert new record
                $kycModel->insert($updateData);
                $message = 'Document submitted successfully';
            }

            return $this->response->setJSON([
                'status' => 'success',
                'message' => $message,
                'data' => $updateData
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to submit document: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Extract data from uploaded Policy PDF
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function extractPolicyPdf()
    {
        $request = service('request');
        $file = $request->getFile('pdf_file');

        // Validate file
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Invalid or missing PDF file.'
            ])->setStatusCode(400);
        }

        // Validate file type
        if ($file->getClientMimeType() !== 'application/pdf') {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Please upload a valid PDF file.'
            ])->setStatusCode(400);
        }

        try {
            // Move file to temp location
            $tempPath = WRITEPATH . 'uploads/temp/';
            if (!is_dir($tempPath)) {
                mkdir($tempPath, 0755, true);
            }
            
            $tempFileName = 'pdf_' . time() . '_' . $file->getRandomName();
            $file->move($tempPath, $tempFileName);
            $fullPath = $tempPath . $tempFileName;

            // Verify PDF file is valid
            if (!file_exists($fullPath)) {
                return $this->response->setJSON([
                    'status' => false,
                    'message' => 'PDF file not found after upload.'
                ])->setStatusCode(400);
            }
            
            // Check if file is readable and has content
            $fileSize = filesize($fullPath);
            if ($fileSize === false || $fileSize < 100) {
                return $this->response->setJSON([
                    'status' => false,
                    'message' => 'PDF file appears to be empty or corrupted.'
                ])->setStatusCode(400);
            }
            
            // Verify it's actually a PDF by checking header
            $fileHeader = file_get_contents($fullPath, false, null, 0, 4);
            if ($fileHeader !== '%PDF') {
                log_message('warning', 'File does not appear to be a valid PDF. Header: ' . bin2hex($fileHeader));
            }

            // Extract text from PDF
            log_message('info', 'Starting PDF extraction for file: ' . basename($fullPath) . ' (Size: ' . $fileSize . ' bytes)');
            $extractedText = $this->extractTextFromPdf($fullPath);
            log_message('info', 'Initial extraction result - Length: ' . strlen($extractedText) . ', Method: ' . $this->getExtractionMethod());
            
            // If extraction returned minimal text, try to get more from binary parsing
            if (empty($extractedText) || strlen(trim($extractedText)) < 50) {
                log_message('info', 'Initial extraction returned minimal text (' . strlen($extractedText) . ' chars), trying enhanced binary extraction');
                try {
                    $content = file_get_contents($fullPath);
                    log_message('info', 'PDF file size: ' . strlen($content) . ' bytes');
                    
                    // Try to extract more text using multiple methods
                    $enhancedText = $this->enhancedBinaryExtraction($content);
                    log_message('info', 'Enhanced binary extraction returned text length: ' . strlen($enhancedText));
                    
                    if (!empty($enhancedText) && strlen($enhancedText) > strlen($extractedText)) {
                        $extractedText = $enhancedText;
                        log_message('info', 'Using enhanced extraction result - Length: ' . strlen($extractedText));
                    } else {
                        log_message('warning', 'Enhanced extraction did not improve results. Original: ' . strlen($extractedText) . ', Enhanced: ' . strlen($enhancedText));
                    }
                } catch (\Exception $e) {
                    log_message('error', 'Enhanced binary extraction error: ' . $e->getMessage());
                }
            }
            
            // Log sample of extracted text for debugging
            if (!empty($extractedText)) {
                $sample = substr($extractedText, 0, 200);
                log_message('info', 'Extracted text sample (first 200 chars): ' . $sample);
            } else {
                log_message('warning', 'No text extracted from PDF. File may be encrypted, image-based, or in unsupported format.');
            }

            // If extraction failed, try to extract from filename as fallback
            if (empty($extractedText) || strlen(trim($extractedText)) < 50) {
                $fileName = $file->getName();
                log_message('info', 'Extraction failed, attempting to extract from filename: ' . $fileName);
                
                // Try to extract policy number from filename (e.g., "DG_4W0D_IYR_SCHEDULEHC_D238619471_...")
                if (preg_match('/[A-Z0-9]{8,}/', $fileName, $filenameMatches)) {
                    $potentialPolicyNo = $filenameMatches[0];
                    if (strlen($potentialPolicyNo) >= 8) {
                        $extractedText = "Policy Number: " . $potentialPolicyNo . "\n";
                        log_message('info', 'Extracted potential policy number from filename: ' . $potentialPolicyNo);
                    }
                }
            }
            
            // Parse extracted text to find policy data (even if minimal)
            $parsedData = $this->parsePolicyData($extractedText);

            // Clean up temp file
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }

            // Log extraction results for debugging
            log_message('info', 'PDF Extraction - Text length: ' . strlen($extractedText));
            log_message('info', 'PDF Extraction - Parsed data count: ' . count($parsedData));
            
            // Clean extracted data - remove newlines and extra spaces from string values
            foreach ($parsedData as $key => $value) {
                if (is_string($value)) {
                    $parsedData[$key] = preg_replace('/\s+/', ' ', trim($value));
                    // Remove "Branch" suffix from company_name if present
                    if ($key === 'company_name') {
                        $parsedData[$key] = preg_replace('/\s*Branch\s*$/i', '', $parsedData[$key]);
                    }
                }
            }
            
            // Match names with IDs from database (only if we have parsed data)
            if (!empty($parsedData)) {
                $parsedData = $this->matchNamesWithIds($parsedData);
            }
            
            // Always return raw_text for frontend extraction, even if backend extraction failed
            $rawTextForFrontend = !empty($extractedText) ? substr($extractedText, 0, 3000) : '';
            
            // If parsedData is empty but we have raw_text, still return success so frontend can extract
            $status = true;
            $message = empty($parsedData) && strlen($rawTextForFrontend) > 50 
                ? 'PDF processed. Some data extracted. Frontend will attempt additional extraction from raw text.' 
                : (empty($parsedData) 
                    ? 'PDF processed but no data could be extracted. The PDF may be image-based or encrypted.' 
                    : 'PDF data extracted successfully.');
            
            return $this->response->setJSON([
                'status' => $status,
                'message' => $message,
                'data' => $parsedData ?: [],
                'raw_text' => $rawTextForFrontend,
                'text_length' => strlen($extractedText),
                'extraction_method' => $this->getExtractionMethod()
            ]);

        } catch (\Exception $e) {
            log_message('error', 'PDF Extraction Error: ' . $e->getMessage());
            log_message('error', 'PDF Extraction Stack Trace: ' . $e->getTraceAsString());
            
            // Clean up temp file if it exists
            if (isset($fullPath) && file_exists($fullPath)) {
                @unlink($fullPath);
            }
            
            return $this->response->setJSON([
                'status' => false,
                'message' => 'Failed to extract data from PDF: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ])->setStatusCode(500);
        }
    }

    /**
     * Extract text from PDF file
     * 
     * @param string $filePath Path to PDF file
     * @return string Extracted text
     */
    private function extractTextFromPdf($filePath)
    {
        $text = '';

        // Method 1: Try using smalot/pdfparser if available (BEST METHOD)
        // Check if the class exists or can be autoloaded
        $parserClass = '\Smalot\PdfParser\Parser';
        if (class_exists($parserClass) || file_exists(FCPATH . '../vendor/smalot/pdfparser/src/Smalot/PdfParser/Parser.php')) {
            try {
                // Try to autoload if not already loaded
                if (!class_exists($parserClass)) {
                    require_once FCPATH . '../vendor/autoload.php';
                }
                
                if (class_exists($parserClass)) {
                    $parser = new $parserClass();
                    $pdf = $parser->parseFile($filePath);
                    
                    // Check if PDF is encrypted
                    $details = $pdf->getDetails();
                    if (isset($details['Encrypted']) && $details['Encrypted']) {
                        log_message('warning', 'PDF is encrypted, trying alternative extraction methods');
                    } else {
                        $text = $pdf->getText();
                        if (!empty($text) && strlen(trim($text)) > 10) {
                            log_message('info', 'PDF text extracted using smalot/pdfparser - Length: ' . strlen($text));
                            return trim($text);
                        }
                    }
                }
            } catch (\Exception $e) {
                log_message('error', 'PDF Parser Error: ' . $e->getMessage());
                log_message('error', 'PDF Parser Trace: ' . $e->getTraceAsString());
                
                // If it's an encryption error, try alternative methods
                if (stripos($e->getMessage(), 'encrypt') !== false || stripos($e->getMessage(), 'password') !== false) {
                    log_message('warning', 'PDF appears to be encrypted, will try binary extraction');
                }
            }
        } else {
            log_message('info', 'smalot/pdfparser not found, trying alternative methods');
        }

        // Method 2: Try using pdftotext command (if available on Windows/Linux)
        if (empty($text) && function_exists('shell_exec')) {
            // Try Windows path first
            $pdftotextPaths = [
                'pdftotext',  // Linux/Unix
                'C:\\xampp\\poppler\\bin\\pdftotext.exe',  // XAMPP with poppler
                'C:\\Program Files\\poppler\\bin\\pdftotext.exe',
                'C:\\Program Files (x86)\\poppler\\bin\\pdftotext.exe'
            ];
            
            foreach ($pdftotextPaths as $pdftotext) {
                $command = escapeshellarg($pdftotext) . ' ' . escapeshellarg($filePath) . ' - 2>&1';
                $output = @shell_exec($command);
                if (!empty($output) && strlen($output) > 50) {
                    $text = $output;
                    log_message('info', 'PDF text extracted using pdftotext');
                    break;
                }
            }
        }

        // Method 3: Improved text extraction from PDF binary
        if (empty($text)) {
            try {
                $content = file_get_contents($filePath);
                
                // Method 3a: Extract text from PDF text objects
                // Look for text in parentheses (PDF text strings) - improved pattern
                preg_match_all('/\((.*?)\)/s', $content, $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $match) {
                        // Decode PDF escape sequences
                        $decoded = str_replace(['\\n', '\\r', '\\t'], ["\n", "\r", "\t"], $match);
                        $decoded = preg_replace('/\\\\[0-9]{3}/', '', $decoded); // Remove octal escapes
                        $decoded = preg_replace('/\\\\[a-zA-Z]/', '', $decoded); // Remove other escapes
                        
                        // Only keep readable text
                        $cleanText = preg_replace('/[^\x20-\x7E\n\r\t]/', '', $decoded);
                        if (strlen(trim($cleanText)) > 2) {
                            $text .= $cleanText . " ";
                        }
                    }
                }
                
                // Method 3a2: Extract text from angle brackets (another PDF text format)
                if (empty($text) || strlen($text) < 50) {
                    preg_match_all('/<([0-9A-Fa-f]+)>/s', $content, $hexMatches);
                    if (!empty($hexMatches[1])) {
                        foreach ($hexMatches[1] as $hex) {
                            if (strlen($hex) % 2 === 0) {
                                $decoded = '';
                                for ($i = 0; $i < strlen($hex); $i += 2) {
                                    $byte = hexdec(substr($hex, $i, 2));
                                    if ($byte >= 32 && $byte <= 126) { // Printable ASCII
                                        $decoded .= chr($byte);
                                    }
                                }
                                if (strlen(trim($decoded)) > 2) {
                                    $text .= $decoded . " ";
                                }
                            }
                        }
                    }
                }
                
                // Method 3b: Extract text from stream objects (for uncompressed and compressed PDFs)
                if (empty($text) || strlen($text) < 100) {
                    preg_match_all('/stream\s*(.*?)\s*endstream/s', $content, $streamMatches);
                    if (!empty($streamMatches[1])) {
                        foreach ($streamMatches[1] as $stream) {
                            // Try multiple decompression methods
                            $decoded = false;
                            
                            // Try FlateDecode (most common)
                            $decoded = @gzuncompress($stream);
                            if ($decoded === false) {
                                // Try zlib decompress
                                $decoded = @gzdecode($stream);
                            }
                            if ($decoded === false) {
                                // Try raw stream
                                $decoded = $stream;
                            }
                            
                            // Extract readable ASCII text
                            $cleanText = preg_replace('/[^\x20-\x7E\n\r]/', '', $decoded);
                            if (strlen($cleanText) > 20) {
                                $text .= $cleanText . "\n";
                            }
                            
                            // Also try to extract text from parentheses within streams
                            preg_match_all('/\((.*?)\)/s', $decoded, $streamTextMatches);
                            if (!empty($streamTextMatches[1])) {
                                foreach ($streamTextMatches[1] as $streamText) {
                                    $clean = preg_replace('/[^\x20-\x7E]/', '', $streamText);
                                    if (strlen(trim($clean)) > 2) {
                                        $text .= $clean . " ";
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Method 3c: Extract text between BT (Begin Text) and ET (End Text) markers
                if (empty($text) || strlen($text) < 100) {
                    preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $btMatches);
                    if (!empty($btMatches[1])) {
                        foreach ($btMatches[1] as $btText) {
                            // Extract text in parentheses
                            preg_match_all('/\((.*?)\)/', $btText, $textMatches);
                            foreach ($textMatches[1] as $txt) {
                                // Decode PDF escape sequences
                                $decoded = str_replace(['\\n', '\\r', '\\t', '\\('], ["\n", "\r", "\t", "("], $txt);
                                $decoded = preg_replace('/\\\\([0-9]{1,3})/', '', $decoded);
                                $clean = preg_replace('/[^\x20-\x7E]/', '', $decoded);
                                if (strlen(trim($clean)) > 1) {
                                    $text .= $clean . " ";
                                }
                            }
                            
                            // Also extract text from TJ (text positioning) operators
                            preg_match_all('/\[(.*?)\]\s*TJ/s', $btText, $tjMatches);
                            foreach ($tjMatches[1] as $tjText) {
                                preg_match_all('/\((.*?)\)/', $tjText, $tjTextMatches);
                                foreach ($tjTextMatches[1] as $txt) {
                                    $clean = preg_replace('/[^\x20-\x7E]/', '', $txt);
                                    if (strlen(trim($clean)) > 1) {
                                        $text .= $clean . " ";
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Method 3d: Extract text using Tj and TJ operators (text showing operators)
                if (empty($text) || strlen($text) < 100) {
                    // Extract from Tj operator: (text) Tj
                    preg_match_all('/\((.*?)\)\s*Tj/s', $content, $tjMatches);
                    if (!empty($tjMatches[1])) {
                        foreach ($tjMatches[1] as $txt) {
                            $decoded = str_replace(['\\n', '\\r', '\\t'], ["\n", "\r", "\t"], $txt);
                            $clean = preg_replace('/[^\x20-\x7E]/', '', $decoded);
                            if (strlen(trim($clean)) > 1) {
                                $text .= $clean . " ";
                            }
                        }
                    }
                    
                    // Extract from TJ operator: [(text1) (text2)] TJ
                    preg_match_all('/\[(.*?)\]\s*TJ/s', $content, $tjArrayMatches);
                    if (!empty($tjArrayMatches[1])) {
                        foreach ($tjArrayMatches[1] as $tjArray) {
                            preg_match_all('/\((.*?)\)/', $tjArray, $arrayTextMatches);
                            foreach ($arrayTextMatches[1] as $txt) {
                                $decoded = str_replace(['\\n', '\\r', '\\t'], ["\n", "\r", "\t"], $txt);
                                $clean = preg_replace('/[^\x20-\x7E]/', '', $decoded);
                                if (strlen(trim($clean)) > 1) {
                                    $text .= $clean . " ";
                                }
                            }
                        }
                    }
                }
                
                // Method 3e: Extract text from PDF using more aggressive pattern matching
                if (empty($text) || strlen(trim($text)) < 100) {
                    // Look for readable text sequences (at least 3 consecutive printable characters)
                    preg_match_all('/([A-Za-z0-9\s\.\,\:\-\/\(\)]{3,})/', $content, $textMatches);
                    if (!empty($textMatches[1])) {
                        $extracted = '';
                        foreach ($textMatches[1] as $match) {
                            $clean = preg_replace('/[^\x20-\x7E\n\r]/', '', $match);
                            if (strlen(trim($clean)) > 3 && !preg_match('/^[\d\s\.\,\:\-\/]+$/', $clean)) {
                                $extracted .= $clean . "\n";
                            }
                        }
                        if (strlen($extracted) > 50) {
                            $text .= $extracted;
                        }
                    }
                }
                
                if (!empty($text)) {
                    log_message('info', 'PDF text extracted using binary parsing method - Length: ' . strlen($text));
                }
            } catch (\Exception $e) {
                log_message('error', 'PDF binary extraction error: ' . $e->getMessage());
            }
        }

            // Clean up the extracted text
        if (!empty($text)) {
            // Remove excessive whitespace but preserve line breaks for structure
            $text = preg_replace('/[ \t]+/', ' ', $text);
            // Remove special characters but keep alphanumeric and common punctuation
            $text = preg_replace('/[^\w\s\.\,\:\-\/\(\)\n\r]/', ' ', $text);
            $text = trim($text);
        }

        return $text;
    }
    
    /**
     * Enhanced binary extraction for difficult PDFs
     * 
     * @param string $content PDF file content
     * @return string Extracted text
     */
    private function enhancedBinaryExtraction($content)
    {
        $text = '';
        
        try {
            // Method 1: Extract all text between parentheses (most common PDF text format)
            preg_match_all('/\((.*?)\)/s', $content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $match) {
                    // Decode PDF escape sequences
                    $decoded = str_replace(['\\n', '\\r', '\\t', '\\('], ["\n", "\r", "\t", "("], $match);
                    $decoded = preg_replace('/\\\\([0-9]{1,3})/', '', $decoded); // Remove octal escapes
                    $decoded = preg_replace('/\\\\[a-zA-Z]/', '', $decoded); // Remove other escapes
                    
                    // Only keep readable text
                    $cleanText = preg_replace('/[^\x20-\x7E\n\r\t]/', '', $decoded);
                    if (strlen(trim($cleanText)) > 1) {
                        $text .= $cleanText . " ";
                    }
                }
            }
            
            // Method 1b: Extract from hex-encoded text
            if (strlen($text) < 100) {
                preg_match_all('/<([0-9A-Fa-f]+)>/s', $content, $hexMatches);
                if (!empty($hexMatches[1])) {
                    foreach ($hexMatches[1] as $hex) {
                        if (strlen($hex) % 2 === 0 && strlen($hex) > 4) {
                            $decoded = '';
                            for ($i = 0; $i < strlen($hex); $i += 2) {
                                $byte = hexdec(substr($hex, $i, 2));
                                if ($byte >= 32 && $byte <= 126) { // Printable ASCII
                                    $decoded .= chr($byte);
                                }
                            }
                            if (strlen(trim($decoded)) > 2) {
                                $text .= $decoded . " ";
                            }
                        }
                    }
                }
            }
            
            // Method 2: Extract from stream objects (compressed and uncompressed)
            if (strlen($text) < 100) {
                preg_match_all('/stream\s*(.*?)\s*endstream/s', $content, $streamMatches);
                if (!empty($streamMatches[1])) {
                    foreach ($streamMatches[1] as $stream) {
                        // Try multiple decompression methods
                        $decoded = false;
                        $decoded = @gzuncompress($stream);
                        if ($decoded === false) {
                            $decoded = @gzdecode($stream);
                        }
                        if ($decoded === false) {
                            $decoded = $stream;
                        }
                        
                        // Extract readable ASCII text
                        $cleanText = preg_replace('/[^\x20-\x7E\n\r]/', '', $decoded);
                        if (strlen($cleanText) > 20) {
                            $text .= $cleanText . "\n";
                        }
                        
                        // Also extract text from parentheses within streams
                        preg_match_all('/\((.*?)\)/s', $decoded, $streamTextMatches);
                        if (!empty($streamTextMatches[1])) {
                            foreach ($streamTextMatches[1] as $streamText) {
                                $clean = preg_replace('/[^\x20-\x7E]/', '', $streamText);
                                if (strlen(trim($clean)) > 2) {
                                    $text .= $clean . " ";
                                }
                            }
                        }
                    }
                }
            }
            
            // Method 3: Extract from BT/ET blocks with Tj and TJ operators
            if (strlen($text) < 100) {
                preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $btMatches);
                if (!empty($btMatches[1])) {
                    foreach ($btMatches[1] as $btText) {
                        // Extract text in parentheses
                        preg_match_all('/\((.*?)\)/', $btText, $textMatches);
                        foreach ($textMatches[1] as $txt) {
                            $decoded = str_replace(['\\n', '\\r', '\\t'], ["\n", "\r", "\t"], $txt);
                            $clean = preg_replace('/[^\x20-\x7E]/', '', $decoded);
                            if (strlen(trim($clean)) > 1) {
                                $text .= $clean . " ";
                            }
                        }
                        
                        // Extract from Tj operator
                        preg_match_all('/\((.*?)\)\s*Tj/s', $btText, $tjMatches);
                        foreach ($tjMatches[1] as $txt) {
                            $decoded = str_replace(['\\n', '\\r', '\\t'], ["\n", "\r", "\t"], $txt);
                            $clean = preg_replace('/[^\x20-\x7E]/', '', $decoded);
                            if (strlen(trim($clean)) > 1) {
                                $text .= $clean . " ";
                            }
                        }
                        
                        // Extract from TJ operator
                        preg_match_all('/\[(.*?)\]\s*TJ/s', $btText, $tjArrayMatches);
                        foreach ($tjArrayMatches[1] as $tjArray) {
                            preg_match_all('/\((.*?)\)/', $tjArray, $arrayTextMatches);
                            foreach ($arrayTextMatches[1] as $txt) {
                                $decoded = str_replace(['\\n', '\\r', '\\t'], ["\n", "\r", "\t"], $txt);
                                $clean = preg_replace('/[^\x20-\x7E]/', '', $decoded);
                                if (strlen(trim($clean)) > 1) {
                                    $text .= $clean . " ";
                                }
                            }
                        }
                    }
                }
            }
            
            // Method 4: Direct Tj and TJ extraction (outside BT/ET blocks)
            if (strlen($text) < 100) {
                preg_match_all('/\((.*?)\)\s*Tj/s', $content, $directTjMatches);
                foreach ($directTjMatches[1] as $txt) {
                    $decoded = str_replace(['\\n', '\\r', '\\t'], ["\n", "\r", "\t"], $txt);
                    $clean = preg_replace('/[^\x20-\x7E]/', '', $decoded);
                    if (strlen(trim($clean)) > 1) {
                        $text .= $clean . " ";
                    }
                }
                
                preg_match_all('/\[(.*?)\]\s*TJ/s', $content, $directTjArrayMatches);
                foreach ($directTjArrayMatches[1] as $tjArray) {
                    preg_match_all('/\((.*?)\)/', $tjArray, $arrayTextMatches);
                    foreach ($arrayTextMatches[1] as $txt) {
                        $decoded = str_replace(['\\n', '\\r', '\\t'], ["\n", "\r", "\t"], $txt);
                        $clean = preg_replace('/[^\x20-\x7E]/', '', $decoded);
                        if (strlen(trim($clean)) > 1) {
                            $text .= $clean . " ";
                        }
                    }
                }
            }
            
            // Clean up
            if (!empty($text)) {
                // Preserve line breaks for better parsing
                $text = preg_replace('/[ \t]+/', ' ', $text);
                $text = preg_replace('/[^\w\s\.\,\:\-\/\(\)\n\r]/', ' ', $text);
                $text = trim($text);
            }
        } catch (\Exception $e) {
            log_message('error', 'Enhanced binary extraction error: ' . $e->getMessage());
        }
        
        return $text;
    }

    /**
     * Get the extraction method used (for debugging)
     * 
     * @return string
     */
    private function getExtractionMethod()
    {
        if (class_exists('\Smalot\PdfParser\Parser')) {
            return 'smalot/pdfparser';
        }
        if (function_exists('shell_exec')) {
            return 'pdftotext command';
        }
        return 'binary parsing';
    }

    /**
     * Parse extracted text to find policy data
     * 
     * @param string $text Extracted text from PDF
     * @return array Parsed policy data
     */
    private function parsePolicyData($text)
    {
        $data = [];
        $textLower = strtolower($text);
        $originalLines = explode("\n", $text);
        $lines = array_map('trim', $originalLines);
        
        // Keep original text for pattern matching
        $originalText = $text;
        
        // Clean text - remove extra whitespace but preserve structure for some patterns
        $text = preg_replace('/\s+/', ' ', $text);
        $text = str_replace(["\r\n", "\r", "\n"], ' ', $text);
        
        // Also create a version with preserved line breaks for line-by-line parsing
        $textWithLines = implode("\n", $lines);

        // Policy Number patterns - Enhanced for different PDF formats (Go Digit, HDFC ERGO, AKP Insurance)
        // Important: Stop at vehicle-related keywords to prevent capturing vehicle model
        $policyNumberPatterns = [
            // Go Digit format: "Policy No.: D238619471/25112025"
            '/policy\s*no\.?[\s:]*([A-Z0-9\-\/\s]+?)(?:\s*(?:Policy\s*Issue|Period|From|Model|Vehicle|Registration|Engine|Chassis|Fuel|Year|GVW|NCB)|$)/i',
            // Standard formats with boundaries
            '/policy\s*(?:number|no|#|no\.)[\s:]*([A-Z0-9\-\/\s]+?)(?:\s*(?:Policy\s*Issue|Period|From|Model|Vehicle|Registration|Engine|Chassis|Fuel|Year|GVW|NCB)|$)/i',
            '/policy\s*no[:\s]+([A-Z0-9\-\/\s]+?)(?:\s*(?:Model|Vehicle|Registration|Engine|Chassis|Fuel|Year|GVW|NCB)|$)/i',
            // Numeric-only policy numbers (most common) - stop at first letter that's not part of a number/separator
            '/policy\s*(?:number|no|#|no\.)[\s:]*([0-9\s\-\/]+?)(?:\s*[A-Za-z]|$)/i',
            // Pattern for long numeric strings like "2301207607005700000" - stop at word boundaries
            '/policy\s*(?:number|no|#|no\.)[\s:]*([0-9]{6,}(?:\s+[0-9]+)*(?:\/[0-9]+)?)(?:\s*[A-Za-z]|$)/i',
            // For formats like "0238639471/25112025" or "2301 2076 0700 5700 000" - numeric only
            '/(?:policy\s*no|policy\s*number)[\s:]*([0-9\s\/\-]+?)(?:\s*[A-Za-z]|$)/i',
            // Fallback: numeric-only pattern (stops at first letter)
            '/policy[\s:]*([0-9]{6,}(?:\s+[0-9]+)*(?:\/[0-9]+)?)(?:\s*[A-Za-z]|$)/i'
        ];
        foreach ($policyNumberPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $policyNo = preg_replace('/\s+/', '', trim($matches[1])); // Remove spaces
                // Additional validation: policy number should be primarily numeric
                // Allow some letters (like "D" prefix) but not full words
                $numericChars = preg_match_all('/[0-9]/', $policyNo);
                $totalChars = strlen($policyNo);
                // Policy number should be at least 70% numeric (allows for separators and prefixes)
                if ($totalChars > 0 && ($numericChars / $totalChars) >= 0.7) {
                    // Stop at first occurrence of common vehicle keywords
                    $policyNo = preg_replace('/(Model|Vehicle|Registration|Engine|Chassis|Fuel|Year|GVW|NCB).*$/i', '', $policyNo);
                    $policyNo = trim($policyNo);
                    if (strlen($policyNo) >= 1 && strlen($policyNo) <= 50) {
                        $data['policy_number'] = $policyNo;
                        log_message('info', 'Policy Number extracted: ' . $policyNo);
                        break;
                    }
                }
            }
        }
        
        // Proposer/Policy Holder Name - extract from "Proposer Details" section
        $holderNamePatterns = [
            '/proposer\s*details.*?name[\s:]*([A-Za-z\s]+?)(?:\s*Address|$)/is',
            '/name[\s:]*([A-Za-z\s]+?)(?:\s*Address|Contact|$)/i',
            '/policy\s*holder[\s:]*([A-Za-z\s]+?)(?:\s*Address|Contact|$)/i',
            '/holder\s*name[\s:]*([A-Za-z\s]+?)(?:\s*Address|Contact|$)/i'
        ];
        foreach ($holderNamePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $holderName = trim(preg_replace('/\s+/', ' ', $matches[1]));
                if (strlen($holderName) > 2 && strlen($holderName) < 100) {
                    $data['holder_name'] = $holderName;
                    $data['user_name'] = $holderName; // Also set user_name for compatibility
                    break;
                }
            }
        }

        // Policy Type - improved extraction with multiple strategies for different PDF formats
        // Strategy 1: Direct pattern matching (AKP Insurance format)
        $policyTypePatterns = [
            '/policy\s*type[\s:]*([^\n\r:]+?)(?:\s*\n|\s*Sub\s*Type|\s*Segment|\s*Company|$)/i',
            '/policy\s*type[\s:]*([A-Za-z0-9\s]{1,50}?)(?:\s*Sub|$)/i',
            '/policy\s*type[\s:]*([A-Za-z0-9\s]{2,50})/i'
        ];
        foreach ($policyTypePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                if (isset($matches[1]) && !empty($matches[1])) {
                    $policyType = trim($matches[1]);
                    // Remove trailing colons, semicolons, or extra spaces
                    $policyType = preg_replace('/[:;]\s*$/', '', $policyType);
                    $policyType = trim($policyType);
                    if (strlen($policyType) > 1 && strlen($policyType) < 100) {
                        $data['policy_type'] = $policyType;
                        break;
                    }
                }
            }
        }
        
        // Strategy 2: Extract from policy title/description (Go Digit, HDFC ERGO format)
        if (empty($data['policy_type'])) {
            $policyTitlePatterns = [
                '/digit\s+private\s+car[\s\-]+stand[\s\-]+alone[\s\-]+own\s+damage/i' => 'Vehicle',
                '/standalone\s+motor\s+own\s+damage[\s\-]+two\s+wheeler/i' => 'Vehicle',
                '/standalone\s+motor\s+own\s+damage/i' => 'Vehicle',
                '/two\s+wheeler/i' => 'Vehicle',
                '/private\s+car/i' => 'Vehicle',
                '/motor\s+own\s+damage/i' => 'Vehicle',
                '/motor\s+insurance/i' => 'Vehicle',
                '/vehicle\s+insurance/i' => 'Vehicle',
                '/car\s+insurance/i' => 'Vehicle',
                '/bike\s+insurance/i' => 'Vehicle',
                '/family\s+health[\s\-]+protect/i' => 'Health',
                '/health\s+protect/i' => 'Health',
                '/health\s+insurance/i' => 'Health',
                '/medical\s+insurance/i' => 'Health',
                '/life\s+insurance/i' => 'Life',
                '/term\s+insurance/i' => 'Life',
                '/endowment/i' => 'Life'
            ];
            
            foreach ($policyTitlePatterns as $pattern => $type) {
                if (preg_match($pattern, $text, $matches)) {
                    $data['policy_type'] = $type;
                    log_message('info', 'Policy Type extracted from title: ' . $type);
                    break;
                }
            }
        }
        
        // Strategy 3: Check for vehicle-specific fields to infer Vehicle type
        if (empty($data['policy_type']) && (
            preg_match('/registration\s*(?:no|number)/i', $text) ||
            preg_match('/engine\s*(?:no|number)/i', $text) ||
            preg_match('/chassis\s*(?:no|number)/i', $text) ||
            preg_match('/vehicle\s*(?:make|model|registration)/i', $text) ||
            preg_match('/[A-Z]{2}[\s\-]?\d{1,2}[\s\-]?[A-Z]{1,2}[\s\-]?\d{1,4}/i', $text) || // Vehicle reg pattern
            preg_match('/vehicle\s+registration|registration\s+no|engine\s+no|chassis\s+no/i', $text)
        )) {
            $data['policy_type'] = 'Vehicle';
            log_message('info', 'Policy Type inferred as Vehicle from vehicle fields');
        }
        
        // Fallback: try common policy types (only if not found yet)
        if (empty($data['policy_type'])) {
            $policyTypes = ['health', 'life', 'vehicle', 'motor', 'car', 'bike', 'term', 'endowment', 'ulip', 'medical', 'accident', 'travel'];
            foreach ($policyTypes as $type) {
                // Look for the type as a standalone word or after "policy type"
                if (preg_match('/policy\s*type[:\s]+' . preg_quote($type, '/') . '/i', $text) || 
                    preg_match('/\b' . preg_quote($type, '/') . '\b/i', $text)) {
                    $data['policy_type'] = ucfirst($type);
                    break;
                }
            }
        }
        
        // Log extracted policy type for debugging
        if (!empty($data['policy_type'])) {
            log_message('info', 'PDF Policy Type extracted: "' . $data['policy_type'] . '"');
        }

        // Company Name - improved patterns for different PDF formats
        // First, try known company patterns with direct replacements
        $knownCompanyPatterns = [
            '/go\s+digit\s+general\s+insurance\s+ltd\.?/i' => 'Go Digit General Insurance Ltd',
            '/hdfc\s+ergo/i' => 'HDFC ERGO',
            '/bajaj\s+allianz/i' => 'Bajaj Allianz',
            '/icici\s+lombard/i' => 'ICICI Lombard',
            '/new\s+india\s+assurance/i' => 'New India Assurance',
            '/oriental\s+insurance/i' => 'Oriental Insurance',
            '/united\s+india\s+insurance/i' => 'United India Insurance',
            '/national\s+insurance/i' => 'National Insurance'
        ];
        
        $companyFound = false;
        foreach ($knownCompanyPatterns as $pattern => $companyName) {
            if (preg_match($pattern, $text)) {
                $data['company_name'] = $companyName;
                $companyFound = true;
                break;
            }
        }
        
        // If not found, try generic patterns
        if (!$companyFound) {
            $companyPatterns = [
                // AKP Insurance format: "Company: test Company"
                '/company[\s:]*([A-Za-z0-9\s]+?)(?:\s*Branch|$)/i',
                // Generic patterns
                '/insurance\s+company[\s:]*([A-Za-z0-9\s]+?)(?:\s*Branch|$)/i',
                '/insurer[\s:]*([A-Za-z0-9\s]+?)(?:\s*Branch|$)/i',
                '/company[\s:]*([A-Za-z0-9\s]{3,50})/i',
                // Extract from header/title area
                '/([A-Z][A-Za-z\s]+(?:Insurance|Insurance\s+Ltd|ERGO|General\s+Insurance))/i'
            ];
            
            foreach ($companyPatterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    $companyName = trim($matches[1]);
                    $companyName = preg_replace('/\s*Branch\s*$/i', '', $companyName);
                    $companyName = trim($companyName);
                    if (strlen($companyName) > 2 && strlen($companyName) < 100) {
                        $data['company_name'] = $companyName;
                        $companyFound = true;
                        break;
                    }
                }
            }
        }
        
        // Branch Name
        $branchPatterns = [
            '/branch[\s:]*([A-Za-z0-9\s]+?)(?:\s*Policy|$)/i',
            '/branch[\s:]*([A-Za-z0-9\s]{2,50})/i'
        ];
        foreach ($branchPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $branchName = trim($matches[1]);
                if (strlen($branchName) > 1 && strlen($branchName) < 50) {
                    $data['branch_name'] = $branchName;
                    break;
                }
            }
        }
        
        // Sub Type
        $subTypePatterns = [
            '/sub\s*type[\s:]*([A-Za-z0-9\s\-]+?)(?:\s*Segment|$)/i',
            '/sub\s*type[\s:]*([A-Za-z0-9\s\-]{2,50})/i'
        ];
        foreach ($subTypePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $subTypeName = trim($matches[1]);
                if (strlen($subTypeName) > 1) {
                    $data['sub_type_name'] = $subTypeName;
                    log_message('info', 'Sub Type Name extracted: ' . $subTypeName);
                    break;
                }
            }
        }
        
        // Segment
        $segmentPatterns = [
            '/segment[\s:]*([A-Za-z0-9\s]+?)(?:\s*Company|$)/i',
            '/segment[\s:]*([A-Za-z0-9\s]{2,50})/i'
        ];
        foreach ($segmentPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $segmentName = trim($matches[1]);
                if (strlen($segmentName) > 1) {
                    $data['segment_name'] = $segmentName;
                    break;
                }
            }
        }

        // Dates - improved patterns for different PDF formats
        // Start Date patterns
        $datePatterns = [
            // AKP Insurance format: "2025-12-30 to 2025-12-30"
            '/policy\s*period[\s:]*(\d{4}-\d{2}-\d{2})\s*to/i',
            // HDFC ERGO format: "From 31 Jul, 2025 00:01 hrs"
            '/from\s+(\d{1,2})\s+([A-Za-z]{3,}),\s+(\d{4})/i',
            // Go Digit format: "From: 26-Nov-2025 00:00:01" or "Period of Policy... From: 26-Nov-2025"
            '/period\s*of\s*policy.*?from[:\s]+(\d{1,2}[\/\-][A-Za-z]{3,}[\/\-]\d{4})/i',
            '/from[:\s]+(\d{1,2}[\/\-][A-Za-z]{3,}[\/\-]\d{4})/i',
            // Standard formats
            '/start\s*date[\s:]*(\d{4}-\d{2}-\d{2})/i',
            '/start\s*date[\s:]*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
            '/commencement\s*date[\s:]*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
            '/from\s*date[\s:]*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
            '/period\s*of\s*insurance[\s:]*from\s+(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
            '/period\s*of\s*policy[\s:]*from\s+(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i'
        ];
        foreach ($datePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                try {
                    $dateStr = trim($matches[1]);
                    // Handle "31 Jul, 2025" format - check if pattern has 3 capture groups
                    if (isset($matches[2]) && isset($matches[3]) && !empty($matches[2]) && !empty($matches[3])) {
                        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                        $month = $this->monthNameToNumber($matches[2]);
                        $year = $matches[3];
                        if ($month && $day && $year) {
                            $dateStr = $year . '-' . $month . '-' . $day;
                        }
                    }
                    // If already in YYYY-MM-DD format, use directly
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                        $data['start_date'] = $dateStr;
                    } else {
                        $data['start_date'] = $this->normalizeDate($dateStr);
                    }
                    if (!empty($data['start_date'])) break;
                } catch (\Exception $e) {
                    log_message('error', 'Error parsing start date: ' . $e->getMessage());
                    continue;
                }
            }
        }

        // End Date patterns
        $endDatePatterns = [
            // AKP Insurance format: "2025-12-30 to 2025-12-30"
            '/policy\s*period[\s:]*\d{4}-\d{2}-\d{2}\s*to\s*(\d{4}-\d{2}-\d{2})/i',
            // HDFC ERGO format: "To 30 Jul, 2026 Midnight"
            '/to\s+(\d{1,2})\s+([A-Za-z]{3,}),\s+(\d{4})/i',
            // Go Digit format: "To: 25-Nov-2026 23:59:59" or "Period of Policy... To: 25-Nov-2026"
            '/period\s*of\s*policy.*?to[:\s]+(\d{1,2}[\/\-][A-Za-z]{3,}[\/\-]\d{4})/i',
            '/to[:\s]+(\d{1,2}[\/\-][A-Za-z]{3,}[\/\-]\d{4})/i',
            // Standard formats
            '/to\s*(\d{4}-\d{2}-\d{2})/i',
            '/end\s*date[\s:]*(\d{4}-\d{2}-\d{2})/i',
            '/end\s*date[\s:]*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
            '/expiry\s*date[\s:]*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
            '/maturity\s*date[\s:]*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
            '/period\s*of\s*insurance[\s:]*.*?to\s+(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
            '/period\s*of\s*policy[\s:]*.*?to\s+(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i'
        ];
        foreach ($endDatePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                try {
                    $dateStr = trim($matches[1]);
                    // Handle "30 Jul, 2026" format - check if pattern has 3 capture groups
                    if (isset($matches[2]) && isset($matches[3]) && !empty($matches[2]) && !empty($matches[3])) {
                        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                        $month = $this->monthNameToNumber($matches[2]);
                        $year = $matches[3];
                        if ($month && $day && $year) {
                            $dateStr = $year . '-' . $month . '-' . $day;
                        }
                    }
                    // If already in YYYY-MM-DD format, use directly
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                        $data['end_date'] = $dateStr;
                    } else {
                        $data['end_date'] = $this->normalizeDate($dateStr);
                    }
                    if (!empty($data['end_date'])) break;
                } catch (\Exception $e) {
                    log_message('error', 'Error parsing end date: ' . $e->getMessage());
                    continue;
                }
            }
        }
        
        // Policy Mode
        if (preg_match('/policy\s*mode[\s:]*([A-Za-z\s]+?)(?:\s*Policy|$)/i', $text, $matches)) {
            $policyMode = trim($matches[1]);
            $data['policy_mode'] = ucfirst(trim($policyMode));
        }
        
        // Family Group Name
        $familyGroupPatterns = [
            '/family\s*group[\s:]*([A-Z0-9]+)/i',
            '/family\s*group[\s:]*([A-Za-z0-9\s]+?)(?:\s*Nominee|$)/i',
            '/family\s*group[\s:]*([A-Za-z0-9\s]{2,50})/i'
        ];
        foreach ($familyGroupPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $familyGroupName = trim($matches[1]);
                if (strlen($familyGroupName) > 1 && strlen($familyGroupName) < 50) {
                    $data['family_group_name'] = $familyGroupName;
                    break;
                }
            }
        }

        // Premium Amount
        $premiumPatterns = [
            '/premium[\s:]*[₹$]?\s*(\d+(?:[.,]\d+)?)/i',
            '/premium\s*amount[\s:]*[₹$]?\s*(\d+(?:[.,]\d+)?)/i',
            '/annual\s*premium[\s:]*[₹$]?\s*(\d+(?:[.,]\d+)?)/i'
        ];
        foreach ($premiumPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $data['premium'] = str_replace([',', ' '], '', $matches[1]);
                break;
            }
        }

        // Sum Assured
        $sumAssuredPatterns = [
            '/sum\s*assured[\s:]*[₹$]?\s*(\d+(?:[.,]\d+)?)/i',
            '/coverage[\s:]*[₹$]?\s*(\d+(?:[.,]\d+)?)/i',
            '/insured\s*amount[\s:]*[₹$]?\s*(\d+(?:[.,]\d+)?)/i'
        ];
        foreach ($sumAssuredPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $data['sum_assured'] = str_replace([',', ' '], '', $matches[1]);
                break;
            }
        }

        // Tax Amount
        if (preg_match('/tax[\s:]*[₹$]?\s*(\d+(?:[.,]\d+)?)/i', $text, $matches)) {
            $data['tax_amount'] = str_replace([',', ' '], '', $matches[1]);
        }

        // Vehicle Registration Number - Multiple patterns for different PDF formats
        $vehicleRegPatterns = [
            // Go Digit format: "Vehicle Registration No.: GJ05RZ1544"
            '/vehicle\s*registration\s*no\.?[\s:]*([A-Z0-9\s\-]+?)(?:\s*Partner|\s*Policy|\s*Name|$)/i',
            // AKP Insurance format: "Registration Number: Vehicle test"
            '/registration\s*number[\s:]*([A-Za-z0-9\s\-]+?)(?:\s*Vehicle\s*Model|\s*Engine|\s*Chassis|$)/i',
            // Standard formats
            '/(?:registration|reg|reg\.?)\s*(?:no|number|no\.)[\s:]*([A-Z]{2}[\s\-]?\d{1,2}[\s\-]?[A-Z]{1,2}[\s\-]?\d{1,4})/i',
            '/registration\s*no[:\s]+([A-Z]{2}[\-\s]?\d{1,2}[\-\s]?[A-Z]{1,2}[\-\s]?\d{1,4})/i',
            '/vehicle\s*registration\s*no[:\s]+([A-Z]{2}[\-\s]?\d{1,2}[\-\s]?[A-Z]{1,2}[\-\s]?\d{1,4})/i',
            '/reg\.?\s*no[:\s]+([A-Z]{2}[\-\s]?\d{1,2}[\-\s]?[A-Z]{1,2}[\-\s]?\d{1,4})/i',
            // Generic pattern for Indian vehicle registration (e.g., GJ05RZ1544)
            '/([A-Z]{2}\d{1,2}[A-Z]{1,2}\d{1,4})/i'
        ];
        foreach ($vehicleRegPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $regNo = trim($matches[1]);
                // Clean up the registration number
                if (preg_match('/^[A-Z]{2}[\s\-]?\d{1,2}[\s\-]?[A-Z]{1,2}[\s\-]?\d{1,4}$/i', $regNo)) {
                    $regNo = preg_replace('/[\s\-]+/', '', strtoupper($regNo));
                } else {
                    // For non-standard formats, just clean spaces and convert to uppercase
                    $regNo = preg_replace('/[\s\-]+/', '', strtoupper($regNo));
                }
                if (strlen($regNo) >= 1 && strlen($regNo) <= 50) {
                    $data['vehicle_reg_no'] = $regNo;
                    log_message('info', 'Vehicle Registration Number extracted: ' . $regNo);
                    
                    // If policy type is not set, set it to Vehicle
                    if (empty($data['policy_type'])) {
                        $data['policy_type'] = 'Vehicle';
                        log_message('info', 'Policy Type set to Vehicle based on Vehicle Registration Number');
                    }
                    break;
                }
            }
        }

        // Vehicle Model - Multiple patterns (allow numeric models when explicitly labeled as "Vehicle Model:")
        // First, try "Vehicle Model:" pattern which allows numeric values
        if (preg_match('/vehicle\s*model[\s:]*([A-Za-z0-9\s\/\-]+?)(?:\s*Engine\s*Number|\s*Fuel\s*Type|\s*Engine|\s*Year|$)/i', $text, $matches)) {
            $model = trim($matches[1]);
            if (strlen($model) >= 1 && strlen($model) < 100) {
                $data['vehicle_model'] = $model;
                log_message('info', 'Vehicle Model extracted from "Vehicle Model:" label: ' . $model);
            }
        } else {
            // Fallback patterns for other formats (require letter start to avoid policy numbers)
            $vehicleModelPatterns = [
                '/vehicle\s*model[\s:]*([A-Za-z0-9\s\/\-]+?)(?:\s*Engine|$)/i',
                '/model[\s\-]*variant[\s:]*([A-Za-z0-9\s\/\-]+?)(?:\s*Registration|$)/i',
                '/model[\s:]*([A-Za-z][A-Za-z0-9\s\/\-]+?)(?:\s*Registration|\s*Engine|\s*Chassis|$)/i',
                '/make[\s:]*[A-Za-z]+[\s]+model[\s:]*([A-Za-z0-9\s\/\-]+)/i',
                '/(?:^|\s)model[\s\-:]*([A-Za-z][A-Za-z0-9\s\/\-]+?)(?:\s|$)/i'
            ];
            foreach ($vehicleModelPatterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    $model = trim($matches[1]);
                    // Require it to start with a letter to avoid capturing policy numbers
                    if (preg_match('/^[A-Za-z]/', $model) && strlen($model) >= 1 && strlen($model) < 100) {
                        // Remove any trailing policy number patterns
                        $model = preg_replace('/\s*[0-9]{6,}.*$/', '', $model);
                        $model = trim($model);
                        if (strlen($model) >= 1) {
                            $data['vehicle_model'] = $model;
                            log_message('info', 'Vehicle Model extracted: ' . $model);
                            break;
                        }
                    }
                }
            }
        }
        
        // Additional pattern: If we see a pattern like "2301207607005700000Model-VariantS1-AIR"
        // Extract the model part separately
        if (empty($data['vehicle_model']) && preg_match('/([0-9]{6,})([A-Za-z][A-Za-z0-9\s\/\-]+)/', $text, $combinedMatches)) {
            // This suggests policy number and model are combined
            // The model part should start with a letter
            if (isset($combinedMatches[2]) && preg_match('/^[A-Za-z]/', $combinedMatches[2])) {
                $potentialModel = trim($combinedMatches[2]);
                // Clean up - remove any trailing numbers that might be part of policy number
                $potentialModel = preg_replace('/\s*[0-9]{6,}.*$/', '', $potentialModel);
                if (strlen($potentialModel) >= 1 && strlen($potentialModel) < 100) {
                    $data['vehicle_model'] = $potentialModel;
                    log_message('info', 'Vehicle Model extracted from combined pattern: ' . $potentialModel);
                }
            }
        }

        // Engine Number - Multiple patterns (accept shorter values)
        $enginePatterns = [
            '/engine\s*(?:no|number|no\.)[\s:]*([A-Z0-9\s\-]+?)(?:\s*Chassis|\s*Registration|\s*Fuel|$)/i',
            '/engine\s*no[:\s]+([A-Z0-9\s\-]+?)(?:\s*Chassis|\s*Registration|\s*Fuel|$)/i',
            '/engine[:\s]+([A-Z0-9\s\-]+?)(?:\s*Chassis|\s*Registration|\s*Fuel|$)/i',
            '/engine\s*(?:no|number|no\.)[\s:]*([A-Z0-9]{3,20})/i',
            '/engine\s*no[:\s]+([A-Z0-9]{3,20})/i'
        ];
        foreach ($enginePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $engineNo = strtoupper(trim(preg_replace('/[\s\-]+/', '', $matches[1])));
                if (strlen($engineNo) >= 1 && strlen($engineNo) <= 50) {
                    $data['engine_no'] = $engineNo;
                    break;
                }
            }
        }

        // Chassis Number - Multiple patterns (accept shorter values)
        $chassisPatterns = [
            '/chassis\s*(?:no|number|no\.)[\s:]*([A-Z0-9\s\-]+?)(?:\s*Registration|\s*Fuel|\s*Year|$)/i',
            '/chassis\s*no[:\s]+([A-Z0-9\s\-]+?)(?:\s*Registration|\s*Fuel|\s*Year|$)/i',
            '/chassis[:\s]+([A-Z0-9\s\-]+?)(?:\s*Registration|\s*Fuel|\s*Year|$)/i',
            '/chassis\s*(?:no|number|no\.)[\s:]*([A-Z0-9]{3,20})/i',
            '/chassis\s*no[:\s]+([A-Z0-9]{3,20})/i'
        ];
        foreach ($chassisPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $chassisNo = strtoupper(trim(preg_replace('/[\s\-]+/', '', $matches[1])));
                if (strlen($chassisNo) >= 1 && strlen($chassisNo) <= 50) {
                    $data['chassis_no'] = $chassisNo;
                    break;
                }
            }
        }
        
        // Fuel Type
        $fuelTypePatterns = [
            '/fuel\s*type[\s:]*([A-Za-z]+)/i',
            '/fuel[:\s]+([A-Za-z]+)/i'
        ];
        foreach ($fuelTypePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $fuelType = ucfirst(strtolower(trim($matches[1])));
                if (in_array($fuelType, ['Petrol', 'Diesel', 'Electric', 'CNG', 'LPG', 'Hybrid'])) {
                    $data['fuel_type'] = $fuelType;
                    break;
                }
            }
        }
        
        // Year of Manufacture
        if (preg_match('/year\s*of\s*(?:manufacture|mfg|regn)[\s:]*(\d{4})/i', $text, $matches)) {
            $year = intval($matches[1]);
            if ($year >= 1900 && $year <= date('Y') + 1) {
                $data['year_of_manufacture'] = $year;
            }
        }
        
        // Cubic Capacity (CC) - Multiple patterns (must be clearly labeled as CC, not year)
        $ccPatterns = [
            // Most specific: "CC:" followed by number (matches "CC: 100")
            '/cc[\s:]+(\d+)(?:\s*CC|\s*Registration|\s*Date|\s*Gross|\s*Vehicle|$)/i',
            // "CC " (space) followed by number and then a word boundary or end
            '/cc\s+(\d+)(?:\s*CC|\s*Registration|\s*Date|\s*Gross|\s*Vehicle|$)/i',
            // "Engine Capacity (CC):" or similar
            '/engine\s*capacity[\s:]*\(?cc\)?[\s:]*(\d+)/i',
            '/cubic\s*capacity[\s:]*\(?cc\)?[\s:]*(\d+)/i'
        ];
        foreach ($ccPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $cc = intval($matches[1]);
                // Get the full match to check context
                $fullMatch = $matches[0];
                $matchPos = strpos($text, $fullMatch);
                $contextBefore = $matchPos > 0 ? substr($text, max(0, $matchPos - 30), 30) : '';
                $contextAfter = substr($text, $matchPos + strlen($fullMatch), 30);
                $fullContext = strtolower($contextBefore . ' ' . $fullMatch . ' ' . $contextAfter);
                
                // Validate: CC should be between 50 and 10000
                // Exclude if context suggests it's a year (contains "year", "manufacture", "mfg")
                $isYearContext = (stripos($fullContext, 'year') !== false || 
                                 stripos($fullContext, 'manufacture') !== false ||
                                 stripos($fullContext, 'mfg') !== false);
                
                if ($cc >= 50 && $cc <= 10000 && !$isYearContext) {
                    // Additional check: if value is in year range (1900-2030), require stronger CC context
                    if ($cc >= 1900 && $cc <= 2030) {
                        // Only accept if context clearly indicates CC (contains "cc", "capacity", "cubic")
                        $hasCCContext = (stripos($fullContext, 'cc') !== false || 
                                        stripos($fullContext, 'capacity') !== false ||
                                        stripos($fullContext, 'cubic') !== false);
                        if ($hasCCContext) {
                            $data['cc'] = $cc;
                            log_message('info', 'CC extracted (year-like value with CC context): ' . $cc);
                            break;
                        }
                    } else {
                        // Not in year range, safe to use
                        $data['cc'] = $cc;
                        log_message('info', 'CC extracted: ' . $cc);
                        break;
                    }
                }
            }
        }
        
        // Registration Date
        $regDatePatterns = [
            '/registration\s*date[\s:]*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
            '/reg\.?\s*date[\s:]*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i'
        ];
        foreach ($regDatePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $regDate = $this->normalizeDate($matches[1]);
                if ($regDate) {
                    $data['registration_date'] = $regDate;
                    break;
                }
            }
        }
        
        // Gross Vehicle Weight (GVW)
        $gvwPatterns = [
            '/gross\s*vehicle\s*weight[\s\(]*gvw[\s\)]*[\s:]*(\d+(?:[.,]\d+)?)/i',
            '/gvw[\s:]*(\d+(?:[.,]\d+)?)/i',
            '/gross\s*vehicle\s*weight[\s:]*(\d+(?:[.,]\d+)?)/i'
        ];
        foreach ($gvwPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $gvw = str_replace([',', ' '], '', trim($matches[1]));
                if (strlen($gvw) > 0) {
                    $data['gvw'] = $gvw;
                    break;
                }
            }
        }
        
        // No Claim Bonus (NCB)
        $ncbPatterns = [
            '/no\s*claim\s*bonus[\s\(]*ncb[\s\)]*[\s:]*(\d+(?:[.,]\d+)?)/i',
            '/ncb[\s:]*(\d+(?:[.,]\d+)?)/i',
            '/no\s*claim\s*bonus[\s:]*(\d+(?:[.,]\d+)?)/i'
        ];
        foreach ($ncbPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $ncb = str_replace([',', ' '], '', trim($matches[1]));
                if (strlen($ncb) > 0) {
                    $data['ncb'] = $ncb;
                    break;
                }
            }
        }

        // Nominee Name
        if (preg_match('/nominee[\s:]*name[\s:]*([A-Za-z\s]+)/i', $text, $matches)) {
            $data['nominee_name'] = trim($matches[1]);
        }

        // Nominee Contact
        if (preg_match('/nominee[\s:]*contact[\s:]*(\d{10,})/i', $text, $matches)) {
            $data['nominee_contact'] = trim($matches[1]);
        }
        
        // Nominee Details - improved extraction from table format
        // Pattern: Name\tContact\t% Share\nrfddh\t9714064841\t100%
        $nomineePatterns = [
            '/nominee\s*details.*?name[\s\t]+contact[\s\t]+.*?share[\s\n\r]+([A-Za-z\s]+)[\s\t]+(\d{10,})/is',
            '/nominee[\s:]*name[\s\t:]+([A-Za-z\s]+)[\s\t]+(\d{10,})/i',
            '/name[\s\t]+contact[\s\t]+.*?share[\s\n\r]+([A-Za-z\s]+)[\s\t]+(\d{10,})/is'
        ];
        foreach ($nomineePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                if (empty($data['nominee_name'])) {
                    $data['nominee_name'] = trim(preg_replace('/\s+/', ' ', $matches[1]));
                }
                if (empty($data['nominee_contact'])) {
                    $data['nominee_contact'] = trim($matches[2]);
                }
                break;
            }
        }
        
        // Coverage Details - Basic Sum Insured, Premium Amount, Total Sum Insured
        // Pattern: Basic Sum Insured\tPremium Amount\tTotal Sum Insured\n2000.00\t100.00\t2000.00
        $coveragePatterns = [
            '/basic\s*sum\s*insured[\s\t]+premium\s*amount[\s\t]+total\s*sum\s*insured[\s\n\r]+([\d,\.]+)[\s\t]+([\d,\.]+)[\s\t]+([\d,\.]+)/is',
            '/coverage\s*details.*?basic\s*sum[\s\t]+premium[\s\t]+total[\s\n\r]+([\d,\.]+)[\s\t]+([\d,\.]+)[\s\t]+([\d,\.]+)/is',
            '/sum\s*insured[\s\t]+premium[\s\t]+total[\s\n\r]+([\d,\.]+)[\s\t]+([\d,\.]+)[\s\t]+([\d,\.]+)/is'
        ];
        foreach ($coveragePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $basicSum = str_replace([',', ' '], '', trim($matches[1]));
                $premium = str_replace([',', ' '], '', trim($matches[2]));
                $totalSum = str_replace([',', ' '], '', trim($matches[3]));
                
                if (empty($data['sum_assured'])) {
                    $data['sum_assured'] = $basicSum;
                }
                if (empty($data['premium'])) {
                    $data['premium'] = $premium;
                }
                // Total Sum Insured can be used as sum_assured if not already set
                if (empty($data['sum_assured'])) {
                    $data['sum_assured'] = $totalSum;
                }
                break;
            }
        }
        
        // Alternative pattern for coverage details
        if (preg_match('/sum\s*assured[\s:]*[₹$]?\s*(\d+(?:[.,]\d+)?)/i', $text, $matches) && empty($data['sum_assured'])) {
            $data['sum_assured'] = str_replace([',', ' '], '', $matches[1]);
        }
        
        if (preg_match('/premium\s*amount[\s:]*[₹$]?\s*(\d+(?:[.,]\d+)?)/i', $text, $matches) && empty($data['premium'])) {
            $data['premium'] = str_replace([',', ' '], '', $matches[1]);
        }
        
        // Line-by-line parsing as fallback for different PDF formats
        if (is_array($lines) && !empty($lines)) {
            try {
                $this->parseByLines($lines, $data);
            } catch (\Exception $e) {
                log_message('error', 'Error in parseByLines: ' . $e->getMessage());
            }
        }
        
        // Additional flexible patterns for common variations
        if (!empty($originalText)) {
            try {
                $this->parseFlexiblePatterns($originalText, $data);
            } catch (\Exception $e) {
                log_message('error', 'Error in parseFlexiblePatterns: ' . $e->getMessage());
            }
        }
        
        // Clean and validate extracted data
        try {
            $data = $this->cleanExtractedData($data);
        } catch (\Exception $e) {
            log_message('error', 'Error in cleanExtractedData: ' . $e->getMessage());
        }

        return $data;
    }
    
    /**
     * Parse data line by line (for structured PDFs)
     */
    private function parseByLines($lines, &$data)
    {
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Policy Number variations
            if (empty($data['policy_number']) && preg_match('/^(?:policy\s*(?:number|no|#)?[\s:]*)?([A-Z0-9\-]{6,})$/i', $line, $matches)) {
                $data['policy_number'] = trim($matches[1]);
            }
            
            // Name extraction (if line looks like a name)
            if (empty($data['holder_name']) && preg_match('/^([A-Z][a-z]+(?:\s+[A-Z][a-z]+){1,4})$/u', $line)) {
                // Check if it's not a label
                if (!preg_match('/(details|name|address|contact|policy|type|company|branch)/i', $line)) {
                    $data['holder_name'] = $line;
                    $data['user_name'] = $line;
                }
            }
            
            // Key-value pairs (e.g., "Policy Type: Health Insurance")
            if (preg_match('/^([^:]+)[:\s]+(.+)$/i', $line, $matches)) {
                $key = strtolower(trim($matches[1]));
                $value = trim($matches[2]);
                
                // Policy Type
                if (empty($data['policy_type']) && preg_match('/policy\s*type/i', $key)) {
                    $data['policy_type'] = $value;
                }
                
                // Company
                if (empty($data['company_name']) && preg_match('/company|insurer/i', $key)) {
                    $data['company_name'] = preg_replace('/\s*branch\s*$/i', '', $value);
                }
                
                // Branch
                if (empty($data['branch_name']) && preg_match('/branch/i', $key)) {
                    $data['branch_name'] = $value;
                }
                
                // Sub Type
                if (empty($data['sub_type_name']) && preg_match('/sub\s*type/i', $key)) {
                    $data['sub_type_name'] = $value;
                }
                
                // Segment
                if (empty($data['segment_name']) && preg_match('/segment/i', $key)) {
                    $data['segment_name'] = $value;
                }
                
                // Family Group
                if (empty($data['family_group_name']) && preg_match('/family\s*group/i', $key)) {
                    $data['family_group_name'] = $value;
                }
                
                // Policy Mode
                if (empty($data['policy_mode']) && preg_match('/policy\s*mode|payment\s*mode/i', $key)) {
                    $data['policy_mode'] = ucfirst(trim($value));
                }
                
                // Vehicle Registration Number
                if (empty($data['vehicle_reg_no']) && preg_match('/registration\s*number/i', $key)) {
                    $data['vehicle_reg_no'] = trim($value);
                }
                
                // Vehicle Model
                if (empty($data['vehicle_model']) && preg_match('/vehicle\s*model/i', $key)) {
                    $data['vehicle_model'] = trim($value);
                }
                
                // Engine Number
                if ((empty($data['engine_no']) || $data['engine_no'] === 'NUMBER') && preg_match('/engine\s*(?:no|number)/i', $key)) {
                    $data['engine_no'] = trim($value);
                }
                
                // Chassis Number
                if (empty($data['chassis_no']) && preg_match('/chassis\s*(?:no|number)/i', $key)) {
                    $data['chassis_no'] = trim($value);
                }
                
                // Gross Vehicle Weight (GVW)
                if (empty($data['gvw']) && preg_match('/gross\s*vehicle\s*weight|gvw/i', $key)) {
                    $data['gvw'] = preg_replace('/[^0-9.]/', '', $value);
                }
                
                // No Claim Bonus (NCB)
                if (empty($data['ncb']) && preg_match('/no\s*claim\s*bonus|ncb/i', $key)) {
                    $data['ncb'] = preg_replace('/[^0-9.]/', '', $value);
                }
                
                // Year of Manufacture
                if (empty($data['year_of_manufacture']) && preg_match('/year\s*of\s*manufacture|manufacturing\s*year|mfg\s*year/i', $key)) {
                    $year = intval($value);
                    if ($year >= 1900 && $year <= date('Y') + 1) {
                        $data['year_of_manufacture'] = $year;
                    }
                }
                
                // CC (Cubic Capacity) - with context checking to avoid matching years
                if (empty($data['cc']) && preg_match('/cc|cubic\s*capacity|engine\s*cc/i', $key)) {
                    $cc = intval($value);
                    // Validate: CC should be between 50 and 10000, and not a year (1900-2030)
                    // Check context - if key contains "year" or "manufacture", it's likely a year
                    $isYearContext = (stripos($key, 'year') !== false || 
                                     stripos($key, 'manufacture') !== false ||
                                     stripos($key, 'mfg') !== false);
                    
                    if ($cc >= 50 && $cc <= 10000 && !$isYearContext) {
                        // Additional check: if value is in year range (1900-2030), require stronger CC context
                        if ($cc >= 1900 && $cc <= 2030) {
                            // Only accept if context clearly indicates CC (contains "cc", "capacity", "cubic")
                            $hasCCContext = (stripos($key, 'cc') !== false || 
                                            stripos($key, 'capacity') !== false ||
                                            stripos($key, 'cubic') !== false);
                            if ($hasCCContext) {
                                $data['cc'] = $cc;
                                log_message('info', 'CC extracted from line-by-line (year-like value with CC context): ' . $cc);
                            }
                        } else {
                            // Not in year range, safe to use
                            $data['cc'] = $cc;
                            log_message('info', 'CC extracted from line-by-line: ' . $cc);
                        }
                    }
                }
                
                // Dates
                if (empty($data['start_date']) && preg_match('/start|from|commencement|inception/i', $key)) {
                    if (preg_match('/(\d{4}-\d{2}-\d{2}|\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/', $value, $dateMatch)) {
                        $data['start_date'] = $this->normalizeDate($dateMatch[1]);
                    }
                }
                
                if (empty($data['end_date']) && preg_match('/end|to|expiry|maturity/i', $key)) {
                    if (preg_match('/(\d{4}-\d{2}-\d{2}|\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/', $value, $dateMatch)) {
                        $data['end_date'] = $this->normalizeDate($dateMatch[1]);
                    }
                }
                
                // Premium
                if (empty($data['premium']) && preg_match('/premium/i', $key)) {
                    if (preg_match('/(\d+(?:[.,]\d+)?)/', $value, $amountMatch)) {
                        $data['premium'] = str_replace([',', ' '], '', $amountMatch[1]);
                    }
                }
                
                // Sum Assured
                if (empty($data['sum_assured']) && preg_match('/sum\s*assured|coverage|insured\s*amount/i', $key)) {
                    if (preg_match('/(\d+(?:[.,]\d+)?)/', $value, $amountMatch)) {
                        $data['sum_assured'] = str_replace([',', ' '], '', $amountMatch[1]);
                    }
                }
            }
        }
    }
    
    /**
     * Parse with flexible patterns for various PDF formats
     */
    private function parseFlexiblePatterns($text, &$data)
    {
        // More flexible Policy Type extraction
        if (empty($data['policy_type'])) {
            $flexiblePatterns = [
                '/type[:\s]+([A-Za-z0-9\s]{2,30})(?:\s|$|Sub|Segment|Company)/i',
                '/insurance\s+type[:\s]+([A-Za-z0-9\s]{2,30})/i',
                '/product\s+type[:\s]+([A-Za-z0-9\s]{2,30})/i'
            ];
            foreach ($flexiblePatterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    $type = trim($matches[1]);
                    if (strlen($type) > 1 && strlen($type) < 50) {
                        $data['policy_type'] = $type;
                        break;
                    }
                }
            }
        }
        
        // More flexible date extraction
        if (empty($data['start_date']) || empty($data['end_date'])) {
            // Try to find date ranges
            if (preg_match('/(\d{4}-\d{2}-\d{2}|\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})\s*(?:to|-|until)\s*(\d{4}-\d{2}-\d{2}|\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i', $text, $dateRange)) {
                if (empty($data['start_date'])) {
                    $data['start_date'] = $this->normalizeDate($dateRange[1]);
                }
                if (empty($data['end_date'])) {
                    $data['end_date'] = $this->normalizeDate($dateRange[2]);
                }
            }
        }
        
        // Extract amounts with various currency symbols and formats
        if (empty($data['premium'])) {
            $amountPatterns = [
                '/premium[:\s]*[₹$€£]?\s*(\d{1,3}(?:[,\s]\d{3})*(?:\.\d{2})?)/i',
                '/premium[:\s]*(\d+(?:[.,]\d+)?)/i'
            ];
            foreach ($amountPatterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    $data['premium'] = str_replace([',', ' ', '₹', '$', '€', '£'], '', $matches[1]);
                    break;
                }
            }
        }
        
        if (empty($data['sum_assured'])) {
            $amountPatterns = [
                '/sum\s*assured[:\s]*[₹$€£]?\s*(\d{1,3}(?:[,\s]\d{3})*(?:\.\d{2})?)/i',
                '/coverage[:\s]*[₹$€£]?\s*(\d{1,3}(?:[,\s]\d{3})*(?:\.\d{2})?)/i',
                '/insured\s*amount[:\s]*[₹$€£]?\s*(\d{1,3}(?:[,\s]\d{3})*(?:\.\d{2})?)/i'
            ];
            foreach ($amountPatterns as $pattern) {
                if (preg_match($pattern, $text, $matches)) {
                    $data['sum_assured'] = str_replace([',', ' ', '₹', '$', '€', '£'], '', $matches[1]);
                    break;
                }
            }
        }
    }
    
    /**
     * Clean and validate extracted data
     */
    private function cleanExtractedData($data)
    {
        // First, check if policy_number contains vehicle model (e.g., "2301207607005700000Model-VariantS1-AIR")
        if (!empty($data['policy_number']) && is_string($data['policy_number'])) {
            $policyNo = $data['policy_number'];
            
            // Pattern: long numeric string followed by text starting with letter (likely vehicle model)
            // Match: "2301207607005700000Model-VariantS1-AIR" -> policy: "2301207607005700000", model: "Model-VariantS1-AIR"
            if (preg_match('/^([0-9\s\-\/]{6,}?)([A-Za-z][A-Za-z0-9\s\/\-]+)$/', $policyNo, $splitMatches)) {
                $numericPart = preg_replace('/[\s\-\/]/', '', trim($splitMatches[1]));
                $textPart = trim($splitMatches[2]);
                
                // If numeric part is substantial (at least 6 digits) and text part looks like a model
                if (strlen($numericPart) >= 6 && strlen($textPart) >= 2) {
                    $data['policy_number'] = $numericPart;
                    // Only set vehicle_model if it's not already set or if the extracted one is better
                    if (empty($data['vehicle_model']) || strlen($textPart) > strlen($data['vehicle_model'])) {
                        $data['vehicle_model'] = $textPart;
                        log_message('info', 'Separated Policy Number and Vehicle Model - Policy: ' . $numericPart . ', Model: ' . $textPart);
                    }
                }
            }
            
            // Alternative pattern: Remove any trailing text that starts with capital letters (likely model name)
            // This handles cases where model is attached without clear separation
            if (preg_match('/^([0-9\s\-\/]+)([A-Z][A-Za-z0-9\s\/\-]+)$/', $policyNo, $altMatches)) {
                $numericPart = preg_replace('/[\s\-\/]/', '', trim($altMatches[1]));
                $textPart = trim($altMatches[2]);
                
                // Check if text part looks like a model (contains letters, not just numbers)
                if (preg_match('/[A-Za-z]/', $textPart) && strlen($numericPart) >= 6 && strlen($textPart) >= 2) {
                    // Check if text part contains model-related keywords
                    if (preg_match('/(Model|Variant|Make|Brand|Series)/i', $textPart) || 
                        preg_match('/^[A-Z][A-Za-z0-9\-\/]+$/', $textPart)) {
                        $data['policy_number'] = $numericPart;
                        if (empty($data['vehicle_model']) || strlen($textPart) > strlen($data['vehicle_model'])) {
                            $data['vehicle_model'] = $textPart;
                            log_message('info', 'Separated Policy Number and Vehicle Model (alt pattern) - Policy: ' . $numericPart . ', Model: ' . $textPart);
                        }
                    }
                }
            }
        }
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Remove extra whitespace
                $value = preg_replace('/\s+/', ' ', trim($value));
                
                // Remove special characters that shouldn't be in names
                if (in_array($key, ['holder_name', 'user_name', 'nominee_name', 'company_name'])) {
                    $value = preg_replace('/[^A-Za-z0-9\s\.\-]/', '', $value);
                }
                
                // Clean policy type - remove unwanted words
                if ($key === 'policy_type') {
                    $value = preg_replace('/\s*(insurance|policy|type|plan)\s*/i', '', $value);
                    $value = trim($value);
                }
                
                // Clean company name - remove "Branch" suffix
                if ($key === 'company_name') {
                    $value = preg_replace('/\s*branch\s*$/i', '', $value);
                }
                
                // Clean policy number - ensure it doesn't contain vehicle model
                if ($key === 'policy_number') {
                    // Remove any trailing text that looks like a vehicle model
                    $value = preg_replace('/(Model|Vehicle|Variant|Make|Brand|Series)[A-Za-z0-9\s\/\-]*$/i', '', $value);
                    // Remove any text that starts with capital letter after numbers
                    $value = preg_replace('/([0-9]+)([A-Z][A-Za-z]+.*)$/', '$1', $value);
                    $value = trim($value);
                }
                
                // Clean vehicle model - ensure it doesn't contain policy numbers
                if ($key === 'vehicle_model') {
                    // Remove any leading numeric sequences that look like policy numbers
                    $value = preg_replace('/^[0-9\s\-\/]{6,}/', '', $value);
                    $value = trim($value);
                }
                
                // Validate lengths
                if (strlen($value) > 200) {
                    $value = substr($value, 0, 200);
                }
                
                $data[$key] = $value;
            }
        }
        
        return $data;
    }
    
    /**
     * Match extracted names with database IDs
     * 
     * @param array $parsedData Parsed data from PDF
     * @return array Data with IDs added
     */
    private function matchNamesWithIds($parsedData)
    {
        // Policy Type ID - match from insurance_types table by name
        if (!empty($parsedData['policy_type']) && empty($parsedData['policy_type_id'])) {
            try {
                $policyTypeModel = new InsuranceTypeModel();
                $policyTypeName = trim($parsedData['policy_type']);
                
                // Try exact match first (case-insensitive)
                $policyTypeId = $policyTypeModel->findIdByName($policyTypeName, true);
                
                // If exact match fails, try partial match
                if (!$policyTypeId) {
                    $policyTypeId = $policyTypeModel->findIdByName($policyTypeName, false);
                }
                
                if ($policyTypeId) {
                    $parsedData['policy_type_id'] = $policyTypeId;
                    log_message('info', 'Policy Type matched: "' . $policyTypeName . '" -> ID: ' . $policyTypeId);
                } else {
                    log_message('warning', 'Policy Type not found in insurance_types table: "' . $policyTypeName . '"');
                }
            } catch (\Exception $e) {
                log_message('error', 'Error matching Policy Type: ' . $e->getMessage());
            }
        }
        
        // Company ID
        if (!empty($parsedData['company_name']) && empty($parsedData['company_id'])) {
            try {
                $companyModel = new InsuranceCompanyModel();
                $company = $companyModel->like('name', $parsedData['company_name'], 'both')->first();
                if ($company) {
                    $parsedData['company_id'] = is_array($company) ? $company['id'] : $company->id;
                }
            } catch (\Exception $e) {
                log_message('error', 'Error matching Company: ' . $e->getMessage());
            }
        }
        
        // Branch ID (requires company_id first)
        if (!empty($parsedData['branch_name']) && empty($parsedData['branch_id'])) {
            try {
                $branchModel = new BranchModel();
                $query = $branchModel->like('name', $parsedData['branch_name'], 'both');
                
                // If company_id is available, filter by it
                if (!empty($parsedData['company_id'])) {
                    $query->where('company_id', $parsedData['company_id']);
                }
                
                $branch = $query->first();
                if ($branch) {
                    $parsedData['branch_id'] = is_array($branch) ? $branch['id'] : $branch->id;
                }
            } catch (\Exception $e) {
                log_message('error', 'Error matching Branch: ' . $e->getMessage());
            }
        }
        
        // Segment ID (requires policy_type_id first)
        if (!empty($parsedData['segment_name']) && empty($parsedData['segment_id'])) {
            try {
                $segmentModel = new SegmentModel();
                $query = $segmentModel->like('name', $parsedData['segment_name'], 'both');
                
                // If policy_type_id is available, filter by it
                if (!empty($parsedData['policy_type_id'])) {
                    $query->where('insurance_type_id', $parsedData['policy_type_id']);
                }
                
                $segment = $query->first();
                if ($segment) {
                    $parsedData['segment_id'] = is_array($segment) ? $segment['id'] : $segment->id;
                }
            } catch (\Exception $e) {
                log_message('error', 'Error matching Segment: ' . $e->getMessage());
            }
        }
        
        // Sub Type ID (requires policy_type_id first)
        if (!empty($parsedData['sub_type_name']) && empty($parsedData['sub_type_id'])) {
            try {
                $subTypeModel = new InsuranceSubTypeModel();
                $query = $subTypeModel->like('name', $parsedData['sub_type_name'], 'both');
                
                // If policy_type_id is available, filter by it (use type_id column)
                if (!empty($parsedData['policy_type_id'])) {
                    $query->where('type_id', $parsedData['policy_type_id']);
                }
                
                $subType = $query->first();
                if ($subType) {
                    $parsedData['sub_type_id'] = is_array($subType) ? $subType['id'] : $subType->id;
                }
            } catch (\Exception $e) {
                log_message('error', 'Error matching Sub Type: ' . $e->getMessage());
            }
        }
        
        // Family Group ID
        if (!empty($parsedData['family_group_name']) && empty($parsedData['family_group_id'])) {
            try {
                $familyGroupModel = new FamilyGroupModel();
                $familyGroup = $familyGroupModel->like('group_name', $parsedData['family_group_name'], 'both')->first();
                if ($familyGroup) {
                    $parsedData['family_group_id'] = is_array($familyGroup) ? $familyGroup['id'] : $familyGroup->id;
                }
            } catch (\Exception $e) {
                log_message('error', 'Error matching Family Group: ' . $e->getMessage());
            }
        }
        
        // Policy Holder (User) ID - match by name
        if ((!empty($parsedData['holder_name']) || !empty($parsedData['user_name'])) && empty($parsedData['user_id']) && empty($parsedData['holder_id'])) {
            try {
                $userModel = new UserModel();
                $holderName = $parsedData['holder_name'] ?? $parsedData['user_name'];
                
                // Try exact match first
                $user = $userModel->like('name', $holderName, 'both')->where('role', 2)->first();
                if ($user) {
                    $parsedData['user_id'] = is_array($user) ? $user['id'] : $user->id;
                    $parsedData['holder_id'] = $parsedData['user_id'];
                } else {
                    // Try partial match
                    $nameParts = explode(' ', $holderName);
                    if (count($nameParts) > 1) {
                        // Try matching with first and last name
                        $user = $userModel->like('name', $nameParts[0], 'both')
                            ->like('name', $nameParts[count($nameParts) - 1], 'both')
                            ->where('role', 2)
                            ->first();
                        if ($user) {
                            $parsedData['user_id'] = is_array($user) ? $user['id'] : $user->id;
                            $parsedData['holder_id'] = $parsedData['user_id'];
                        }
                    }
                }
            } catch (\Exception $e) {
                log_message('error', 'Error matching User/Holder: ' . $e->getMessage());
            }
        }
        
        return $parsedData;
    }

    /**
     * Normalize date string to Y-m-d format
     * 
     * @param string $dateStr Date string in various formats
     * @return string|null Normalized date or null
     */
    /**
     * Convert month name to number (e.g., "Jul" => "07")
     */
    private function monthNameToNumber($monthName)
    {
        $months = [
            'january' => '01', 'jan' => '01',
            'february' => '02', 'feb' => '02',
            'march' => '03', 'mar' => '03',
            'april' => '04', 'apr' => '04',
            'may' => '05',
            'june' => '06', 'jun' => '06',
            'july' => '07', 'jul' => '07',
            'august' => '08', 'aug' => '08',
            'september' => '09', 'sep' => '09', 'sept' => '09',
            'october' => '10', 'oct' => '10',
            'november' => '11', 'nov' => '11',
            'december' => '12', 'dec' => '12'
        ];
        
        $monthLower = strtolower(trim($monthName));
        return isset($months[$monthLower]) ? $months[$monthLower] : null;
    }
    
    private function normalizeDate($dateStr)
    {
        $dateStr = trim($dateStr);
        
        // Try different date formats
        $formats = ['d/m/Y', 'd-m-Y', 'Y/m/d', 'Y-m-d', 'd/m/y', 'd-m-y', 'd-M-Y', 'd M Y'];
        
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $dateStr);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }
        
        // Try strtotime as fallback
        $timestamp = strtotime($dateStr);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }
        
        return null;
    }
}

