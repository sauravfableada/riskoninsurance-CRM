<?php

namespace App\Http\Controllers;

use App\Models\EmailMarketingTemplate;
use App\Models\DefaultEmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailMarketingTemplateController extends Controller
{
    public function index()
    {
        $records = EmailMarketingTemplate::with(['defaultTemplate', 'creator'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('marketing.email_marketing_templates.index', compact('records'));
    }

    public function create()
    {
        $templates = DefaultEmailTemplate::orderBy('name')->get();

        return view('marketing.email_marketing_templates.create', compact('templates'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'template_id' => 'required|exists:default_email_template,id',
            'name'        => 'required|string|max:255',
            'status'      => 'required|string|max:50',
            'image_1'     => 'nullable|image|max:2048',
            'image_2'     => 'nullable|image|max:2048',
            'image_3'     => 'nullable|image|max:2048',
        ]);

        $data['user_id']     = Auth::id();
        $data['created_by']  = Auth::id();
        $data['modified_by'] = Auth::id();

        // Store uploaded images and capture their paths
        $image1Path = null;
        $image2Path = null;
        $image3Path = null;

        if ($request->hasFile('image_1')) {
            $image1Path = $request->file('image_1')->store('email_marketing', 'public');
            $data['image_1'] = $image1Path;
        }
        if ($request->hasFile('image_2')) {
            $image2Path = $request->file('image_2')->store('email_marketing', 'public');
            $data['image_2'] = $image2Path;
        }
        if ($request->hasFile('image_3')) {
            $image3Path = $request->file('image_3')->store('email_marketing', 'public');
            $data['image_3'] = $image3Path;
        }

        // Build content from selected default template and replace image placeholders
        $baseTemplate = DefaultEmailTemplate::findOrFail($data['template_id']);
        $content = $baseTemplate->content;

        if ($image1Path) {
            $content = str_replace('[IMG1]', asset('storage/' . $image1Path), $content);
        }
        if ($image2Path) {
            $content = str_replace('[IMG2]', asset('storage/' . $image2Path), $content);
        }
        if ($image3Path) {
            $content = str_replace('[IMG3]', asset('storage/' . $image3Path), $content);
        }

        $data['content'] = $content;

        EmailMarketingTemplate::create($data);

        return redirect()->route('marketing.email_marketing_templates.index')
            ->with('success', 'Email marketing template created successfully.');
    }

    public function show(EmailMarketingTemplate $email_marketing_template)
    {
        $email_marketing_template->load('defaultTemplate', 'creator');

        return view('marketing.email_marketing_templates.show', [
            'record' => $email_marketing_template,
        ]);
    }

    public function edit(EmailMarketingTemplate $email_marketing_template)
    {
        $templates = DefaultEmailTemplate::orderBy('name')->get();

        return view('marketing.email_marketing_templates.edit', [
            'record'    => $email_marketing_template,
            'templates' => $templates,
        ]);
    }

    public function update(Request $request, EmailMarketingTemplate $email_marketing_template)
    {
        $data = $request->validate([
            'template_id' => 'required|exists:default_email_template,id',
            'name'        => 'required|string|max:255',
            'status'      => 'required|string|max:50',
            'image_1'     => 'nullable|image|max:2048',
            'image_2'     => 'nullable|image|max:2048',
            'image_3'     => 'nullable|image|max:2048',
        ]);

        $data['modified_by'] = Auth::id();

        // Start with existing image paths
        $image1Path = $email_marketing_template->image_1;
        $image2Path = $email_marketing_template->image_2;
        $image3Path = $email_marketing_template->image_3;

        if ($request->hasFile('image_1')) {
            $image1Path = $request->file('image_1')->store('email_marketing', 'public');
            $data['image_1'] = $image1Path;
        }
        if ($request->hasFile('image_2')) {
            $image2Path = $request->file('image_2')->store('email_marketing', 'public');
            $data['image_2'] = $image2Path;
        }
        if ($request->hasFile('image_3')) {
            $image3Path = $request->file('image_3')->store('email_marketing', 'public');
            $data['image_3'] = $image3Path;
        }

        // Rebuild content from (possibly new) default template and current image paths
        $baseTemplate = DefaultEmailTemplate::findOrFail($data['template_id']);
        $content = $baseTemplate->content;

        if ($image1Path) {
            $content = str_replace('[IMG1]', asset('storage/' . $image1Path), $content);
        }
        if ($image2Path) {
            $content = str_replace('[IMG2]', asset('storage/' . $image2Path), $content);
        }
        if ($image3Path) {
            $content = str_replace('[IMG3]', asset('storage/' . $image3Path), $content);
        }

        $data['content'] = $content;

        $email_marketing_template->update($data);

        return redirect()->route('marketing.email_marketing_templates.index')
            ->with('success', 'Email marketing template updated successfully.');
    }

    public function destroy(EmailMarketingTemplate $email_marketing_template)
    {
        $email_marketing_template->deleted_by = Auth::id();
        $email_marketing_template->save();
        $email_marketing_template->delete();

        return redirect()->route('marketing.email_marketing_templates.index')
            ->with('success', 'Email marketing template deleted successfully.');
    }
}

