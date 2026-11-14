<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingTemplate;
use Illuminate\Http\Request;

class MarketingTemplateController extends Controller
{
    public function index()
    {
        $templates = MarketingTemplate::latest()->paginate(20);
        return view('marketing.templates.index', compact('templates'));
    }

    public function create()
    {
        return view('marketing.templates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        MarketingTemplate::create($validated);

        return redirect()->route('admin.marketing.templates.index')
            ->with('success', 'Email template created successfully.');
    }

    public function edit(MarketingTemplate $template)
    {
        return view('marketing.templates.edit', compact('template'));
    }

    public function update(Request $request, MarketingTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $template->update($validated);

        return redirect()->route('admin.marketing.templates.index')
            ->with('success', 'Email template updated successfully.');
    }

    public function destroy(MarketingTemplate $template)
    {
        $template->delete();
        return redirect()->route('admin.marketing.templates.index')
            ->with('success', 'Email template deleted.');
    }
}
