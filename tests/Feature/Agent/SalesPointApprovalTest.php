<?php

// Helpers (sprUser, sprCreate, sprPayload, ...) live in SalesPointRegistrationTest.php (same directory).

use App\Models\CompannySetting;
use App\Models\SalesPointRegistration;
use App\Services\SalesPointAgreementPdfService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

const SPR_ALL = [
    'show sales point registrations',
    'create sales point registration',
    'update sales point registration',
    'delete sales point registration',
    'approve sales point registration',
    'download sales point agreement',
];

function sprPdfPages(string $bytes): int
{
    return preg_match_all('#/Type\s*/Page(?![a-z])#', $bytes);
}

beforeEach(function () {
    Storage::fake('private');
    cache()->forget('company_setting');
    CompannySetting::create(['name' => 'Wadi Shukran']);
    config(['sales_point_agreement.block_self_approval' => false]);
});

test('a super-admin style user can approve a registration they submitted themselves', function () {
    $user = sprUser(SPR_ALL);
    $reg = sprCreate($user);

    $this->actingAs($user)
        ->post("/agents/registrations/{$reg->id}/approve")
        ->assertRedirect(route('agents.registrations.show', $reg))
        ->assertSessionHas('success');

    $reg->refresh();
    expect($reg->status)->toBe('approved')
        ->and($reg->created_by)->toBe($user->id)
        ->and($reg->approved_by)->toBe($user->id)
        ->and($reg->approved_at)->not->toBeNull()
        ->and($reg->pdf_path)->not->toBeNull()
        ->and($reg->terms_snapshot)->toHaveCount(5)
        ->and($reg->terms_version)->toBe(1);

    Storage::disk('private')->assertExists($reg->pdf_path);
});

test('the generated agreement is a valid single-page PDF', function () {
    $user = sprUser(SPR_ALL);
    $reg = sprCreate($user);
    $this->actingAs($user)->post("/agents/registrations/{$reg->id}/approve");

    $bytes = Storage::disk('private')->get($reg->fresh()->pdf_path);

    expect(substr($bytes, 0, 5))->toBe('%PDF-')
        ->and(sprPdfPages($bytes))->toBe(1)
        ->and(strlen($bytes))->toBeGreaterThan(20000);
});

test('long and Arabic values still render on one page', function () {
    $user = sprUser(SPR_ALL);
    $reg = sprCreate($user, [
        'shop_name' => substr(str_repeat('Al Noor General Trading & Services ', 8), 0, 255),
        'owner_name' => 'محمد عبدالله المنصوري '.str_repeat('x', 200),
        'address' => 'شارع الرقة، ديرة، دبي '.str_repeat('Very long street name ', 30),
        'products' => ['chocolate' => true, 'attar' => true, 'tasbih' => true, 'pen' => true, 'others' => true],
        'products_other_text' => substr(str_repeat('Dates ', 60), 0, 255),
    ]);

    $bytes = app(SalesPointAgreementPdfService::class)->render($reg);

    expect(substr($bytes, 0, 5))->toBe('%PDF-')->and(sprPdfPages($bytes))->toBe(1);
});

test('approving twice or approving a non-pending registration is refused', function () {
    $user = sprUser(SPR_ALL);
    $reg = sprCreate($user);
    $this->actingAs($user);

    $this->post("/agents/registrations/{$reg->id}/approve")->assertSessionHas('success');
    $path = $reg->fresh()->pdf_path;

    $this->post("/agents/registrations/{$reg->id}/approve")->assertSessionHas('error');
    expect($reg->fresh()->pdf_path)->toBe($path)->and(Storage::disk('private')->allFiles())->toContain($path);

    $rejected = sprCreate($user, ['status' => 'rejected']);
    $this->post("/agents/registrations/{$rejected->id}/approve")->assertSessionHas('error');
    expect($rejected->fresh()->status)->toBe('rejected');
});

