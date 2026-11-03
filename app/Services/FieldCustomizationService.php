<?php

namespace App\Services;

use App\Models\FieldCustomization;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FieldCustomizationService
{
    protected static array $runtimeCache = [];

    /**
     * Clear runtime in-memory cache.
     */
    public static function clearRuntimeCache(): void
    {
        self::$runtimeCache = [];
    }

    /**
     * Get all supported modules for field customization in this Insurance CRM.
     */
    public function getModules(): array
    {
        return [
            'policies' => [
                'key' => 'policies',
                'name' => 'Policies',
                'icon' => 'fa-shield-halved',
                'badge' => 'bg-primary text-white',
                'description' => 'Configure fields for Insurance Policies and Coverage.',
            ],
            'customers' => [
                'key' => 'customers',
                'name' => 'Customers / Clients',
                'icon' => 'fa-users',
                'badge' => 'bg-success text-white',
                'description' => 'Configure fields for Client accounts, Family members and contacts.',
            ],
            'leads' => [
                'key' => 'leads',
                'name' => 'Leads',
                'icon' => 'fa-bullhorn',
                'badge' => 'bg-warning text-dark',
                'description' => 'Configure fields for Insurance Leads and Opportunities.',
            ],
            'prospects' => [
                'key' => 'prospects',
                'name' => 'Prospects',
                'icon' => 'fa-users-viewfinder',
                'badge' => 'bg-info text-white',
                'description' => 'Configure fields for Manage Prospects and area leads.',
            ],
            'claim_trackers' => [
                'key' => 'claim_trackers',
                'name' => 'Claim Tracker',
                'icon' => 'fa-file-circle-check',
                'badge' => 'bg-danger text-white',
                'description' => 'Configure fields for Insurance Claim Tracking and Settlement.',
            ],
            'followups' => [
                'key' => 'followups',
                'name' => 'Follow-ups',
                'icon' => 'fa-user-tie',
                'badge' => 'bg-primary text-white',
                'description' => 'Configure fields for Client and Lead Follow-ups.',
            ],
            'meetings' => [
                'key' => 'meetings',
                'name' => 'Meetings',
                'icon' => 'fa-handshake',
                'badge' => 'bg-success text-white',
                'description' => 'Configure fields for Scheduled Client and Lead Meetings.',
            ],
            'tasks' => [
                'key' => 'tasks',
                'name' => 'Tasks',
                'icon' => 'fa-tasks',
                'badge' => 'bg-info text-white',
                'description' => 'Configure fields for Operations and Management Tasks.',
            ],
            'investments' => [
                'key' => 'investments',
                'name' => 'Investments',
                'icon' => 'fa-chart-line',
                'badge' => 'bg-secondary text-white',
                'description' => 'Configure fields for Mutual Funds, Bonds, FD and Investments.',
            ],
            'users' => [
                'key' => 'users',
                'name' => 'Sub-Agents / Staff',
                'icon' => 'fa-people-group',
                'badge' => 'bg-dark text-white',
                'description' => 'Configure fields for Sub-Agents and Staff Team Members.',
            ],
            'deals' => [
                'key' => 'deals',
                'name' => 'Deals',
                'icon' => 'fa-medal',
                'badge' => 'bg-warning text-dark',
                'description' => 'Configure fields for Sales Deals and Pipeline Opportunities.',
            ],
            'quotations' => [
                'key' => 'quotations',
                'name' => 'Quotations',
                'icon' => 'fa-file-invoice-dollar',
                'badge' => 'bg-info text-dark',
                'description' => 'Configure fields for Estimates and Insurance Quotations.',
            ],
        ];
    }

    /**
     * Get default field definitions for a module.
     */
    public function getDefaultFields(string $module): array
    {
        $registry = [
            'policies' => [
                'policy_number' => [
                    'field_name' => 'policy_number',
                    'default_label' => 'Policy Number',
                    'type' => 'text',
                    'default_required' => true,
                    'system_required' => true,
                    'default_visible' => true,
                    'default_order' => 10,
                ],
                'staff_id' => [
                    'field_name' => 'staff_id',
                    'default_label' => 'Staff / Agent Name',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 20,
                ],
                'family_group_id' => [
                    'field_name' => 'family_group_id',
                    'default_label' => 'Family Group',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 30,
                ],
                'user_id' => [
                    'field_name' => 'user_id',
                    'default_label' => 'Policy Holder / Client',
                    'type' => 'select',
                    'default_required' => true,
                    'system_required' => true,
                    'default_visible' => true,
                    'default_order' => 40,
                ],
                'policy_type_id' => [
                    'field_name' => 'policy_type_id',
                    'default_label' => 'Policy Type',
                    'type' => 'select',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 50,
                ],
                'sub_type_id' => [
                    'field_name' => 'sub_type_id',
                    'default_label' => 'Policy Sub Type',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 60,
                ],
                'segment_id' => [
                    'field_name' => 'segment_id',
                    'default_label' => 'Segment',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 70,
                ],
                'company_id' => [
                    'field_name' => 'company_id',
                    'default_label' => 'Insurance Company',
                    'type' => 'select',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 80,
                ],
                'branch_id' => [
                    'field_name' => 'branch_id',
                    'default_label' => 'Branch',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 90,
                ],
                'vehicle_reg_no' => [
                    'field_name' => 'vehicle_reg_no',
                    'default_label' => 'Vehicle Registration Number',
                    'type' => 'text',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 100,
                ],
                'vehicle_model' => [
                    'field_name' => 'vehicle_model',
                    'default_label' => 'Vehicle Model',
                    'type' => 'text',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 110,
                ],
                'engine_no' => [
                    'field_name' => 'engine_no',
                    'default_label' => 'Engine Number',
                    'type' => 'text',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 120,
                ],
                'chassis_no' => [
                    'field_name' => 'chassis_no',
                    'default_label' => 'Chassis Number',
                    'type' => 'text',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 130,
                ],
                'fuel_type' => [
                    'field_name' => 'fuel_type',
                    'default_label' => 'Fuel Type',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 140,
                ],
                'cc' => [
                    'field_name' => 'cc',
                    'default_label' => 'Engine Capacity (CC)',
                    'type' => 'number',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 150,
                ],
                'gvw' => [
                    'field_name' => 'gvw',
                    'default_label' => 'Gross Vehicle Weight (GVW)',
                    'type' => 'number',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 160,
                ],
                'ncb' => [
                    'field_name' => 'ncb',
                    'default_label' => 'No Claim Bonus (NCB)',
                    'type' => 'text',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 170,
                ],
                'year_of_manufacture' => [
                    'field_name' => 'year_of_manufacture',
                    'default_label' => 'Year of Manufacture',
                    'type' => 'number',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 180,
                ],
                'policy_mode' => [
                    'field_name' => 'policy_mode',
                    'default_label' => 'Payment Mode',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 190,
                ],
                'start_date' => [
                    'field_name' => 'start_date',
                    'default_label' => 'Policy Start Date',
                    'type' => 'date',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 200,
                ],
                'end_date' => [
                    'field_name' => 'end_date',
                    'default_label' => 'Policy Expiry Date',
                    'type' => 'date',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 210,
                ],
                'nominee_name' => [
                    'field_name' => 'nominee_name',
                    'default_label' => 'Nominee Name',
                    'type' => 'text',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 220,
                ],
                'nominee_contact' => [
                    'field_name' => 'nominee_contact',
                    'default_label' => 'Nominee Contact Number',
                    'type' => 'tel',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 230,
                ],
                'sum_assured' => [
                    'field_name' => 'sum_assured',
                    'default_label' => 'Sum Assured',
                    'type' => 'number',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 240,
                ],
                'vehicle_idv' => [
                    'field_name' => 'vehicle_idv',
                    'default_label' => 'Vehicle IDV',
                    'type' => 'number',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 250,
                ],
                'premium' => [
                    'field_name' => 'premium',
                    'default_label' => 'Net Premium Amount',
                    'type' => 'number',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 260,
                ],
                'tax_amount' => [
                    'field_name' => 'tax_amount',
                    'default_label' => 'Tax / GST Amount',
                    'type' => 'number',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 270,
                ],
                'payment_date' => [
                    'field_name' => 'payment_date',
                    'default_label' => 'Payment Date',
                    'type' => 'date',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 280,
                ],
                'remarks' => [
                    'field_name' => 'remarks',
                    'default_label' => 'Remarks / Notes',
                    'type' => 'textarea',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 290,
                ],
            ],

            'customers' => [
                'name' => [
                    'field_name' => 'name',
                    'default_label' => 'Customer / Client Name',
                    'type' => 'text',
                    'default_required' => true,
                    'system_required' => true,
                    'default_visible' => true,
                    'default_order' => 10,
                ],
                'email' => [
                    'field_name' => 'email',
                    'default_label' => 'Email Address',
                    'type' => 'email',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 20,
                ],
                'phone' => [
                    'field_name' => 'phone',
                    'default_label' => 'Phone Number',
                    'type' => 'tel',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 30,
                ],
                'whatsapp' => [
                    'field_name' => 'whatsapp',
                    'default_label' => 'WhatsApp Number',
                    'type' => 'tel',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 40,
                ],
                'company_name' => [
                    'field_name' => 'company_name',
                    'default_label' => 'Company Name',
                    'type' => 'text',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 50,
                ],
                'website' => [
                    'field_name' => 'website',
                    'default_label' => 'Website',
                    'type' => 'url',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 60,
                ],
                'tax_number' => [
                    'field_name' => 'tax_number',
                    'default_label' => 'PAN / GST / Tax Number',
                    'type' => 'text',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 70,
                ],
                'type' => [
                    'field_name' => 'type',
                    'default_label' => 'Client Type',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 80,
                ],
                'dob' => [
                    'field_name' => 'dob',
                    'default_label' => 'Date of Birth',
                    'type' => 'date',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 90,
                ],
                'anniversary_date' => [
                    'field_name' => 'anniversary_date',
                    'default_label' => 'Anniversary Date',
                    'type' => 'date',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 100,
                ],
                'country_id' => [
                    'field_name' => 'country_id',
                    'default_label' => 'Country',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 110,
                ],
                'city_id' => [
                    'field_name' => 'city_id',
                    'default_label' => 'City',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 120,
                ],
                'address' => [
                    'field_name' => 'address',
                    'default_label' => 'Address',
                    'type' => 'textarea',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 130,
                ],
                'image' => [
                    'field_name' => 'image',
                    'default_label' => 'Profile Photo',
                    'type' => 'file',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 140,
                ],
            ],

            'leads' => [
                'name' => [
                    'field_name' => 'name',
                    'default_label' => 'Lead Name',
                    'type' => 'text',
                    'default_required' => true,
                    'system_required' => true,
                    'default_visible' => true,
                    'default_order' => 10,
                ],
                'email' => [
                    'field_name' => 'email',
                    'default_label' => 'Email Address',
                    'type' => 'email',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 20,
                ],
                'phone' => [
                    'field_name' => 'phone',
                    'default_label' => 'Phone Number',
                    'type' => 'tel',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 30,
                ],
                'whatsapp' => [
                    'field_name' => 'whatsapp',
                    'default_label' => 'WhatsApp Number',
                    'type' => 'tel',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 40,
                ],
                'status' => [
                    'field_name' => 'status',
                    'default_label' => 'Lead Status',
                    'type' => 'select',
                    'default_required' => true,
                    'system_required' => true,
                    'default_visible' => true,
                    'default_order' => 50,
                ],
                'assigned_user_id' => [
                    'field_name' => 'assigned_user_id',
                    'default_label' => 'Assigned Staff / Agent',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 60,
                ],
                'company_name' => [
                    'field_name' => 'company_name',
                    'default_label' => 'Company Name',
                    'type' => 'text',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 70,
                ],
                'group' => [
                    'field_name' => 'group',
                    'default_label' => 'Group / Family Group',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 80,
                ],
                'source' => [
                    'field_name' => 'source',
                    'default_label' => 'Lead Source',
                    'type' => 'text',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 90,
                ],
                'address' => [
                    'field_name' => 'address',
                    'default_label' => 'Address',
                    'type' => 'textarea',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 100,
                ],
                'image' => [
                    'field_name' => 'image',
                    'default_label' => 'Lead Photo',
                    'type' => 'file',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 110,
                ],
                'notes' => [
                    'field_name' => 'notes',
                    'default_label' => 'Comments / Notes',
                    'type' => 'textarea',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 120,
                ],
            ],

            'prospects' => [
                'name' => [
                    'field_name' => 'name',
                    'default_label' => 'Prospect Name',
                    'type' => 'text',
                    'default_required' => true,
                    'system_required' => true,
                    'default_visible' => true,
                    'default_order' => 10,
                ],
                'mobile_no' => [
                    'field_name' => 'mobile_no',
                    'default_label' => 'Mobile Number',
                    'type' => 'tel',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 20,
                ],
                'area' => [
                    'field_name' => 'area',
                    'default_label' => 'Area / Location',
                    'type' => 'text',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 30,
                ],
                'status' => [
                    'field_name' => 'status',
                    'default_label' => 'Status',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 40,
                ],
            ],

            'claim_trackers' => [
                'family_group_id' => [
                    'field_name' => 'family_group_id',
                    'default_label' => 'Family Group',
                    'type' => 'select',
                    'default_required' => true,
                    'system_required' => true,
                    'default_visible' => true,
                    'default_order' => 10,
                ],
                'proposer_id' => [
                    'field_name' => 'proposer_id',
                    'default_label' => 'Proposer / Client',
                    'type' => 'select',
                    'default_required' => true,
                    'system_required' => true,
                    'default_visible' => true,
                    'default_order' => 20,
                ],
                'policy_id' => [
                    'field_name' => 'policy_id',
                    'default_label' => 'Policy Number',
                    'type' => 'select',
                    'default_required' => true,
                    'system_required' => true,
                    'default_visible' => true,
                    'default_order' => 30,
                ],
                'insured_member_id' => [
                    'field_name' => 'insured_member_id',
                    'default_label' => 'Insured Member',
                    'type' => 'select',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 40,
                ],
                'claim_amount' => [
                    'field_name' => 'claim_amount',
                    'default_label' => 'Claim Amount',
                    'type' => 'number',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 50,
                ],
                'issued_by' => [
                    'field_name' => 'issued_by',
                    'default_label' => 'Issued By (Agent / Broker)',
                    'type' => 'select',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 60,
                ],
                'claim_file' => [
                    'field_name' => 'claim_file',
                    'default_label' => 'Claim Document / File',
                    'type' => 'file',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 70,
                ],
                'claim_file_received_date' => [
                    'field_name' => 'claim_file_received_date',
                    'default_label' => 'File Received Date',
                    'type' => 'date',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 80,
                ],
                'claim_file_submit_date' => [
                    'field_name' => 'claim_file_submit_date',
                    'default_label' => 'File Submit Date',
                    'type' => 'date',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 90,
                ],
                'claim_status' => [
                    'field_name' => 'claim_status',
                    'default_label' => 'Claim Status',
                    'type' => 'select',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 100,
                ],
            ],

            'followups' => [
                'lead_id' => [
                    'field_name' => 'lead_id',
                    'default_label' => 'Lead Name',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 10,
                ],
                'customer_id' => [
                    'field_name' => 'customer_id',
                    'default_label' => 'Customer / Client',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 20,
                ],
                'assigned_user_id' => [
                    'field_name' => 'assigned_user_id',
                    'default_label' => 'Assigned Staff / Agent',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 30,
                ],
                'purpose' => [
                    'field_name' => 'purpose',
                    'default_label' => 'Purpose',
                    'type' => 'text',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 40,
                ],
                'priority' => [
                    'field_name' => 'priority',
                    'default_label' => 'Priority',
                    'type' => 'select',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 50,
                ],
                'status' => [
                    'field_name' => 'status',
                    'default_label' => 'Status',
                    'type' => 'select',
                    'default_required' => true,
                    'system_required' => true,
                    'default_visible' => true,
                    'default_order' => 60,
                ],
                'follow_up_at' => [
                    'field_name' => 'follow_up_at',
                    'default_label' => 'Follow Up Date & Time',
                    'type' => 'datetime',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 70,
                ],
                'next_follow_up_at' => [
                    'field_name' => 'next_follow_up_at',
                    'default_label' => 'Next Follow Up Date',
                    'type' => 'date',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 80,
                ],
                'comment' => [
                    'field_name' => 'comment',
                    'default_label' => 'Comment / Notes',
                    'type' => 'textarea',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 90,
                ],
            ],

            'meetings' => [
                'title' => [
                    'field_name' => 'title',
                    'default_label' => 'Meeting Title',
                    'type' => 'text',
                    'default_required' => true,
                    'system_required' => true,
                    'default_visible' => true,
                    'default_order' => 10,
                ],
                'customer_id' => [
                    'field_name' => 'customer_id',
                    'default_label' => 'Customer',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 20,
                ],
                'lead_id' => [
                    'field_name' => 'lead_id',
                    'default_label' => 'Lead',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 30,
                ],
                'assigned_user_id' => [
                    'field_name' => 'assigned_user_id',
                    'default_label' => 'Assigned Staff',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 40,
                ],
                'scheduled_at' => [
                    'field_name' => 'scheduled_at',
                    'default_label' => 'Scheduled Date & Time',
                    'type' => 'datetime',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 50,
                ],
                'meeting_type' => [
                    'field_name' => 'meeting_type',
                    'default_label' => 'Meeting Type',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 60,
                ],
                'status' => [
                    'field_name' => 'status',
                    'default_label' => 'Status',
                    'type' => 'select',
                    'default_required' => true,
                    'system_required' => true,
                    'default_visible' => true,
                    'default_order' => 70,
                ],
                'agenda' => [
                    'field_name' => 'agenda',
                    'default_label' => 'Agenda',
                    'type' => 'textarea',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 80,
                ],
                'address' => [
                    'field_name' => 'address',
                    'default_label' => 'Address / Location',
                    'type' => 'textarea',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 90,
                ],
            ],

            'tasks' => [
                'title' => [
                    'field_name' => 'title',
                    'default_label' => 'Task Title',
                    'type' => 'text',
                    'default_required' => true,
                    'system_required' => true,
                    'default_visible' => true,
                    'default_order' => 10,
                ],
                'related_id' => [
                    'field_name' => 'related_id',
                    'default_label' => 'Related Customer',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 20,
                ],
                'policy_type_id' => [
                    'field_name' => 'policy_type_id',
                    'default_label' => 'Policy Type',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 30,
                ],
                'policy_sub_type_id' => [
                    'field_name' => 'policy_sub_type_id',
                    'default_label' => 'Policy Sub Type',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 40,
                ],
                'assigned_user_id' => [
                    'field_name' => 'assigned_user_id',
                    'default_label' => 'Assigned Staff',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 50,
                ],
                'priority' => [
                    'field_name' => 'priority',
                    'default_label' => 'Priority',
                    'type' => 'select',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 60,
                ],
                'status' => [
                    'field_name' => 'status',
                    'default_label' => 'Status',
                    'type' => 'select',
                    'default_required' => true,
                    'system_required' => true,
                    'default_visible' => true,
                    'default_order' => 70,
                ],
                'due_date' => [
                    'field_name' => 'due_date',
                    'default_label' => 'Due Date',
                    'type' => 'date',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 80,
                ],
                'description' => [
                    'field_name' => 'description',
                    'default_label' => 'Description',
                    'type' => 'textarea',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 90,
                ],
            ],

            'investments' => [
                'transaction_date' => [
                    'field_name' => 'transaction_date',
                    'default_label' => 'Transaction Date',
                    'type' => 'date',
                    'default_required' => true,
                    'system_required' => true,
                    'default_visible' => true,
                    'default_order' => 10,
                ],
                'document_number' => [
                    'field_name' => 'document_number',
                    'default_label' => 'Document Number',
                    'type' => 'text',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 20,
                ],
                'family_group_id' => [
                    'field_name' => 'family_group_id',
                    'default_label' => 'Family Group',
                    'type' => 'select',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 30,
                ],
                'investment_holder_id' => [
                    'field_name' => 'investment_holder_id',
                    'default_label' => 'Policy Holder / Investor',
                    'type' => 'select',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 40,
                ],
                'first_name' => [
                    'field_name' => 'first_name',
                    'default_label' => 'First Name',
                    'type' => 'text',
                    'default_required' => true,
                    'system_required' => true,
                    'default_visible' => true,
                    'default_order' => 50,
                ],
                'middle_name' => [
                    'field_name' => 'middle_name',
                    'default_label' => 'Middle Name',
                    'type' => 'text',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 60,
                ],
                'last_name' => [
                    'field_name' => 'last_name',
                    'default_label' => 'Last Name',
                    'type' => 'text',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 70,
                ],
                'mobile_number' => [
                    'field_name' => 'mobile_number',
                    'default_label' => 'Mobile Number',
                    'type' => 'tel',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 80,
                ],
                'email_address' => [
                    'field_name' => 'email_address',
                    'default_label' => 'Email Address',
                    'type' => 'email',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 90,
                ],
                'gender' => [
                    'field_name' => 'gender',
                    'default_label' => 'Gender',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 100,
                ],
                'date_of_birth' => [
                    'field_name' => 'date_of_birth',
                    'default_label' => 'Date of Birth',
                    'type' => 'date',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 110,
                ],
                'family_head_name' => [
                    'field_name' => 'family_head_name',
                    'default_label' => 'Family Head Name',
                    'type' => 'text',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 120,
                ],
                'relation_with_head' => [
                    'field_name' => 'relation_with_head',
                    'default_label' => 'Relation With Head',
                    'type' => 'text',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 130,
                ],
                'city' => [
                    'field_name' => 'city',
                    'default_label' => 'City',
                    'type' => 'text',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 140,
                ],
                'area' => [
                    'field_name' => 'area',
                    'default_label' => 'Area',
                    'type' => 'text',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 150,
                ],
                'address_line_1' => [
                    'field_name' => 'address_line_1',
                    'default_label' => 'Address Line 1',
                    'type' => 'text',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 160,
                ],
            ],

            'users' => [
                'name' => [
                    'field_name' => 'name',
                    'default_label' => 'Full Name',
                    'type' => 'text',
                    'default_required' => true,
                    'system_required' => true,
                    'default_visible' => true,
                    'default_order' => 10,
                ],
                'username' => [
                    'field_name' => 'username',
                    'default_label' => 'User Name',
                    'type' => 'text',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 20,
                ],
                'email' => [
                    'field_name' => 'email',
                    'default_label' => 'Work Email Address',
                    'type' => 'email',
                    'default_required' => true,
                    'system_required' => true,
                    'default_visible' => true,
                    'default_order' => 30,
                ],
                'phone' => [
                    'field_name' => 'phone',
                    'default_label' => 'Phone Number',
                    'type' => 'tel',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 40,
                ],
                'whatsapp' => [
                    'field_name' => 'whatsapp',
                    'default_label' => 'WhatsApp Number',
                    'type' => 'tel',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 50,
                ],
                'role' => [
                    'field_name' => 'role',
                    'default_label' => 'Role / Designation',
                    'type' => 'select',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 60,
                ],
                'address' => [
                    'field_name' => 'address',
                    'default_label' => 'Address',
                    'type' => 'textarea',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 70,
                ],
                'image' => [
                    'field_name' => 'image',
                    'default_label' => 'Profile Photo',
                    'type' => 'file',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 80,
                ],
            ],

            'deals' => [
                'title' => [
                    'field_name' => 'title',
                    'default_label' => 'Deal Title',
                    'type' => 'text',
                    'default_required' => true,
                    'system_required' => true,
                    'default_visible' => true,
                    'default_order' => 10,
                ],
                'customer_id' => [
                    'field_name' => 'customer_id',
                    'default_label' => 'Customer',
                    'type' => 'select',
                    'default_required' => true,
                    'system_required' => true,
                    'default_visible' => true,
                    'default_order' => 20,
                ],
                'amount' => [
                    'field_name' => 'amount',
                    'default_label' => 'Deal Amount',
                    'type' => 'number',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 30,
                ],
                'currency_id' => [
                    'field_name' => 'currency_id',
                    'default_label' => 'Currency',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 40,
                ],
                'stage_id' => [
                    'field_name' => 'stage_id',
                    'default_label' => 'Pipeline Stage',
                    'type' => 'select',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 50,
                ],
                'status_id' => [
                    'field_name' => 'status_id',
                    'default_label' => 'Deal Status',
                    'type' => 'select',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 60,
                ],
                'probability' => [
                    'field_name' => 'probability',
                    'default_label' => 'Probability (%)',
                    'type' => 'number',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 70,
                ],
                'expected_close_date' => [
                    'field_name' => 'expected_close_date',
                    'default_label' => 'Expected Close Date',
                    'type' => 'date',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 80,
                ],
                'assigned_user_id' => [
                    'field_name' => 'assigned_user_id',
                    'default_label' => 'Assigned Staff',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 90,
                ],
            ],

            'quotations' => [
                'reference' => [
                    'field_name' => 'reference',
                    'default_label' => 'Quotation Reference',
                    'type' => 'text',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 10,
                ],
                'lead_id' => [
                    'field_name' => 'lead_id',
                    'default_label' => 'Related Lead',
                    'type' => 'select',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 20,
                ],
                'total_amount' => [
                    'field_name' => 'total_amount',
                    'default_label' => 'Total Amount',
                    'type' => 'number',
                    'default_required' => true,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 30,
                ],
                'valid_until' => [
                    'field_name' => 'valid_until',
                    'default_label' => 'Valid Until',
                    'type' => 'date',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 40,
                ],
                'status' => [
                    'field_name' => 'status',
                    'default_label' => 'Status',
                    'type' => 'select',
                    'default_required' => true,
                    'system_required' => true,
                    'default_visible' => true,
                    'default_order' => 50,
                ],
                'notes' => [
                    'field_name' => 'notes',
                    'default_label' => 'Notes / Terms',
                    'type' => 'textarea',
                    'default_required' => false,
                    'system_required' => false,
                    'default_visible' => true,
                    'default_order' => 60,
                ],
            ],
        ];

        return $registry[$module] ?? [];
    }

    /**
     * Get module fields merged with database configurations.
     */
    public function getModuleFields(string $module): Collection
    {
        $defaults = $this->getDefaultFields($module);
        if (empty($defaults)) {
            return collect();
        }

        $saved = $this->getSavedCustomizations($module);
        $merged = collect();

        foreach ($defaults as $fieldName => $defaultDef) {
            $custom = $saved->get($fieldName);

            $isSystemRequired = !empty($defaultDef['system_required']);
            $customLabel = $custom?->custom_label;
            $validationMsg = $custom?->validation_message;
            $order = $custom?->display_order ?? $defaultDef['default_order'];

            $isVisible = $isSystemRequired ? true : ($custom ? (bool) $custom->is_visible : (bool) $defaultDef['default_visible']);
            $isRequired = $isSystemRequired ? true : ($custom ? (bool) $custom->is_required : (bool) $defaultDef['default_required']);
            $activeLabel = filled($customLabel) ? $customLabel : $defaultDef['default_label'];

            $merged->put($fieldName, [
                'field_name' => $fieldName,
                'default_label' => $defaultDef['default_label'],
                'custom_label' => $customLabel ?? '',
                'label' => $activeLabel,
                'is_visible' => $isVisible,
                'is_required' => $isRequired,
                'system_required' => $isSystemRequired,
                'validation_message' => $validationMsg ?? '',
                'display_order' => (int) $order,
                'type' => $defaultDef['type'] ?? 'text',
            ]);
        }

        return $merged->sortBy('display_order')->values();
    }

    /**
     * Get saved customizations indexed by field_name.
     */
    protected function getSavedCustomizations(string $module): Collection
    {
        if (isset(self::$runtimeCache[$module])) {
            return self::$runtimeCache[$module];
        }

        try {
            if (!Schema::hasTable('field_customizations')) {
                return collect();
            }

            $records = FieldCustomization::where('module', $module)->get()->keyBy('field_name');
            self::$runtimeCache[$module] = $records;
            return $records;
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * Get configuration for a single field.
     */
    public function getFieldConfig(string $module, string $fieldName): array
    {
        $fields = $this->getModuleFields($module);
        $field = $fields->firstWhere('field_name', $fieldName);

        if ($field) {
            return $field;
        }

        $defaults = $this->getDefaultFields($module);
        $defaultDef = $defaults[$fieldName] ?? null;

        if ($defaultDef) {
            return [
                'field_name' => $fieldName,
                'default_label' => $defaultDef['default_label'],
                'custom_label' => '',
                'label' => $defaultDef['default_label'],
                'is_visible' => (bool) $defaultDef['default_visible'],
                'is_required' => (bool) $defaultDef['default_required'],
                'system_required' => (bool) ($defaultDef['system_required'] ?? false),
                'validation_message' => '',
                'display_order' => (int) $defaultDef['default_order'],
                'type' => $defaultDef['type'] ?? 'text',
            ];
        }

        return [
            'field_name' => $fieldName,
            'default_label' => ucwords(str_replace('_', ' ', $fieldName)),
            'custom_label' => '',
            'label' => ucwords(str_replace('_', ' ', $fieldName)),
            'is_visible' => true,
            'is_required' => false,
            'system_required' => false,
            'validation_message' => '',
            'display_order' => 100,
            'type' => 'text',
        ];
    }

    /**
     * Check if a field is visible.
     */
    public function isFieldVisible(string $module, string $fieldName): bool
    {
        $config = $this->getFieldConfig($module, $fieldName);
        return (bool) $config['is_visible'];
    }

    /**
     * Check if a field is required.
     */
    public function isFieldRequired(string $module, string $fieldName): bool
    {
        $config = $this->getFieldConfig($module, $fieldName);
        return (bool) $config['is_required'];
    }

    /**
     * Get custom or default label for a field.
     */
    public function getFieldLabel(string $module, string $fieldName, ?string $fallback = null): string
    {
        $config = $this->getFieldConfig($module, $fieldName);
        if (!empty($config['label'])) {
            return $config['label'];
        }
        return $fallback ?? $config['default_label'] ?? ucwords(str_replace('_', ' ', $fieldName));
    }

    /**
     * Get validation message for a field.
     */
    public function getValidationMessage(string $module, string $fieldName): ?string
    {
        $config = $this->getFieldConfig($module, $fieldName);
        return !empty($config['validation_message']) ? $config['validation_message'] : null;
    }

    /**
     * Save field customizations for a module.
     */
    public function saveModuleFields(string $module, array $fieldsData): void
    {
        $defaults = $this->getDefaultFields($module);
        if (empty($defaults)) {
            throw new \InvalidArgumentException("Invalid CRM module: {$module}");
        }

        DB::transaction(function () use ($module, $fieldsData, $defaults) {
            foreach ($fieldsData as $fieldName => $data) {
                if (!isset($defaults[$fieldName])) {
                    continue;
                }

                $defaultDef = $defaults[$fieldName];
                $isSystemRequired = !empty($defaultDef['system_required']);

                $customLabel = isset($data['custom_label']) ? trim((string) $data['custom_label']) : null;
                if ($customLabel === $defaultDef['default_label'] || $customLabel === '') {
                    $customLabel = null;
                }

                $isVisible = $isSystemRequired ? true : (!empty($data['is_visible']));
                $isRequired = $isSystemRequired ? true : (!empty($data['is_required']));
                $validationMsg = isset($data['validation_message']) ? trim((string) $data['validation_message']) : null;
                $displayOrder = isset($data['display_order']) ? (int) $data['display_order'] : (int) $defaultDef['default_order'];

                FieldCustomization::updateOrCreate(
                    [
                        'module' => $module,
                        'field_name' => $fieldName,
                    ],
                    [
                        'custom_label' => $customLabel,
                        'is_visible' => $isVisible,
                        'is_required' => $isRequired,
                        'validation_message' => $validationMsg ?: null,
                        'display_order' => $displayOrder,
                    ]
                );
            }
        });

        unset(self::$runtimeCache[$module]);
    }

    /**
     * Reset field customizations for a module.
     */
    public function resetModuleFields(string $module): void
    {
        FieldCustomization::where('module', $module)->delete();
        unset(self::$runtimeCache[$module]);
    }

    /**
     * Apply dynamic validation rules and custom messages for a module.
     */
    public static function applyDynamicValidation(string $module, array $rules, array &$messages = [], array|bool &$customAttributes = [], bool $isUpdate = false): array
    {
        if (is_bool($customAttributes)) {
            $isUpdate = $customAttributes;
            $customAttributes = [];
        }

        $instance = app(self::class);
        $moduleFields = $instance->getModuleFields($module)->keyBy('field_name');

        foreach ($moduleFields as $fieldName => $fieldConfig) {
            $isRequired = (bool) $fieldConfig['is_required'];
            $isVisible = (bool) $fieldConfig['is_visible'];
            $label = $fieldConfig['label'];
            $hasCustomMsg = !empty($fieldConfig['validation_message']);
            $customMsg = $hasCustomMsg ? $fieldConfig['validation_message'] : "The {$label} field is required.";

            if (is_array($customAttributes)) {
                $customAttributes[$fieldName] = $label;
            }

            if (!$isVisible && !$fieldConfig['system_required']) {
                $isRequired = false;
            }

            if (isset($rules[$fieldName])) {
                $fieldRules = $rules[$fieldName];

                if (is_string($fieldRules)) {
                    $fieldRules = explode('|', $fieldRules);
                } elseif (!is_array($fieldRules)) {
                    $fieldRules = [$fieldRules];
                }

                if ($isRequired) {
                    $fieldRules = array_filter($fieldRules, function ($r) {
                        return is_string($r) ? strtolower($r) !== 'nullable' : true;
                    });

                    $hasRequired = false;
                    foreach ($fieldRules as $r) {
                        if (is_string($r) && strtolower($r) === 'required') {
                            $hasRequired = true;
                            break;
                        }
                    }
                    if (!$hasRequired) {
                        array_unshift($fieldRules, 'required');
                    }

                    $messages["{$fieldName}.required"] = $customMsg;
                    if ($hasCustomMsg) {
                        $messages["{$fieldName}.regex"] = $customMsg;
                        $messages["{$fieldName}.string"] = $customMsg;
                        $messages["{$fieldName}.max"] = $customMsg;
                        $messages["{$fieldName}.min"] = $customMsg;
                    }
                } else {
                    if (!$fieldConfig['system_required']) {
                        $fieldRules = array_filter($fieldRules, function ($r) {
                            return is_string($r) ? strtolower($r) !== 'required' : true;
                        });

                        $hasNullable = false;
                        foreach ($fieldRules as $r) {
                            if (is_string($r) && strtolower($r) === 'nullable') {
                                $hasNullable = true;
                                break;
                            }
                        }
                        if (!$hasNullable) {
                            array_unshift($fieldRules, 'nullable');
                        }
                    }

                    if ($hasCustomMsg) {
                        $messages["{$fieldName}.required"] = $customMsg;
                        $messages["{$fieldName}.regex"] = $customMsg;
                        $messages["{$fieldName}.string"] = $customMsg;
                        $messages["{$fieldName}.max"] = $customMsg;
                        $messages["{$fieldName}.min"] = $customMsg;
                    }
                }

                $rules[$fieldName] = array_values($fieldRules);
            } else {
                if ($isRequired) {
                    $rules[$fieldName] = ['required'];
                    $messages["{$fieldName}.required"] = $customMsg;
                    if ($hasCustomMsg) {
                        $messages["{$fieldName}.regex"] = $customMsg;
                        $messages["{$fieldName}.string"] = $customMsg;
                    }
                }
            }
        }

        return $rules;
    }

    /**
     * Filter update data to avoid overwriting hidden fields with empty values.
     */
    public static function filterUpdateData(string $module, array $data, $existingModel = null): array
    {
        $instance = app(self::class);
        $moduleFields = $instance->getModuleFields($module)->keyBy('field_name');

        foreach ($moduleFields as $fieldName => $fieldConfig) {
            if (!$fieldConfig['is_visible'] && !$fieldConfig['system_required']) {
                if (array_key_exists($fieldName, $data)) {
                    $val = $data[$fieldName];
                    if ($val === null || $val === '' || (is_array($val) && empty($val))) {
                        unset($data[$fieldName]);
                    }
                }
            }
        }

        return $data;
    }
}
