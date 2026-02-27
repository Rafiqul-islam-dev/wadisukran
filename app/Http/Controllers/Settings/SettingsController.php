<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\CompannySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'vat' => 'required|numeric|max:99',
            'order_status' => 'required|in:0,1',
            'max_win_amount' => 'required|numeric',
            'customer_phone_require' => 'required|numeric|in:0,1'
        ]);

        $company = CompannySetting::first();
        if (!$company) {
            $company = new CompannySetting();
        }
        $company->name = $request->name;
        $company->address = $request->address;
        $company->phone = $request->phone;
        $company->whatsapp = $request->whatsapp;
        $company->trn_no = $request->trn_no;
        $company->currency = $request->currency;
        $company->email = $request->email;
        $company->website = $request->website;
        $company->licence_no = $request->licence_no;
        $company->bank_account = $request->bank_account;
        $company->vat = $request->vat;
        $company->order_status = $request->order_status;
        $company->max_win_amount = $request->max_win_amount;
        $company->customer_phone_require = $request->customer_phone_require;

        if ($request->hasFile('logo')) {
            if($company->logo && file_exists(public_path($company->logo))){
                unlink(public_path($company->logo));
            }
            $file = $request->file('logo');
            $file_name = 'company-logo'.time() . '.' . $file->extension();
            $path = $file->storeAs('uploads/company', $file_name);
            $company->logo = $path;
        }

        $company->save();

        Cache::forget('company_setting');

        return redirect()->back()->with('success', 'Company setting created successfully.');
    }
}