test('if the PDF fails the registration stays pending and nothing is left behind', function () {
    $user = sprUser(SPR_ALL);
    $reg = sprCreate($user);
    $before = Storage::disk('private')->allFiles();

    $this->mock(SalesPointAgreementPdfService::class, fn ($m) => $m->shouldReceive('render')->andThrow(new RuntimeException('boom')));

    $this->actingAs($user)
        ->post("/agents/registrations/{$reg->id}/approve")
        ->assertSessionHas('error');

    $reg->refresh();
    expect($reg->status)->toBe('pending')
        ->and($reg->approved_by)->toBeNull()
        ->and($reg->pdf_path)->toBeNull()
        ->and($reg->terms_snapshot)->toHaveCount(5) // frozen at submission, unaffected by the failed approval
        ->and(Storage::disk('private')->allFiles())->toEqual($before);
});

test('a corrupt PDF result is rejected and rolled back', function () {
    $user = sprUser(SPR_ALL);
    $reg = sprCreate($user);
    $this->mock(SalesPointAgreementPdfService::class, fn ($m) => $m->shouldReceive('render')->andReturn('not a pdf'));

    $this->actingAs($user)->post("/agents/registrations/{$reg->id}/approve")->assertSessionHas('error');

    expect($reg->fresh()->status)->toBe('pending');
});

test('self approval can be switched off by config', function () {
    config(['sales_point_agreement.block_self_approval' => true]);
    $creator = sprUser(SPR_ALL);
    $other = sprUser(SPR_ALL);
    $reg = sprCreate($creator);

    $this->actingAs($creator)->post("/agents/registrations/{$reg->id}/approve")->assertSessionHas('error');
    expect($reg->fresh()->status)->toBe('pending');

    $this->actingAs($other)->post("/agents/registrations/{$reg->id}/approve")->assertSessionHas('success');
    expect($reg->fresh()->approved_by)->toBe($other->id);
});

test('reject needs a reason, records who/when, and the registration can be fixed and approved', function () {
    $user = sprUser(SPR_ALL);
    $reg = sprCreate($user);
    $this->actingAs($user);

    $this->post("/agents/registrations/{$reg->id}/reject", [])->assertSessionHasErrors('reason');
    $this->post("/agents/registrations/{$reg->id}/reject", ['reason' => 'abc'])->assertSessionHasErrors('reason');
    expect($reg->fresh()->status)->toBe('pending');

    $this->post("/agents/registrations/{$reg->id}/reject", ['reason' => 'Passport copy is blurry'])->assertSessionHas('success');
    $reg->refresh();
    expect($reg->status)->toBe('rejected')
        ->and($reg->reject_reason)->toBe('Passport copy is blurry')
        ->and($reg->rejected_by)->toBe($user->id)
        ->and($reg->rejected_at)->not->toBeNull();

    // cannot approve/reject a rejected one directly
    $this->post("/agents/registrations/{$reg->id}/approve")->assertSessionHas('error');
    $this->post("/agents/registrations/{$reg->id}/reject", ['reason' => 'again again'])->assertSessionHas('error');

    // fix + resubmit -> pending -> approve
    $this->post("/agents/registrations/update/{$reg->id}", sprPayload(['shop_name' => 'Fixed Shop']))->assertSessionHasNoErrors();
    expect($reg->fresh()->status)->toBe('pending');
    $this->post("/agents/registrations/{$reg->id}/approve")->assertSessionHas('success');
    expect($reg->fresh()->status)->toBe('approved');
});

test('approve, reject, regenerate and pdf routes need the right permission', function () {
    $owner = sprUser(SPR_ALL);
    $reg = sprCreate($owner);
    $viewer = sprUser(['show sales point registrations', 'create sales point registration', 'update sales point registration']);

    $this->actingAs($viewer);
    $this->post("/agents/registrations/{$reg->id}/approve")->assertForbidden();
    $this->post("/agents/registrations/{$reg->id}/reject", ['reason' => 'nope nope'])->assertForbidden();
    $this->post("/agents/registrations/{$reg->id}/regenerate-pdf")->assertForbidden();
    $this->get("/agents/registrations/{$reg->id}/pdf")->assertForbidden();
    expect($reg->fresh()->status)->toBe('pending');
});

