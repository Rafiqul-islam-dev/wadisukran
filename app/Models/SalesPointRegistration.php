<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesPointRegistration extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'reference_no', 'status',
        'shop_name', 'owner_name', 'passport_no', 'uid_no', 'eid_no', 'trade_license_no', 'email',
        'mobile_country_code', 'mobile', 'whatsapp_country_code', 'whatsapp', 'address', 'location_map', 'photo_path',
        'products', 'products_other_text',
        'dev_tab', 'dev_pos_machine', 'dev_sim_card', 'dev_active_data', 'sim_card_provided', 'delivery_date', 'delivery_time',
        'person_signature_path', 'company_signature_path', 'signed_date', 'signed_place',
        'created_by', 'approved_by', 'approved_at', 'rejected_by', 'rejected_at', 'reject_reason',
        'terms_version', 'terms_snapshot', 'pdf_path', 'pdf_generated_at',
    ];

    /** Never serialise sensitive values / internal storage paths by accident. */
    protected $hidden = [
        'passport_no', 'uid_no', 'eid_no',
        'photo_path', 'person_signature_path', 'company_signature_path', 'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'passport_no' => 'encrypted',
            'uid_no' => 'encrypted',
            'eid_no' => 'encrypted',
            'products' => 'array',
            'terms_snapshot' => 'array',
            'dev_tab' => 'boolean',
            'dev_pos_machine' => 'boolean',
            'dev_sim_card' => 'boolean',
            'dev_active_data' => 'boolean',
            'sim_card_provided' => 'boolean',
            'delivery_date' => 'date:Y-m-d',
            'signed_date' => 'date:Y-m-d',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'pdf_generated_at' => 'datetime',
        ];
    }

    public function documents()
    {
        return $this->hasMany(SalesPointRegistrationDocument::class, 'registration_id');
    }

    public function events()
    {
        return $this->hasMany(SalesPointRegistrationEvent::class, 'registration_id')->latest('id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by')->withTrashed();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_REJECTED], true);
    }
}
