<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\CompannySetting;
use App\Models\SalesPointRegistration;
use App\Models\User;
use App\Services\SalesPointRegistrationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

const SPR_PERMISSIONS = [
    'show sales point registrations',
    'create sales point registration',
    'update sales point registration',
    'delete sales point registration',
];

function sprUser(array $permissions = []): User
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user = User::factory()->create(['join_date' => now(), 'status' => 'active', 'user_type' => 'admin']);
    foreach ($permissions as $name) {
        Permission::findOrCreate($name, 'web');
    }
    $user->givePermissionTo($permissions);

    return $user;
}

function sprSignature(): string
{
    $img = imagecreatetruecolor(120, 40);
    imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
    imageline($img, 5, 5, 100, 30, imagecolorallocate($img, 0, 0, 0));
    ob_start();
    imagepng($img);

    return 'data:image/png;base64,'.base64_encode(ob_get_clean());
}

function sprPayload(array $override = []): array
{
    return array_replace_recursive([
        'shop_name' => 'Al Noor Trading',
        'owner_name' => 'Mohammed Ali',
        'passport_no' => 'N1234567',
        'uid_no' => '1020304050',
        'eid_no' => '784-1985-1234567-1',
        'trade_license_no' => 'TL-998877',
        'email' => 'owner@alnoor.test',
        'mobile_country_code' => '+971',
        'mobile' => '50 123 4567',
        'whatsapp_country_code' => '+971',
        'whatsapp' => '50 123 4567',
        'address' => 'Shop 12, Deira, Dubai',
        'location_map' => 'https://maps.google.com/?q=25.27,55.30',
        'products' => ['chocolate' => 1, 'attar' => 0, 'tasbih' => 1, 'pen' => 0, 'others' => 0],
        'dev_tab' => 1,
        'dev_pos_machine' => 0,
        'dev_sim_card' => 1,
        'dev_active_data' => 0,
        'sim_card_provided' => 1,
        'delivery_date' => '2026-11-20',
        'delivery_time' => '11:30',
        'person_signature' => sprSignature(),
        'company_signature' => sprSignature(),
        'signed_date' => '2026-11-10',
        'signed_place' => 'Dubai',
    ], $override);
}

/** Headers for an Inertia XHR visit. The asset version changes once `npm run build` has produced a manifest. */
function sprInertia(): array
{
    return [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
    ];
}

function sprCreate(User $by, array $attrs = []): SalesPointRegistration
{
    $data = sprPayload();
    $data['photo'] = null;
    $data['documents'] = [];
    $reg = app(SalesPointRegistrationService::class)->create($data, $by->id);
    if ($attrs) {
        $reg->forceFill($attrs)->save();
    }

    return $reg->fresh();
}

beforeEach(function () {
    Storage::fake('private');
    // `company_setting()` (shared Inertia prop) needs the single settings row; it is cached.
    cache()->forget('company_setting');
    CompannySetting::create(['name' => 'Wadi Shukran']);
});

test('guests are redirected to login', function () {
    $this->get('/agents/registrations')->assertRedirect('/login');
});

test('every route requires its own permission', function () {
    $nobody = sprUser();
    $owner = sprUser(SPR_PERMISSIONS);
    $reg = sprCreate($owner);
    $this->actingAs($nobody);

    $this->get('/agents/registrations')->assertForbidden();
    $this->get('/agents/registrations/create')->assertForbidden();
    $this->post('/agents/registrations/store', sprPayload())->assertForbidden();
    $this->get("/agents/registrations/{$reg->id}")->assertForbidden();
    $this->get("/agents/registrations/{$reg->id}/edit")->assertForbidden();
    $this->post("/agents/registrations/update/{$reg->id}", sprPayload())->assertForbidden();
    $this->delete("/agents/registrations/delete/{$reg->id}")->assertForbidden();
    $this->get("/agents/registrations/{$reg->id}/files/person_signature")->assertForbidden();
});

test('menu access alone is enough - no agent list permission needed', function () {
    $user = sprUser(['show sales point registrations']);

    $this->actingAs($user)
        ->get('/agents/registrations', sprInertia())
        ->assertOk()
        ->assertJsonPath('component', 'Agent/Registration/Index');
});

test('a registration can be created with files and starts pending', function () {
    $user = sprUser(SPR_PERMISSIONS);

    $response = $this->actingAs($user)->post('/agents/registrations/store', sprPayload() + [
        'photo' => UploadedFile::fake()->image('me.jpg', 300, 400),
        'documents' => [
            'passport' => UploadedFile::fake()->image('passport.png'),
            'trade_license' => UploadedFile::fake()->create('licence.pdf', 100, 'application/pdf'),
        ],
    ]);

    $reg = SalesPointRegistration::firstOrFail();
    $response->assertRedirect(route('agents.registrations.show', $reg));

    expect($reg->reference_no)->toBe('SPR-'.str_pad((string) $reg->id, 4, '0', STR_PAD_LEFT))
        ->and($reg->status)->toBe('pending')
        ->and($reg->created_by)->toBe($user->id)
        ->and($reg->products['chocolate'])->toBeTrue()
        ->and($reg->products['attar'])->toBeFalse()
        ->and($reg->terms_version)->toBe(1)
        ->and($reg->documents)->toHaveCount(2);

    Storage::disk('private')->assertExists($reg->photo_path);
    Storage::disk('private')->assertExists($reg->person_signature_path);
    Storage::disk('private')->assertExists($reg->company_signature_path);
    foreach ($reg->documents as $doc) {
        Storage::disk('private')->assertExists($doc->makeVisible('path')->path);
    }
});

