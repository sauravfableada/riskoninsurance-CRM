<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Investment;
use App\Models\InvestmentType;
use App\Models\InvestmentTransactionType;
use App\Models\InvestmentMode;
use App\Models\InvestmentFundName;
use App\Models\InvestmentFolioNumber;
use App\Models\InvestmentHolder;
use App\Models\Broker;
use App\Models\BankMaster;
use App\Models\BondName;
use App\Models\SubBondType;
use App\Models\FundType;
use App\Models\FamilyGroup;
use App\Models\DocumentType;
use App\Models\DocumentName;
use Illuminate\Support\Facades\DB;

class InvestmentController extends Controller
{
    /**
     * Shared static data for dropdowns
     */
    private function getStaticData()
    {
        return [
            'genders' => [
                ['value' => 'Male', 'name' => 'Male'],
                ['value' => 'Female', 'name' => 'Female'],
                ['value' => 'Other', 'name' => 'Other']
            ],
            'accountTypes' => [
                ['value' => 'saving', 'name' => 'Saving'],
                ['value' => 'rd', 'name' => 'Recurring Deposit (RD)'],
                ['value' => 'fd', 'name' => 'Fixed Deposit (FD)'],
                ['value' => 'mis', 'name' => 'Monthly Income Scheme (MIS)'],
                ['value' => 'ppf', 'name' => 'Public Provident Fund (PPF)'],
                ['value' => 'scss', 'name' => 'Senior Citizen Savings Scheme (SCSS)'],
                ['value' => 'nsc', 'name' => 'National Savings Certificate (NSC)']
            ],
            'tenureModes' => [
                ['value' => 'months', 'name' => 'Months'],
                ['value' => 'years', 'name' => 'Years']
            ],
            'interestFrequencies' => [
                ['value' => 'monthly', 'name' => 'Monthly'],
                ['value' => 'quarterly', 'name' => 'Quarterly'],
                ['value' => 'half-yearly', 'name' => 'Half-Yearly'],
                ['value' => 'annually', 'name' => 'Annually'],
                ['value' => 'maturity', 'name' => 'At Maturity']
            ],
            'bondIssuers' => [
                ['value' => 'central', 'name' => 'Central Government'],
                ['value' => 'state', 'name' => 'State Government'],
                ['value' => 'psu', 'name' => 'Public Sector Undertaking (PSU)'],
                ['value' => 'corporate', 'name' => 'Corporate']
            ],
            'taxStatuses' => [
                ['value' => 'taxable', 'name' => 'Taxable'],
                ['value' => 'tax-free', 'name' => 'Tax-Free'],
                ['value' => 'deduction', 'name' => 'Under Section 80C']
            ],
            'callableStatuses' => [
                ['value' => 'callable', 'name' => 'Callable'],
                ['value' => 'non-callable', 'name' => 'Non-Callable']
            ],
            'otherInvestmentTypes' => [
                ['value' => 'gold', 'name' => 'Gold'],
                ['value' => 'real-estate', 'name' => 'Real Estate'],
                ['value' => 'nps', 'name' => 'National Pension System (NPS)'],
                ['value' => 'ulip', 'name' => 'Unit Linked Insurance Plan (ULIP)'],
                ['value' => 'aif', 'name' => 'Alternative Investment Fund (AIF)'],
                ['value' => 'peer-lending', 'name' => 'Peer-to-Peer Lending'],
                ['value' => 'crypto', 'name' => 'Cryptocurrency'],
                ['value' => 'other', 'name' => 'Other']
            ]
        ];
    }

