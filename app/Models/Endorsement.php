<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Endorsement extends Model
{
    use HasFactory;

    protected $table = 'endorsements';

    protected $fillable = [
        'transaction_date',
        'family_group_id',
        'policy_holder_id',
        'policy_id',
        'endorsement_type',
        'vehicle_existing_data',
        'vehicle_reg_no',
        'vehicle_reg_no_transaction_type',
        'vehicle_reg_no_to_be_endorse',
        'vehicle_reg_no_remarks',
        'vehicle_reg_no_endorse_effect',
        'vehicle_name',
        'vehicle_name_transaction_type',
        'vehicle_name_to_be_endorse',
        'vehicle_name_remarks',
        'vehicle_name_endorse_effect',
        'engine_no',
        'engine_no_transaction_type',
        'engine_no_to_be_endorse',
        'engine_no_remarks',
        'engine_no_endorse_effect',
        'chassis_no',
        'chassis_no_transaction_type',
        'chassis_no_to_be_endorse',
        'chassis_no_remarks',
        'chassis_no_endorse_effect',
        'vehicle_brand',
        'vehicle_brand_transaction_type',
        'vehicle_brand_to_be_endorse',
        'vehicle_brand_remarks',
        'vehicle_brand_endorse_effect',
        'vehicle_fuel_type',
        'vehicle_fuel_type_transaction_type',
        'vehicle_fuel_type_to_be_endorse',
        'vehicle_fuel_type_remarks',
        'vehicle_fuel_type_endorse_effect',
        'register_date',
        'register_date_transaction_type',
        'register_date_to_be_endorse',
        'register_date_remarks',
        'register_date_endorse_effect',
        'year_of_manufacture',
        'year_of_manufacture_transaction_type',
        'year_of_manufacture_to_be_endorse',
        'year_of_manufacture_remarks',
        'year_of_manufacture_endorse_effect',
        'rto',
        'rto_transaction_type',
        'rto_to_be_endorse',
        'rto_remarks',
        'rto_endorse_effect',
        'cc',
        'cc_transaction_type',
        'cc_to_be_endorse',
        'cc_remarks',
        'cc_endorse_effect',
        'gvw',
        'gvw_transaction_type',
        'gvw_to_be_endorse',
        'gvw_remarks',
        'gvw_endorse_effect',
        'ncb',
        'ncb_transaction_type',
        'ncb_to_be_endorse',
        'ncb_remarks',
        'ncb_endorse_effect',
        'vehicle_hp',
        'vehicle_hp_transaction_type',
        'vehicle_hp_to_be_endorse',
        'vehicle_hp_remarks',
        'vehicle_hp_endorse_effect',
        'paid_premium_amount',
        'tpa_premium_amount',
        'tax_amount',
        'total_amount',
        'commissionable_amount',
        'endorsement_effective_date',
        'reference_no',
        'endorsement_letter',
        'remark_reason',
        'first_name',
        'first_name_transaction_type',
        'first_name_to_be_endorse',
        'first_name_remarks',
        'first_name_endorse_effect',
        'middle_name',
        'middle_name_transaction_type',
        'middle_name_to_be_endorse',
        'middle_name_remarks',
        'middle_name_endorse_effect',
        'last_name',
        'last_name_transaction_type',
        'last_name_to_be_endorse',
        'last_name_remarks',
        'last_name_endorse_effect',
        'dob',
        'dob_transaction_type',
        'dob_to_be_endorse',
        'dob_remarks',
        'dob_endorse_effect',
        'relation_with_head',
        'relation_with_head_transaction_type',
        'relation_with_head_to_be_endorse',
        'relation_with_head_remarks',
        'relation_with_head_endorse_effect',
        'gender',
        'gender_transaction_type',
        'gender_to_be_endorse',
        'gender_remarks',
        'gender_endorse_effect',
        'address_1',
        'address_1_transaction_type',
        'address_1_to_be_endorse',
        'address_1_remarks',
        'address_1_endorse_effect',
        'address_2',
        'address_2_transaction_type',
        'address_2_to_be_endorse',
        'address_2_remarks',
        'address_2_endorse_effect',
        'city',
        'city_transaction_type',
        'city_to_be_endorse',
        'city_remarks',
        'city_endorse_effect',
        'area',
        'area_transaction_type',
        'area_to_be_endorse',
        'area_remarks',
        'area_endorse_effect',
        'mobile_number',
        'mobile_number_transaction_type',
        'mobile_number_to_be_endorse',
        'mobile_number_remarks',
        'mobile_number_endorse_effect',
        'whatsapp_number',
        'whatsapp_number_transaction_type',
        'whatsapp_number_to_be_endorse',
        'whatsapp_number_remarks',
        'whatsapp_number_endorse_effect',
        'email',
        'email_transaction_type',
        'email_to_be_endorse',
        'email_remarks',
        'email_endorse_effect',
        'aadhaar_card',
        'aadhaar_card_transaction_type',
        'aadhaar_card_to_be_endorse',
        'aadhaar_card_remarks',
        'aadhaar_card_endorse_effect',
        'pan_card',
        'pan_card_transaction_type',
        'pan_card_to_be_endorse',
        'pan_card_remarks',
        'pan_card_endorse_effect',
        'gst_number',
        'gst_number_transaction_type',
        'gst_number_to_be_endorse',
        'gst_number_remarks',
        'gst_number_endorse_effect',
        'general_policy_id',
        'life_policy_id',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'vehicle_existing_data' => 'array',
        'transaction_date' => 'date',
        'endorsement_effective_date' => 'date',
    ];

    public function familyGroup()
    {
        return $this->belongsTo(FamilyGroup::class, 'family_group_id');
    }

    public function policyHolder()
    {
        return $this->belongsTo(Customer::class, 'policy_holder_id');
    }

    public function policy()
    {
        return $this->belongsTo(Policy::class, 'policy_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
