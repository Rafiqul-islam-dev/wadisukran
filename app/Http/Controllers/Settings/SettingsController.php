<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\CompannySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $companies = CompannySetting::latest()->get();

        return Inertia::render('Company/Index', [
            'companies' => $companies
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'trn_no' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:10',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'licence_no' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:100',
            'details' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->except('logo');

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        CompannySetting::create($data);

        return redirect()->back()->with('success', 'Company setting created successfully.');
    }

    public function update(Request $request, CompannySetting $companySetting)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'trn_no' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:10',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'licence_no' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:100',
            'details' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->except('logo');

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($companySetting->logo) {
                Storage::disk('public')->delete($companySetting->logo);
            }
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $companySetting->update($data);

        return redirect()->back()->with('success', 'Company setting updated successfully.');
    }

    public function destroy(CompannySetting $companySetting)
    {
        // Delete logo file if exists
        if ($companySetting->logo) {
            Storage::disk('public')->delete($companySetting->logo);
        }

        $companySetting->delete();

        return redirect()->back()->with('success', 'Company setting deleted successfully.');
    }
}