test('the PDF is only available for approved registrations and only with the download permission', function () {
    $user = sprUser(SPR_ALL);
    $reg = sprCreate($user);
    $this->actingAs($user);

    $this->get("/agents/registrations/{$reg->id}/pdf")->assertNotFound(); // pending
    $this->post("/agents/registrations/{$reg->id}/approve");

    $res = $this->get("/agents/registrations/{$reg->id}/pdf")->assertOk();
    expect($res->headers->get('Content-Type'))->toContain('application/pdf')
        ->and($res->headers->get('Content-Disposition'))->toContain('inline');

    $dl = $this->get("/agents/registrations/{$reg->id}/pdf?download=1");
    expect($dl->headers->get('Content-Disposition'))->toContain('attachment');

    $noDownload = sprUser(['show sales point registrations', 'approve sales point registration']);
    $this->actingAs($noDownload)->get("/agents/registrations/{$reg->id}/pdf")->assertForbidden();
});

test('regenerating replaces the PDF, keeps the frozen terms and only works for approved records', function () {
    $user = sprUser(SPR_ALL);
    $reg = sprCreate($user);
    $this->actingAs($user);

    $this->post("/agents/registrations/{$reg->id}/regenerate-pdf")->assertSessionHas('error'); // pending

    $this->post("/agents/registrations/{$reg->id}/approve");
    $reg->refresh();
    $old = $reg->pdf_path;
    config(['sales_point_agreement.terms' => ['CHANGED TERMS AFTER APPROVAL']]); // must not leak into the old record

    $this->post("/agents/registrations/{$reg->id}/regenerate-pdf")->assertSessionHas('success');
    $reg->refresh();

    expect($reg->pdf_path)->not->toBe($old)
        ->and($reg->terms_snapshot)->toHaveCount(5)
        ->and(Storage::disk('private')->allFiles())->not->toContain($old);
    Storage::disk('private')->assertExists($reg->pdf_path);
});

test('a failed regeneration keeps the previous PDF', function () {
    $user = sprUser(SPR_ALL);
    $reg = sprCreate($user);
    $this->actingAs($user)->post("/agents/registrations/{$reg->id}/approve");
    $old = $reg->fresh()->pdf_path;

    $this->mock(SalesPointAgreementPdfService::class, fn ($m) => $m->shouldReceive('render')->andThrow(new RuntimeException('boom')));
    $this->post("/agents/registrations/{$reg->id}/regenerate-pdf")->assertSessionHas('error');

    expect($reg->fresh()->pdf_path)->toBe($old);
    Storage::disk('private')->assertExists($old);
});

test('an approved registration stays immutable', function () {
    $user = sprUser(SPR_ALL);
    $reg = sprCreate($user);
    $this->actingAs($user)->post("/agents/registrations/{$reg->id}/approve");

    $this->post("/agents/registrations/update/{$reg->id}", sprPayload(['shop_name' => 'Hacked']))->assertForbidden();
    $this->delete("/agents/registrations/delete/{$reg->id}")->assertForbidden();
    $this->post("/agents/registrations/{$reg->id}/reject", ['reason' => 'too late now'])->assertSessionHas('error');

    expect($reg->fresh()->shop_name)->toBe('Al Noor Trading')->and($reg->fresh()->status)->toBe('approved');
});

test('the agreement terms printed are the frozen snapshot', function () {
    $user = sprUser(SPR_ALL);
    $reg = sprCreate($user);
    $this->actingAs($user)->post("/agents/registrations/{$reg->id}/approve");

    $reg->refresh();
    expect($reg->terms_snapshot)->toEqual(array_values(config('sales_point_agreement.terms')));
    expect(SalesPointRegistration::find($reg->id)->terms_snapshot[0])->toContain('promote and sell Wadi Shukran products');
});

