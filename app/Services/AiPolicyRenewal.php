<?php

namespace App\Services;

use App\Models\{Policy, User, CompanyMaster, PolicyPdfHistory, PolicyMember, PolicyAddon};
use Illuminate\Support\Facades\{DB, Schema, Storage, Validator};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AiPolicyRenewal
{
    public const FIELDS = ['policy_type_id'=>'policy_type_id','company_id'=>'company_id','sub_type_id'=>'policy_sub_type_id','segment_id'=>'segment_id','branch_id'=>'branch_id','start_date'=>'start_date','end_date'=>'end_date','premium'=>'premium_amount','tax_amount'=>'tax_amount','sum_assured'=>'sum_assured','vehicle_reg_no'=>'vehicle_registration_number','vehicle_make'=>'vehicle_make','vehicle_model'=>'vehicle_model','variant'=>'variant','engine_no'=>'engine_number','chassis_no'=>'chassis_number','fuel_type'=>'fuel_type','cc'=>'engine_capacity','gvw'=>'gross_vehicle_weight','ncb'=>'no_claim_bonus','year_of_manufacture'=>'year_of_manufacture','vehicle_idv'=>'vehicle_idv','cng_lpg_unit'=>'cng_lpg_unit','policy_mode'=>'policy_mode','payment_mode'=>'payment_type','payment_date'=>'payment_date','amount'=>'payment_amount','nominee_name'=>'nominee_name','nominee_contact'=>'nominee_contact','remarks'=>'remarks','waiting_period'=>'waiting_period','room_rent_limit'=>'room_rent_limit','deductible'=>'deductible','maturity_date'=>'maturity_date','broker_id'=>'broker_id','agent_name'=>'agent_name','agent_code'=>'agent_code','agent_id'=>'agent_id','broker_contact'=>'broker_contact','insurer_claim_email'=>'insurer_claim_email','product_name'=>'product_name','business_type'=>'business_type','reference'=>'reference','coverage_type'=>'coverage_type','premium_own_damage'=>'premium_own_damage','premium_liability'=>'premium_liability','addons_premium'=>'addons_premium','net_premium'=>'net_premium','seating_capacity'=>'seating_capacity','vehicle_color'=>'vehicle_color','vehicle_class'=>'vehicle_class','tp_end_date'=>'tp_end_date','financier_name'=>'financier_name','financier_branch'=>'financier_branch','agreement_number'=>'agreement_number'];

    public function authorize(Policy $source, User $user): void
    {
        abort_unless($user->isAdmin() || $user->hasMatrixPermission('edit_policies'), 403);
        abort_unless(($user->isAdmin() && $user->type !== 'sub_agents') || (int)$source->user_id === (int)$user->id || (int)$source->created_by === (int)$user->id, 403);
        abort_if($source->deleted_at !== null, 404);
    }

    public function validate(array $input): array
    {
        foreach (['covered_members','addon_covers'] as $key) if (is_string($input[$key] ?? null)) $input[$key] = json_decode($input[$key], true) ?? $input[$key];
        $rules = ['policy_number'=>['required','string','max:150'], 'renewal_holder_name'=>['required','string','max:255'],
            'company_id'=>['required','integer','exists:company_master,id,deleted_at,NULL'], 'policy_type_id'=>['required','integer','exists:insurance_policy_types,id'],
            'start_date'=>['required','date_format:Y-m-d'], 'end_date'=>['required','date_format:Y-m-d','after:start_date'], 'premium'=>['required','numeric','min:0'],
            'covered_members'=>['required','array','max:100'], 'covered_members.*.name'=>['required','string','max:255'],
            'covered_members.*.relation'=>['nullable','string','max:100'], 'covered_members.*.dob'=>['nullable','date_format:Y-m-d','before_or_equal:today'], 'covered_members.*.gender'=>['nullable','string','max:30'],
            'addon_covers'=>['nullable','array','max:100'], 'addon_covers.*.cover_name'=>['required','string','max:255'], 'addon_covers.*.premium_amount'=>['nullable','numeric','min:0']];
        // Empty member arrays are valid for categories whose cover is on the asset, rather than named persons.
        $rules['covered_members'][0] = 'present';
        foreach (self::FIELDS as $key=>$column) if (!isset($rules[$key])) $rules[$key] = ['nullable','string','max:5000'];
        foreach (['tax_amount','sum_assured','vehicle_idv','amount','premium_own_damage','premium_liability','addons_premium','net_premium'] as $key) $rules[$key] = ['nullable','numeric','min:0'];
        foreach (['payment_date','maturity_date','tp_end_date'] as $key) $rules[$key] = ['nullable','date_format:Y-m-d'];
        foreach (['sub_type_id'=>'insurance_policy_sub_types','segment_id'=>'segments','branch_id'=>'branches','broker_id'=>'brokers'] as $key=>$table) {
            $rules[$key] = ['nullable','integer',\Illuminate\Validation\Rule::exists($table,'id')];
        }
        $rules['branch_id'] = ['nullable','integer',\Illuminate\Validation\Rule::exists('branches','id')->where('company_id',$input['company_id'] ?? null)];
        $rules['sub_type_id'] = ['nullable','integer',\Illuminate\Validation\Rule::exists('insurance_policy_sub_types','id')->where('insurance_policy_types_id',$input['policy_type_id'] ?? null)];
        $rules['segment_id'] = ['nullable','integer',\Illuminate\Validation\Rule::exists('segments','id')->where('insurance_type_id',$input['policy_type_id'] ?? null)];
        $rules['clear_fields'] = ['nullable','array'];
        $rules['clear_fields.*'] = ['string',\Illuminate\Validation\Rule::in(array_diff(array_keys(self::FIELDS),['company_id','policy_type_id','start_date','end_date','premium']))];
        return Validator::make($input, $rules)->validate();
    }

    public function save(Policy $source, User $user, array $input, array $audit, string $pdfPath): Policy
    {
        $this->authorize($source, $user);
        $values = $this->validate($input);
        if (!Schema::hasColumn('policies','renewal_document_hash')) throw ValidationException::withMessages(['renewal'=> 'Run the AI renewal integrity migration before saving renewals.']);
        if (!is_file($pdfPath)) throw ValidationException::withMessages(['upload_id'=>'Renewal PDF is missing. Re-upload it.']);
        $hash = hash_file('sha256',$pdfPath);
        $stored = null;
        try {
            return DB::transaction(function () use ($source,$user,$values,$input,$audit,$pdfPath,$hash,&$stored) {
                CompanyMaster::whereKey($values['company_id'])->lockForUpdate()->firstOrFail();
                $locked = Policy::whereKey($source->id)->lockForUpdate()->firstOrFail();
                $locked->load(['holder','company']);
                $this->authorize($locked,$user);
                $oldFile = (string)$locked->policy_pdf;
                $oldDisk = str_starts_with($oldFile,'policy_pdfs/') ? Storage::disk('local') : Storage::disk('public');
                $oldPath = str_starts_with($oldFile,'policy_pdfs/') ? $oldFile : 'policies/'.basename($oldFile);
                if ($oldFile && $oldDisk->exists($oldPath) && hash_file('sha256',$oldDisk->path($oldPath)) === $hash) throw ValidationException::withMessages(['duplicate'=>'This is the previous policy PDF, not a new renewal document.']);
                if (Policy::where('renewal_document_hash',$hash)->exists() || app(PolicyDuplicateCheck::class)->find(['policy_number_no'=>$values['policy_number']]+$values)) {
                    throw ValidationException::withMessages(['duplicate'=>'This policy term or renewal PDF already exists. No copy was created.']);
                }
                if (Policy::where('renewal_of_policy_id',$locked->id)->whereNull('deleted_at')->exists()) throw ValidationException::withMessages(['renewal'=>'This term has already been renewed. Open the latest term before renewing again.']);
                $reviewService = app(PolicyRenewalReview::class);
                if (($audit['renewal_source_fingerprint'] ?? '') !== $reviewService->fingerprint($locked)) throw ValidationException::withMessages(['renewal'=>'The source policy changed after extraction. Reload and review it again.']);
                if ((int)($audit['renewal_source_id'] ?? 0) !== (int)$locked->id || (int)($audit['user_id'] ?? 0) !== (int)$user->id) abort(403);
                $review = $reviewService->compare($locked,$audit['data'],$values);
                if ($review['blocked']) throw ValidationException::withMessages(['identity'=>$review['blocked']]);
                Validator::make($input, ['renewal_confirmed'=>['accepted']])->validate();
                // Preserve the old row and all its relationships; a renewal is a new linked term.
                $new = $locked->replicate(['policy_pdf','renewal_document_hash','renewal_review']);
                $columns = array_flip(Schema::getColumnListing('policies'));
                foreach (self::FIELDS as $key=>$column) if (isset($columns[$column]) && array_key_exists($key,$values) && $values[$key] !== null && $values[$key] !== '') $new->$column = $values[$key];
                foreach ($values['clear_fields'] ?? [] as $key) if (isset($columns[self::FIELDS[$key]])) $new->{self::FIELDS[$key]} = null;
                if (isset($columns['payment_mode'])) {
                    if (in_array('payment_mode',$values['clear_fields'] ?? [],true)) $new->payment_mode=null;
                    elseif (!empty($values['payment_mode'])) $new->payment_mode=$values['payment_mode'];
                }
                if (isset($columns['sub_type_id']) && array_key_exists('sub_type_id',$values)) $new->sub_type_id = $values['sub_type_id'];
                if ((int)$values['company_id'] !== (int)$locked->company_id) $new->branch_id = $values['branch_id'] ?? null;
                if ((int)$values['policy_type_id'] !== (int)$locked->policy_type_id) {
                    foreach (['policy_sub_type_id','sub_type_id','segment_id'] as $key) if (isset($columns[$key])) $new->$key = $values[$key === 'policy_sub_type_id' ? 'sub_type_id' : $key] ?? null;
                }
                $new->policy_number = 'POL-'.now()->format('Ymd').'-'.strtoupper(Str::random(10));
                $new->policy_number_no = trim($values['policy_number']);
                $new->holder_id = $locked->holder_id;
                $new->family_group_id = $locked->family_group_id;
                $new->renewal_of_policy_id = $locked->id;
                if (isset($columns['renew_id'])) $new->renew_id = $locked->id;
                if (isset($columns['is_renew'])) $new->is_renew = 1;
                if (isset($columns['renew_status'])) $new->renew_status = 'renewed';
                if (isset($columns['status'])) $new->status = 'active';
                $new->created_by = $user->id; $new->modified_by = $user->id;
                $new->renewal_document_hash = $hash;
                $new->renewal_review = json_encode(['upload_id'=>$audit['upload_id'],'user_id'=>$user->id,'confirmed_at'=>now()->toIso8601String(), 'review'=>$review,'extracted'=>$audit['data'],'reviewed'=>$values],JSON_THROW_ON_ERROR);
                $new->save();
                $stored = "policy_pdfs/{$new->id}/renewal-{$hash}.pdf";
                if (!Storage::disk('local')->put($stored,file_get_contents($pdfPath))) throw new \RuntimeException('Could not store renewal PDF.');
                $new->update(['policy_pdf'=>$stored]);
                PolicyPdfHistory::create(['policy_id'=>$new->id,'file_name'=>$stored,'original_file_name'=>'renewed-policy.pdf','document_type'=>'renewal','label'=>'Renewed Policy PDF (AI reviewed)','document_date'=>$values['start_date'],'uploaded_by'=>$user->id]);
                if ($values['covered_members'] && !Schema::hasTable('policy_members')) throw ValidationException::withMessages(['members'=>'Run the policy_members migration before saving covered members.']);
                foreach ($values['covered_members'] as $member) PolicyMember::create(['policy_id'=>$new->id]+\Illuminate\Support\Arr::only($member,['name','relation','dob','gender']));
                if (!empty($values['addon_covers']) && !Schema::hasTable('policy_addons')) throw ValidationException::withMessages(['addons'=>'Run the policy_addons migration before saving add-on covers.']);
                foreach ($values['addon_covers'] ?? [] as $addon) PolicyAddon::create(['policy_id'=>$new->id,'cover_name'=>$addon['cover_name'],'premium_amount'=>($addon['premium_amount'] ?? null) ?: null]);
                if (Schema::hasTable('vehicle_policies')) {
                    $vehicle=\App\Models\VehiclePolicyModel::where('policy_id',$locked->id)->first();
                    if ($vehicle) {
                        $copy=$vehicle->replicate();$copy->policy_id=$new->id;
                        foreach (['vehicle_reg_no','vehicle_model','engine_no','chassis_no','fuel_type','cc','gvw','ncb','year_of_manufacture'] as $key) {
                            if (isset($values[$key]) && $values[$key] !== '') $copy->$key=$values[$key];
                            if (in_array($key,$values['clear_fields'] ?? [],true)) $copy->$key=null;
                        }
                        $copy->save();
                    }
                }
                return $new;
            });
        } catch (\Throwable $error) {
            if ($stored) Storage::disk('local')->delete($stored);
            throw $error;
        }
    }
}