test('ID numbers are encrypted at rest and hidden from serialisation', function () {
    $reg = sprCreate(sprUser(SPR_PERMISSIONS));
    $raw = DB::table('sales_point_registrations')->where('id', $reg->id)->first();

    expect($raw->passport_no)->not->toContain('N1234567')
        ->and($raw->eid_no)->not->toContain('784')
        ->and($reg->passport_no)->toBe('N1234567')
        ->and($reg->toArray())->not->toHaveKeys(['passport_no', 'uid_no', 'eid_no', 'photo_path', 'person_signature_path']);
});

test('sensitive files are never stored under the public web root', function () {
    $reg = sprCreate(sprUser(SPR_PERMISSIONS));

    // Storage::fake() replaces the disk root in beforeEach, so assert against the real config file.
    $real = require config_path('filesystems.php');
    $root = str_replace('\\', '/', $real['disks']['private']['root']);

    expect($root)->not->toStartWith(str_replace('\\', '/', public_path()))
        ->and($real['disks']['private']['throw'])->toBeTrue()
        ->and(file_exists(public_path($reg->person_signature_path)))->toBeFalse();
});

test('a blank (empty canvas) signature is rejected and reported on the right field', function () {
    $user = sprUser(SPR_PERMISSIONS);
    $blank = imagecreatetruecolor(120, 40);
    imagefill($blank, 0, 0, imagecolorallocate($blank, 255, 255, 255));
    ob_start();
    imagepng($blank);
    $url = 'data:image/png;base64,'.base64_encode(ob_get_clean());

    $this->actingAs($user)
        ->post('/agents/registrations/store', sprPayload(['company_signature' => $url]))
        ->assertSessionHasErrors('company_signature')
        ->assertSessionDoesntHaveErrors('person_signature');

    expect(SalesPointRegistration::count())->toBe(0)->and(Storage::disk('private')->allFiles())->toBeEmpty();
});

test('location map only accepts http(s) links', function () {
    $this->actingAs(sprUser(SPR_PERMISSIONS))
        ->post('/agents/registrations/store', sprPayload(['location_map' => 'javascript://example.com/%0Aalert(1)']))
        ->assertSessionHasErrors('location_map');
});

test('required fields and formats are validated', function () {
    $user = sprUser(SPR_PERMISSIONS);
    $this->actingAs($user);

    $this->post('/agents/registrations/store', [])->assertSessionHasErrors([
        'shop_name', 'owner_name', 'passport_no', 'uid_no', 'eid_no', 'trade_license_no', 'email', 'mobile',
        'whatsapp', 'address', 'location_map', 'delivery_date', 'delivery_time', 'person_signature',
        'company_signature', 'signed_date', 'signed_place',
    ]);

    $this->post('/agents/registrations/store', sprPayload(['eid_no' => '12345']))->assertSessionHasErrors('eid_no');
    $this->post('/agents/registrations/store', sprPayload(['location_map' => 'not a url']))->assertSessionHasErrors('location_map');
    $this->post('/agents/registrations/store', sprPayload(['delivery_time' => '25:99']))->assertSessionHasErrors('delivery_time');
    $this->post('/agents/registrations/store', sprPayload(['products' => ['others' => 1]]))->assertSessionHasErrors('products_other_text');
    $this->post('/agents/registrations/store', sprPayload() + ['photo' => UploadedFile::fake()->create('x.exe', 10)])->assertSessionHasErrors('photo');

    expect(SalesPointRegistration::count())->toBe(0);
});

test('a non-PNG or garbage signature is rejected and nothing is stored', function () {
    $user = sprUser(SPR_PERMISSIONS);

    $this->actingAs($user)
        ->post('/agents/registrations/store', sprPayload(['person_signature' => 'data:image/png;base64,'.base64_encode('<?php evil(); ?>')]))
        ->assertSessionHasErrors('person_signature');

    expect(SalesPointRegistration::count())->toBe(0)
        ->and(Storage::disk('private')->allFiles())->toBeEmpty();
});