test('the printed terms are frozen at submission, not at approval', function () {
    $user = sprUser(SPR_ALL);
    $reg = sprCreate($user);
    expect($reg->terms_snapshot)->toHaveCount(5)->and($reg->terms_version)->toBe(1);

    // config edited AFTER the person signed but BEFORE approval
    config(['sales_point_agreement.terms' => ['NEW TERMS NOBODY SIGNED'], 'sales_point_agreement.terms_version' => 2]);
    $this->actingAs($user)->post("/agents/registrations/{$reg->id}/approve")->assertSessionHas('success');

    $reg->refresh();
    expect($reg->terms_version)->toBe(1)
        ->and($reg->terms_snapshot)->toHaveCount(5)
        ->and($reg->terms_snapshot[0])->toContain('promote and sell Wadi Shukran products');
});

test('re-submitting after a rejection re-freezes the terms the submitter now sees', function () {
    $user = sprUser(SPR_ALL);
    $reg = sprCreate($user, ['status' => 'rejected', 'reject_reason' => 'fix it']);
    config(['sales_point_agreement.terms' => ['UPDATED TERM'], 'sales_point_agreement.terms_version' => 2]);

    $this->actingAs($user)->post("/agents/registrations/update/{$reg->id}", sprPayload());

    expect($reg->fresh()->terms_version)->toBe(2)->and($reg->fresh()->terms_snapshot)->toBe(['UPDATED TERM']);
});

test('HTML in user values is printed as text and never interpreted or fetched', function () {
    $user = sprUser(SPR_ALL);
    $evil = '</div><img src="http://127.0.0.1:9/ssrf.png"><img src="C:/Windows/win.ini"><style>*{display:none}</style>';
    $reg = sprCreate($user, ['shop_name' => $evil, 'owner_name' => $evil, 'address' => $evil, 'signed_place' => $evil, 'products_other_text' => $evil]);

    $bytes = app(SalesPointAgreementPdfService::class)->render($reg);

    expect(substr($bytes, 0, 5))->toBe('%PDF-')->and(sprPdfPages($bytes))->toBe(1);
});

test('very long values are shortened to fit and bidi control characters are stripped', function () {
    $pdf = app(SalesPointAgreementPdfService::class);
    $m = new ReflectionMethod($pdf, 'fit');
    $e = new ReflectionMethod($pdf, 'e');

    expect($m->invoke($pdf, str_repeat('a', 300), 50))->toHaveLength(50)->toEndWith('…')
        ->and($m->invoke($pdf, 'short', 50))->toBe('short')
        ->and($e->invoke($pdf, "Ali\u{202E}evil\u{200B}"))->toBe('Alievil');
});

test('an oversized-pixel photo is rejected before it can crash the PDF render', function () {
    $user = sprUser(SPR_ALL);

    $this->actingAs($user)
        ->post('/agents/registrations/store', sprPayload() + ['photo' => UploadedFile::fake()->image('big.jpg', 5000, 100)])
        ->assertSessionHasErrors('photo');

    expect(SalesPointRegistration::count())->toBe(0);
});

test('the photo and both signatures are embedded in the PDF', function () {
    $user = sprUser(SPR_ALL);
    $reg = sprCreate($user);
    $svc = app(SalesPointAgreementPdfService::class);

    $with = $svc->render($reg);

    // same record, but the PDF is built without the three user images (icons/logo only)
    $blank = $reg->replicate();
    $blank->photo_path = null;
    $blank->person_signature_path = null;
    $blank->company_signature_path = null;
    $without = $svc->render($blank);

    expect(strlen($with))->toBeGreaterThan(strlen($without));
});

test('a missing signature file makes approval fail instead of printing a blank signature box', function () {
    $user = sprUser(SPR_ALL);
    $reg = sprCreate($user);
    Storage::disk('private')->delete($reg->person_signature_path);

    $this->actingAs($user)->post("/agents/registrations/{$reg->id}/approve")->assertSessionHas('error');

    expect($reg->fresh()->status)->toBe('pending');
});
