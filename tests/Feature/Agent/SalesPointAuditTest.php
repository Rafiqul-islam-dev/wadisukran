<?php

// Helpers (sprUser, sprCreate, sprPayload, ...) live in SalesPointRegistrationTest.php; SPR_ALL in SalesPointApprovalTest.php.

use App\Models\CompannySetting;
use App\Models\SalesPointRegistration;
use App\Models\SalesPointRegistrationEvent;
use App\Services\SalesPointAgreementPdfService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('private');
    cache()->forget('company_setting');
    CompannySetting::create(['name' => 'Wadi Shukran']);
});

function sprActions(SalesPointRegistration $r): array
{
    return SalesPointRegistrationEvent::where('registration_id', $r->id)->orderBy('id')->pluck('action')->all();
}

test('the whole life of a registration is written to the audit trail with who and where from', function () {
    $user = sprUser(SPR_ALL);
    $this->actingAs($user)->post('/agents/registrations/store', sprPayload() + [
        'documents' => ['passport' => UploadedFile::fake()->image('p.png')],
    ])->assertSessionHasNoErrors();
    $reg = SalesPointRegistration::firstOrFail();

    $this->post("/agents/registrations/update/{$reg->id}", sprPayload(['shop_name' => 'Edited']));
    $this->post("/agents/registrations/{$reg->id}/reject", ['reason' => 'Needs a clearer photo']);
    $this->post("/agents/registrations/update/{$reg->id}", sprPayload());
    $this->post("/agents/registrations/{$reg->id}/approve");
    $this->get("/agents/registrations/{$reg->id}/files/passport");
    $this->get("/agents/registrations/{$reg->id}/pdf");
    $this->get("/agents/registrations/{$reg->id}/pdf?download=1");
    $this->post("/agents/registrations/{$reg->id}/regenerate-pdf");

    expect(sprActions($reg))->toBe([
        'created', 'updated', 'rejected', 'updated', 'approved', 'document_viewed', 'pdf_viewed', 'pdf_downloaded', 'pdf_regenerated',
    ]);

    $rejected = SalesPointRegistrationEvent::where('action', 'rejected')->first();
    expect($rejected->user_id)->toBe($user->id)
        ->and($rejected->meta['reason'])->toBe('Needs a clearer photo')
        ->and($rejected->ip_address)->not->toBeNull()
        ->and(SalesPointRegistrationEvent::where('action', 'document_viewed')->first()->meta['kind'])->toBe('passport');
});

test('a failed approval leaves no audit entry behind', function () {
    $user = sprUser(SPR_ALL);
    $reg = sprCreate($user);
    $this->mock(SalesPointAgreementPdfService::class, fn ($m) => $m->shouldReceive('render')->andThrow(new RuntimeException('boom')));

    $this->actingAs($user)->post("/agents/registrations/{$reg->id}/approve")->assertSessionHas('error');

    expect(sprActions($reg))->toBe(['created']);
});

test('the Show page returns the activity with user names', function () {
    $user = sprUser(SPR_ALL);
    $reg = sprCreate($user);

    $events = $this->actingAs($user)->get("/agents/registrations/{$reg->id}", sprInertia())->json('props.registration.events');

    expect($events)->toHaveCount(1)->and($events[0]['action'])->toBe('created')->and($events[0]['user']['name'])->toBe($user->name);
});

test('duplicates are flagged by trade licence or email, excluding the registration itself', function () {
    $user = sprUser(SPR_ALL);
    $a = sprCreate($user, ['trade_license_no' => 'TL-DUP-1', 'email' => 'dup@example.test']);
    $this->actingAs($user);

    $res = $this->getJson('/agents/registrations/duplicates?trade_license_no=TL-DUP-1&email=other@example.test')->assertOk();
    expect($res->json('matches'))->toHaveCount(1)
        ->and($res->json('matches.0.reference_no'))->toBe($a->reference_no)
        ->and($res->json('matches.0.same'))->toBe(['trade license']);

    $byEmail = $this->getJson('/agents/registrations/duplicates?email=DUP@example.test')->json('matches');
    expect($byEmail)->toHaveCount(1)->and($byEmail[0]['same'])->toBe(['email']);

    // editing the same record must not warn about itself
    expect($this->getJson("/agents/registrations/duplicates?trade_license_no=TL-DUP-1&exclude={$a->id}")->json('matches'))->toBeEmpty();
    expect($this->getJson('/agents/registrations/duplicates?trade_license_no=NOPE-NOPE')->json('matches'))->toBeEmpty();
    expect($this->getJson('/agents/registrations/duplicates')->json('matches'))->toBeEmpty();
});

test('the duplicate check needs the create permission and never exposes ID numbers', function () {
    $owner = sprUser(SPR_ALL);
    sprCreate($owner, ['trade_license_no' => 'TL-X']);

    $this->actingAs(sprUser(['show sales point registrations']))->getJson('/agents/registrations/duplicates?trade_license_no=TL-X')->assertForbidden();

    $json = $this->actingAs($owner)->getJson('/agents/registrations/duplicates?trade_license_no=TL-X')->getContent();
    expect($json)->not->toContain('N1234567')->not->toContain('784-1985');
});
