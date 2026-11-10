<?php

/*
|--------------------------------------------------------------------------
| Sales Point Registration & Agreement
|--------------------------------------------------------------------------
| Terms text is FINAL (owner-confirmed, see .claude/workflows/sales-point-registration.md §4.1).
| Bump `terms_version` whenever the text changes; each approved registration snapshots
| the exact terms it was signed with.
*/

return [
    'terms_version' => 1,

    'terms' => [
        'The Sales Point / Authorized Person agrees to promote and sell Wadi Shukran products as per company policy and guidelines.',
        'The Sales Point / Authorized Person agrees to keep the provided device(s) in good condition and use them only for Wadi Shukran business.',
        'The Sales Point / Authorized Person agrees to provide accurate information and cooperate with the company at all times.',
        'Any loss, damage or misuse of company property (device, SIM, etc.) will be the responsibility of the Sales Point / Authorized Person.',
        'If the Sales Point / Authorized Person takes or receives a SIM Card from Wadi Shukran, all responsibility and liability for that SIM Card shall remain entirely with the Sales Point / Authorized Person from the exact date and time of delivery until the SIM Card is finally returned to or collected by Wadi Shukran. Any misuse, unauthorized use, unlawful use, loss, or improper use of the SIM Card during this period shall be the sole responsibility of the Sales Point / Authorized Person. This Agreement is also based on trust (amanah) and breaching it equals to breach of trust.',
    ],

    // When true, the user who created a registration cannot approve/reject it.
    // Owner decision: any user with the approve permission (e.g. Super Admin) may approve/reject ANY registration,
    // including one they submitted. Keep false unless the owner changes that rule.
    'block_self_approval' => (bool) env('SALES_POINT_BLOCK_SELF_APPROVAL', false),

    // Uploadable document types (key => label EN). Keys are also the file "kind" in the files route.
    'document_types' => [
        'trade_license' => 'Trade License Copy',
        'passport' => 'Passport Copy',
        'visa' => 'Visa Copy',
        'emirates_id' => 'Emirates ID Copy',
        'business_card' => 'Business Card',
        'shop_picture' => 'Shop Picture',
    ],

    'disk' => 'private',
];
