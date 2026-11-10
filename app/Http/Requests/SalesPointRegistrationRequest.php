<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared validation for creating and updating a Sales Point registration.
 * Authorization is enforced by the `can:` route middleware.
 */
class SalesPointRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCreate = $this->isMethod('post') && ! $this->route('registration');
        $docTypes = array_keys(config('sales_point_agreement.document_types'));
        $phone = ['required', 'string', 'max:20', 'regex:/^[0-9\s\-]{6,20}$/'];
        $signature = ($isCreate ? 'required' : 'nullable').'|string|max:2800000';

        $rules = [
            'shop_name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'passport_no' => 'required|string|max:50',
            'uid_no' => 'required|string|max:50',
            'eid_no' => ['required', 'string', 'max:25', 'regex:/^784-?\d{4}-?\d{7}-?\d$/'],
            'trade_license_no' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'mobile_country_code' => ['nullable', 'string', 'max:8', 'regex:/^\+?\d{1,4}$/'],
            'mobile' => $phone,
            'whatsapp_country_code' => ['nullable', 'string', 'max:8', 'regex:/^\+?\d{1,4}$/'],
            'whatsapp' => $phone,
            'address' => 'required|string|max:1000',
            'location_map' => 'required|url:http,https|max:2048',
            'photo' => 'nullable|file|image|mimes:jpeg,jpg,png,webp|max:5120|dimensions:max_width=4000,max_height=4000',

            'products' => 'nullable|array',
            'products.chocolate' => 'boolean',
            'products.attar' => 'boolean',
            'products.tasbih' => 'boolean',
            'products.pen' => 'boolean',
            'products.others' => 'boolean',
            'products_other_text' => 'nullable|string|max:255|required_if:products.others,1,true',

            'dev_tab' => 'boolean',
            'dev_pos_machine' => 'boolean',
            'dev_sim_card' => 'boolean',
            'dev_active_data' => 'boolean',
            'sim_card_provided' => 'boolean',
            'delivery_date' => 'required|date',
            'delivery_time' => 'required|date_format:H:i',

            'person_signature' => $signature,
            'company_signature' => $signature,
            'signed_date' => 'required|date',
            'signed_place' => 'required|string|max:255',

            'documents' => 'nullable|array',
        ];

        foreach ($docTypes as $type) {
            $rules["documents.$type"] = 'nullable|file|mimes:jpeg,jpg,png,webp,pdf|max:8192';
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'eid_no' => 'Emirates ID number',
            'uid_no' => 'UID number',
            'location_map' => 'location map link',
            'person_signature' => 'authorized person signature',
            'company_signature' => 'company representative signature',
        ];
    }

    public function messages(): array
    {
        return [
            'eid_no.regex' => 'Emirates ID must look like 784-YYYY-NNNNNNN-N.',
            'products_other_text.required_if' => 'Please describe the other products.',
        ];
    }
}
