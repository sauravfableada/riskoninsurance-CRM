<?php

namespace App\Models;

use App\Traits\Blameable;
use App\Traits\RoleBasedFilter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\HasCustomFields;
use App\Traits\OwnedByUser;

class Lead extends Model
{
    use HasFactory, HasCustomFields, OwnedByUser, SoftDeletes, Blameable, RoleBasedFilter;

    protected static function booted(): void
    {
        static::saving(function ($lead) {
            static::syncOwnedUserFromAssignee($lead);
        });
    }

    protected $fillable = [
        'name',
        'email',
        'phone',
        'country_code',
        'group',
        'source',
        'whatsapp',
        'whatsapp_country_code',
        'address',
        'image',
        'company_name',
        'sic_code',
        'status',
        'lead_source_id',
        'lead_stage_id',
        'assigned_user_id',
        'user_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_converted',
        'converted_customer_id',
        'notes',
    ];

    protected $casts = [
        'is_converted' => 'boolean',
        'travel_start_date' => 'date',
    ];

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class);
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    public function leadSource()
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class, 'lead_stage_id');
    }

    public function leadStage()
    {
        return $this->stage();
    }

    public function convertedCustomer()
    {
        return $this->belongsTo(Customer::class, 'converted_customer_id');
    }

    public function customer()
    {
        return $this->convertedCustomer();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'related_id')->where('related_type', 'lead');
    }

    public function statusHistories()
    {
        return $this->hasMany(LeadStatusHistory::class)->latest();
    }
}
