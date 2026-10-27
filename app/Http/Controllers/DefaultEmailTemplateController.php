<?php

namespace App\Http\Controllers;

use App\Models\DefaultEmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DefaultEmailTemplateController extends Controller
{
    public function index(Request $request)
    {
        return view('masters.email_templates.index');
    }

    public function create()
    {
        return view('masters.email_templates.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $data['created_by']  = Auth::id();
        $data['modified_by'] = Auth::id();

        DefaultEmailTemplate::create($data);

        return redirect()->route('masters.default_email_templates.index')
            ->with('success', 'Email template created successfully.');
    }

    public function edit(DefaultEmailTemplate $default_email_template)
    {
        return view('masters.email_templates.edit', ['template' => $default_email_template]);
    }

    public function update(Request $request, DefaultEmailTemplate $default_email_template)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $data['modified_by'] = Auth::id();

        $default_email_template->update($data);

        return redirect()->route('masters.default_email_templates.index')
            ->with('success', 'Email template updated successfully.');
    }

    public function show(DefaultEmailTemplate $default_email_template)
    {
        return view('masters.email_templates.show', ['template' => $default_email_template]);
    }

    public function destroy(DefaultEmailTemplate $default_email_template)
    {
        $default_email_template->deleted_by = Auth::id();
        $default_email_template->save();
        $default_email_template->delete();

        return redirect()->route('masters.default_email_templates.index')
            ->with('success', 'Email template deleted successfully.');
    }

    /**
     * Mark a template as the default email template.
     */
    public function setDefault(DefaultEmailTemplate $default_email_template)
    {
        // Reset existing default, if any
        DefaultEmailTemplate::where('default_email_template', true)->update(['default_email_template' => false]);

        $default_email_template->default_email_template = true;
        $default_email_template->modified_by = Auth::id();
        $default_email_template->save();

        return redirect()->route('masters.default_email_templates.index')
            ->with('success', 'Default email template updated.');
    }

    /**
     * JSON list for JS-driven index table.
     */
    public function apiIndex(Request $request)
    {
        $query = DefaultEmailTemplate::with('creator')->orderBy('name');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $templates = $query->get()->map(function ($tpl) {
            return [
                'id'          => $tpl->id,
                'name'        => $tpl->name,
                'created_at'  => optional($tpl->created_at)->format('Y-m-d H:i'),
                'creator_name'=> optional($tpl->creator)->name,
            ];
        });

        return response()->json($templates);
    }
}

