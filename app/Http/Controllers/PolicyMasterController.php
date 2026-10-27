<?php

namespace App\Http\Controllers;

class PolicyMasterController extends Controller
{
    public function policyTypes()
    {
        return view('masters.insurance_policy_type.index');
    }

    public function policySubTypes()
    {
        return view('masters.insurance_policy_sub_type.index');
    }

    public function segments()
    {
        return view('masters.Segments.index');
    }

    public function companies()
    {
        return view('masters.company_master.index');
    }

    public function branches()
    {
        return view('masters.branches.index');
    }

    public function agencyLevels()
    {
        return view('masters.agency_levels.index');
    }

    public function agencies()
    {
        return view('masters.agency.index');
    }

    public function brokers()
    {
        return view('masters.brokers.index');
    }

    public function divisions()
    {
        return view('masters.divisions.index');
    }

    public function banks()
    {
        return view('masters.banks.index');
    }
}