    /**
     * Fetch dynamic data from database
     */
    private function getDynamicData($investment = null)
    {
        $appendIfMissing = function ($collection, $modelClass, $selectedId) {
            if (!$selectedId) return;
            $exists = $collection->contains('id', $selectedId);
            if (!$exists) {
                $query = $modelClass::query();
                if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($modelClass))) {
                    $query->withTrashed();
                }
                $item = $query->find($selectedId);
                if ($item) {
                    $collection->push($item);
                }
            }
        };

        $banks = BankMaster::active()->get();
        $bonds = BondName::active()->get();
        $subBondTypes = SubBondType::active()->get();
        $modes = InvestmentMode::active()->get();
        $folios = InvestmentFolioNumber::active()->get();
        $fundNames = InvestmentFundName::active()->get();
        $fundTypes = FundType::active()->get();
        $brokers = Broker::active()->get();

        if ($investment) {
            $appendIfMissing($banks, BankMaster::class, $investment->bank_name_id);
            $appendIfMissing($banks, BankMaster::class, $investment->mf_payment_bank_id);
            $appendIfMissing($banks, BankMaster::class, $investment->bank_payment_bank_id);
            $appendIfMissing($banks, BankMaster::class, $investment->po_payment_bank_id);
            $appendIfMissing($banks, BankMaster::class, $investment->bond_payment_bank_id);
            $appendIfMissing($banks, BankMaster::class, $investment->other_payment_bank_id);
            $appendIfMissing($bonds, BondName::class, $investment->bond_name);
            $appendIfMissing($subBondTypes, SubBondType::class, $investment->bond_sub_type_id);
            $appendIfMissing($modes, InvestmentMode::class, $investment->mf_mode_id);
            $appendIfMissing($modes, InvestmentMode::class, $investment->bank_mode_id);
            $appendIfMissing($folios, InvestmentFolioNumber::class, $investment->mf_folio_number_id);
            $appendIfMissing($fundNames, InvestmentFundName::class, $investment->mf_fund_name_id);
            $appendIfMissing($fundTypes, FundType::class, $investment->mf_fund_type_id);
            $appendIfMissing($brokers, Broker::class, $investment->mf_broker_id);
        }

        return [
            'familyGroups' => FamilyGroup::select('id', 'group_name')->get()->toArray(),
            'investmentTypes' => InvestmentType::all()->toArray(),
            'poTransactionTypes' => InvestmentTransactionType::byCategory('post_office')->get()->toArray(),
            'bondTransactionTypes' => InvestmentTransactionType::byCategory('govt_bonds')->get()->toArray(),
            'otherTransactionTypes' => InvestmentTransactionType::byCategory('other')->get()->toArray(),
            'banks' => $banks->toArray(),
            'bonds' => $bonds->toArray(),
            'subBondTypes' => $subBondTypes->toArray(),
            'documentTypes' => DocumentType::active()->orderBy('document_type', 'ASC')->get()->toArray(),
            'documentNames' => DocumentName::active()->orderBy('document_name', 'ASC')->get()->toArray(),
            'modes' => $modes->toArray(),
            'folios' => $folios->toArray(),
            'fundNames' => $fundNames->toArray(),
            'fundTypes' => $fundTypes->toArray(),
            'brokers' => $brokers->toArray(),
        ];
    }

    public function index($type = null)
    {
        $currentType = $type;
        $investments = Investment::when($type, function($query) use ($type) {
            return $query->where('investment_type_main', $type);
        })->latest()->get();
        $familyGroups = DB::table('family_groups')->pluck('group_name', 'id');
        
        return view('investment.manage_investment', compact('currentType', 'investments', 'familyGroups'));
    }

    public function create()
    {
        $data = array_merge($this->getStaticData(), $this->getDynamicData());
        return view('investment.add_investment', $data);
    }

    public function store(Request $request)
    {
        $validated = $this->validateInvestment($request);
        $validated['created_by'] = auth()->id() ?? 1;

        $types = ['mf', 'bank', 'po', 'bond', 'other'];
        foreach ($types as $type) {
            $field = $type . '_document';
            if ($request->hasFile($field)) {
                $validated[$field] = $request->file($field)->store('investments', 'public');
            }
        }

        $investment = Investment::create($validated);
        $this->storeExtraDocuments($request, $investment->id);

        return redirect()->route('investment.index')->with('success', 'Investment added successfully');
    }

    public function edit($id)
    {
        $investment = Investment::findOrFail($id);
        $data = array_merge($this->getStaticData(), $this->getDynamicData($investment));
        $data['investment'] = $investment->toArray();
        $data['extra_documents'] = $investment->extraDocuments()->get()->toArray();
        
        return view('investment.add_investment', $data);
    }

    public function update(Request $request, $id)
    {
        $investment = Investment::findOrFail($id);
        $validated = $this->validateInvestment($request);

        $types = ['mf', 'bank', 'po', 'bond', 'other'];
        foreach ($types as $type) {
            $field = $type . '_document';
            if ($request->hasFile($field)) {
                $validated[$field] = $request->file($field)->store('investments', 'public');
            }
        }

        $investment->update($validated);
        $this->storeExtraDocuments($request, $investment->id);

        return redirect()->route('investment.index')->with('success', 'Investment updated successfully');
    }

    public function show($id)
    {
        $investment = Investment::findOrFail($id);
        $familyGroupName = DB::table('family_groups')->where('id', $investment->family_group_id)->value('group_name');
        $holderName = DB::table('investment_holders')->where('id', $investment->investment_holder_id)->value('holder_name');
        
        $invArray = $investment->toArray();

        // Populate descriptive name fields for view_investment template
        // Mutual Fund names
        $invArray['mf_transaction_type_name'] = $investment->mf_transaction_type_id ? InvestmentType::withTrashed()->find($investment->mf_transaction_type_id)?->type_name : null;
        $invArray['mf_folio_number_name'] = $investment->mf_folio_number_id ? InvestmentFolioNumber::find($investment->mf_folio_number_id)?->folio_number : null;
        $invArray['mf_investment_type_name'] = $investment->mf_investment_type_id ? InvestmentType::withTrashed()->find($investment->mf_investment_type_id)?->type_name : null;
        $invArray['mf_fund_name_name'] = $investment->mf_fund_name_id ? InvestmentFundName::find($investment->mf_fund_name_id)?->fund_name : null;
        $invArray['mf_fund_type_name'] = $investment->mf_fund_type_id ? FundType::withTrashed()->find($investment->mf_fund_type_id)?->name : null;
        $invArray['mf_broker_name'] = $investment->mf_broker_id ? Broker::find($investment->mf_broker_id)?->broker_name : null;
        $invArray['mf_mode_name'] = $investment->mf_mode_id ? InvestmentMode::find($investment->mf_mode_id)?->mode_name : null;
        $invArray['mf_payment_bank_name'] = $investment->mf_payment_bank_id ? BankMaster::withTrashed()->find($investment->mf_payment_bank_id)?->name : null;

        // Bank names
        $invArray['bank_name_text'] = $investment->bank_name_id ? BankMaster::withTrashed()->find($investment->bank_name_id)?->name : null;
        $invArray['bank_mode_name'] = $investment->bank_mode_id ? InvestmentMode::find($investment->bank_mode_id)?->mode_name : null;
        $invArray['bank_payment_bank_name'] = $investment->bank_payment_bank_id ? BankMaster::withTrashed()->find($investment->bank_payment_bank_id)?->name : null;

        // Post Office names
        $invArray['po_payment_bank_name'] = $investment->po_payment_bank_id ? BankMaster::withTrashed()->find($investment->po_payment_bank_id)?->name : null;

        // Bond names
        $invArray['bond_payment_bank_name'] = $investment->bond_payment_bank_id ? BankMaster::withTrashed()->find($investment->bond_payment_bank_id)?->name : null;

        // Other names
        $invArray['other_investment_type_name'] = $investment->other_investment_type_id ? InvestmentType::withTrashed()->find($investment->other_investment_type_id)?->type_name : null;
        $invArray['other_payment_bank_name'] = $investment->other_payment_bank_id ? BankMaster::withTrashed()->find($investment->other_payment_bank_id)?->name : null;

        // Extra documents names
        $extraDocs = $investment->extraDocuments()->get()->map(function($doc) {
            $docArr = $doc->toArray();
            $docArr['document_type_name'] = $doc->document_type_id ? DocumentType::find($doc->document_type_id)?->document_type : null;
            $docArr['document_name_text'] = $doc->document_name_id ? DocumentName::find($doc->document_name_id)?->document_name : null;
            return $docArr;
        })->toArray();
        $invArray['documents'] = $extraDocs;
        
        return view('investment.view_investment', [
            'investment' => $invArray,
            'familyGroupName' => $familyGroupName,
            'holderName' => $holderName
        ]);
    }

    public function destroy($id)
    {
        Investment::findOrFail($id)->delete();
        return redirect()->route('investment.index')->with('success', 'Investment deleted successfully');
    }

    private function validateInvestment(Request $request)
    {
        $rules = [
            'transaction_date' => 'required|date',
            'document_number' => 'nullable|string|max:255',
            'family_group_id' => 'required|integer',
            'investment_holder_id' => 'required|integer',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'area' => 'nullable|string|max:255',
            'mobile_number' => 'required|string|max:20',
            'email_address' => 'nullable|email|max:255',
            'gender' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'family_head_name' => 'nullable|string|max:255',
            'relation_with_head' => 'nullable|string|max:255',
            'investment_type_main' => 'required|in:mutual_fund,bank,post_office,govt_bonds,other',
        ];

        $optionalFields = [
            'mf_transaction_type_id', 'mf_folio_number_id', 'mf_investment_type_id', 'mf_fund_name_id', 'mf_fund_type_id', 
            'mf_broker_id', 'mf_mode_id', 'mf_start_date', 'mf_units_purchase', 'mf_unit_price', 'mf_investment_amount', 
            'mf_total_balance_unit', 'mf_total_fund_value', 'mf_total_count', 'mf_total_investment_balance', 'mf_payment_bank_id', 'mf_remarks', 
            'bank_transaction_type_id', 'bank_account_type', 'bank_name_id', 'bank_investment_product_id', 'bank_customer_no', 'bank_certificate_no', 
            'bank_investment_amount', 'bank_total_invested_amount', 'bank_mode_id', 'bank_tenure_mode', 'bank_tenure', 'bank_deposit_date', 'bank_mature_date', 
            'bank_interest_rate', 'bank_interest_amount', 'bank_interest_frequency', 'bank_payment_bank_id', 'bank_paid_by_client', 'bank_paid_by_agent', 'bank_remarks', 
            'po_transaction_type_id', 'po_account_type', 'po_certificate_account_no', 'po_customer_name', 'po_investment_amount', 'po_total_invested_amount', 
            'po_mode', 'po_tenure_mode', 'po_tenure', 'po_opening_date', 'po_mature_date', 'po_interest_rate', 'po_interest_amount', 'po_interest_frequency', 
            'po_payment_bank_id', 'po_paid_by_client', 'po_paid_by_agent', 'po_remarks', 
            'bond_transaction_type_id', 'bond_id_isin', 'bond_name', 'bond_issuer', 'bond_type', 'bond_sub_type_id', 'bond_issue_date', 'bond_face_value', 
            'bond_purchase_date', 'bond_purchase_price', 'bond_quantity', 'bond_tax_status', 'bond_callable_status', 'bond_maturity_date', 'bond_total_return', 
            'bond_ytm', 'bond_coupon_rate', 'bond_coupon_frequency', 'bond_credit_rating', 'bond_investment_amount', 'bond_total_invested_amount', 
            'bond_payment_bank_id', 'bond_paid_by_client', 'bond_paid_by_agent', 'bond_remarks', 
            'other_transaction_type_id', 'other_investment_to_place', 'other_invested_party_name', 'other_investment_type_id', 'other_document_no', 
            'other_mode', 'other_tenure_mode', 'other_tenure', 'other_deposit_date', 'other_mature_date', 'other_next_due_date', 'other_investment_amount', 
            'other_interest_rate', 'other_payment_bank_id', 'other_paid_by_client', 'other_paid_by_agent', 'other_remarks',
        ];

        foreach ($optionalFields as $field) {
            $rules[$field] = 'nullable';
        }

        $rules['mf_document'] = 'nullable|file';
        $rules['bank_document'] = 'nullable|file';
        $rules['po_document'] = 'nullable|file';
        $rules['bond_document'] = 'nullable|file';
        $rules['other_document'] = 'nullable|file';

        return $request->validate($rules);
    }

    private function storeExtraDocuments(Request $request, $investmentId)
    {
        $docIds = $request->input('doc_id', []);
        $docTypes = $request->input('doc_type', []);
        $docNames = $request->input('doc_name', []);
        $docNos = $request->input('doc_no', []);
        $docFiles = $request->file('doc_file', []);

        $submittedIds = array_filter($docIds, function($id) { return !empty($id); });
        if ($investmentId) {
            \App\Models\InvestmentExtraDocument::where('investment_id', $investmentId)
                ->whereNotIn('id', $submittedIds)
                ->delete();
        }

        foreach ($docTypes as $index => $docType) {
            $docId = $docIds[$index] ?? null;
            $docName = $docNames[$index] ?? null;
            $docNo = $docNos[$index] ?? null;
            $file = $docFiles[$index] ?? null;

            if (empty($docType) && empty($docName) && empty($file) && empty($docId)) {
                continue;
            }

            $docData = [
                'investment_id' => $investmentId,
                'document_type_id' => $docType ?: null,
                'document_name_id' => $docName ?: null,
                'document_nos' => $docNo ?: null,
            ];

            if ($file && $file->isValid()) {
                $docData['document_file'] = $file->store('investments/extra', 'public');
            }

            if (!empty($docId)) {
                \App\Models\InvestmentExtraDocument::where('id', $docId)->update($docData);
            } else {
                \App\Models\InvestmentExtraDocument::create($docData);
            }
        }
    }
}