test('private files stream only to permitted users and unknown kinds 404', function () {
    $owner = sprUser(SPR_PERMISSIONS);
    $reg = sprCreate($owner);

    $this->actingAs($owner)
        ->get("/agents/registrations/{$reg->id}/files/person_signature")
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $this->get("/agents/registrations/{$reg->id}/files/photo")->assertNotFound(); // none uploaded
    $this->get("/agents/registrations/{$reg->id}/files/..%2F..%2F.env")->assertNotFound();
    $this->get("/agents/registrations/{$reg->id}/files/passport")->assertNotFound();
});

test('editing a rejected registration re-submits it as pending', function () {
    $user = sprUser(SPR_PERMISSIONS);
    $reg = sprCreate($user, ['status' => 'rejected', 'rejected_by' => $user->id, 'rejected_at' => now(), 'reject_reason' => 'blurry']);
    $oldSignature = $reg->person_signature_path;

    $this->actingAs($user)
        ->post("/agents/registrations/update/{$reg->id}", sprPayload(['shop_name' => 'Renamed Shop', 'person_signature' => sprSignature()]))
        ->assertRedirect();

    $reg->refresh();
    expect($reg->status)->toBe('pending')
        ->and($reg->shop_name)->toBe('Renamed Shop')
        ->and($reg->reject_reason)->toBeNull()
        ->and($reg->rejected_by)->toBeNull();
    Storage::disk('private')->assertMissing($oldSignature);
    Storage::disk('private')->assertExists($reg->person_signature_path);
});

test('update keeps existing signatures when none are re-sent', function () {
    $user = sprUser(SPR_PERMISSIONS);
    $reg = sprCreate($user);

    $this->actingAs($user)
        ->post("/agents/registrations/update/{$reg->id}", sprPayload(['person_signature' => null, 'company_signature' => null]))
        ->assertSessionHasNoErrors();

    expect($reg->fresh()->person_signature_path)->toBe($reg->person_signature_path);
});

test('approved registrations cannot be edited or deleted', function () {
    $user = sprUser(SPR_PERMISSIONS);
    $reg = sprCreate($user, ['status' => 'approved', 'approved_by' => $user->id, 'approved_at' => now()]);
    $this->actingAs($user);

    $this->get("/agents/registrations/{$reg->id}/edit")->assertForbidden();
    $this->post("/agents/registrations/update/{$reg->id}", sprPayload(['shop_name' => 'Hacked']))->assertForbidden();
    $this->delete("/agents/registrations/delete/{$reg->id}")->assertForbidden();

    expect($reg->fresh()->shop_name)->toBe('Al Noor Trading')->and(SalesPointRegistration::count())->toBe(1);
});

test('a pending registration can be soft deleted', function () {
    $user = sprUser(SPR_PERMISSIONS);
    $reg = sprCreate($user);

    $this->actingAs($user)->delete("/agents/registrations/delete/{$reg->id}")->assertRedirect(route('agents.registrations.index'));

    expect(SalesPointRegistration::count())->toBe(0)
        ->and(SalesPointRegistration::withTrashed()->count())->toBe(1);
});

test('reference numbers are unique and sequential', function () {
    $user = sprUser(SPR_PERMISSIONS);
    $a = sprCreate($user);
    $b = sprCreate($user);

    // id-based => unique even under concurrency (auto-increment is not reset between tests on MySQL)
    expect($a->reference_no)->toBe('SPR-'.str_pad((string) $a->id, 4, '0', STR_PAD_LEFT))
        ->and($b->reference_no)->toBe('SPR-'.str_pad((string) $b->id, 4, '0', STR_PAD_LEFT))
        ->and($a->reference_no)->not->toBe($b->reference_no);
});

test('index filters by status and search', function () {
    $user = sprUser(SPR_PERMISSIONS);
    sprCreate($user);
    sprCreate($user, ['status' => 'approved', 'shop_name' => 'Gold Souq']);
    $this->actingAs($user);

    $all = $this->get('/agents/registrations', sprInertia())->json('props');
    expect($all['counts'])->toMatchArray(['all' => 2, 'pending' => 1, 'approved' => 1, 'rejected' => 0]);

    $approved = $this->get('/agents/registrations?status=approved', sprInertia())->json('props.registrations.data');
    expect($approved)->toHaveCount(1)->and($approved[0]['shop_name'])->toBe('Gold Souq');

    $search = $this->get('/agents/registrations?search=Gold', sprInertia())->json('props.registrations.data');
    expect($search)->toHaveCount(1);
});

test('signature sanitizer re-encodes valid PNGs and rejects everything else', function () {
    $service = app(SalesPointRegistrationService::class);

    expect(substr($service->sanitizeSignature(sprSignature()), 1, 3))->toBe('PNG');
    expect(fn () => $service->sanitizeSignature('data:image/jpeg;base64,AAAA'))->toThrow(InvalidArgumentException::class);
    expect(fn () => $service->sanitizeSignature('data:image/png;base64,!!!'))->toThrow(InvalidArgumentException::class);
    expect(fn () => $service->sanitizeSignature('data:image/png;base64,'.base64_encode('not an image')))->toThrow(InvalidArgumentException::class);
});
