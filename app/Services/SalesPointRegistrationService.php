<?php

namespace App\Services;

use App\Models\SalesPointRegistration;
use App\Models\SalesPointRegistrationDocument;
use App\Models\SalesPointRegistrationEvent;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class SalesPointRegistrationService
{
    public function __construct(protected SalesPointAgreementPdfService $pdf) {}

    /**
     * Approve a pending registration and generate the signed agreement PDF.
     * One transaction: if the PDF cannot be built/stored, the registration stays `pending`.
     */
    public function approve(SalesPointRegistration $registration, int $userId): SalesPointRegistration
    {
        $newPdf = null;

        try {
            return DB::transaction(function () use ($registration, $userId, &$newPdf) {
                $r = SalesPointRegistration::whereKey($registration->id)->lockForUpdate()->firstOrFail();

                // re-check AFTER locking: another approver may have acted meanwhile
                if (! $r->isPending()) {
                    throw new DomainException('Only pending registrations can be approved.');
                }
                $this->assertNotSelfApproval($r, $userId);

                // The terms printed are the ones frozen when the form was submitted/signed (see freezeTerms()).
                // Only legacy rows without a snapshot fall back to the current config.
                if (empty($r->terms_snapshot)) {
                    $this->freezeTerms($r);
                }

                // set the approval data BEFORE rendering so the very first PDF carries the approval stamp
                $r->status = SalesPointRegistration::STATUS_APPROVED;
                $r->approved_by = $userId;
                $r->approved_at = now();

                $newPdf = $this->storePdf($r);

                $r->pdf_path = $newPdf;
                $r->pdf_generated_at = now();
                $r->save();
                $this->record($r, 'approved', $userId, ['terms_version' => $r->terms_version]);

                return $r;
            });
        } catch (\Throwable $e) {
            $this->discard([$newPdf]);
            throw $e;
        }
    }

    public function reject(SalesPointRegistration $registration, int $userId, string $reason): SalesPointRegistration
    {
        return DB::transaction(function () use ($registration, $userId, $reason) {
            $r = SalesPointRegistration::whereKey($registration->id)->lockForUpdate()->firstOrFail();

            if (! $r->isPending()) {
                throw new DomainException('Only pending registrations can be rejected.');
            }
            $this->assertNotSelfApproval($r, $userId);

            $r->status = SalesPointRegistration::STATUS_REJECTED;
            $r->rejected_by = $userId;
            $r->rejected_at = now();
            $r->reject_reason = $reason;
            $r->save();
            $this->record($r, 'rejected', $userId, ['reason' => $reason]);

            return $r;
        });
    }

    /** Rebuild the PDF of an approved registration from its stored data and frozen terms. */
    public function regeneratePdf(SalesPointRegistration $registration, ?int $userId = null): SalesPointRegistration
    {
        $newPdf = null;
        $old = null;

        try {
            $r = DB::transaction(function () use ($registration, $userId, &$newPdf, &$old) {
                $r = SalesPointRegistration::whereKey($registration->id)->lockForUpdate()->firstOrFail();

                if (! $r->isApproved()) {
                    throw new DomainException('Only approved registrations have an agreement PDF.');
                }

                $old = $r->pdf_path;
                $newPdf = $this->storePdf($r);
                $r->pdf_path = $newPdf;
                $r->pdf_generated_at = now();
                $r->save();
                $this->record($r, 'pdf_regenerated', $userId);

                return $r;
            });
        } catch (\Throwable $e) {
            $this->discard([$newPdf]);
            throw $e;
        }

        $this->discard([$old]); // only after the commit
        Log::info('Sales point agreement PDF regenerated', ['registration_id' => $r->id, 'by' => $userId, 'old' => $old, 'new' => $newPdf]);

        return $r;
    }

    /** Append an audit-trail entry (call inside the same transaction as the change it describes). */
    public function record(SalesPointRegistration $registration, string $action, ?int $userId, array $meta = []): void
    {
        SalesPointRegistrationEvent::create([
            'registration_id' => $registration->id,
            'user_id' => $userId,
            'action' => $action,
            'meta' => $meta ?: null,
            'ip_address' => request()?->ip(),
        ]);
    }

    /** Freeze the terms text the submitter is shown/signing, so later config edits cannot change a signed PDF. */
    private function freezeTerms(SalesPointRegistration $r): void
    {
        $r->terms_version = (int) config('sales_point_agreement.terms_version');
        $r->terms_snapshot = array_values((array) config('sales_point_agreement.terms'));
    }

    /** Render + store the agreement; returns the new private-disk path. */
    private function storePdf(SalesPointRegistration $r): string
    {
        $bytes = $this->pdf->render($r, $r->terms_snapshot ?: null);

        if (! str_starts_with($bytes, '%PDF')) {
            throw new RuntimeException('The generated agreement is not a valid PDF.');
        }

        $path = $this->dir($r).'/agreement_'.$r->reference_no.'_'.Str::random(12).'.pdf';
        $this->disk()->put($path, $bytes);

        return $path;
    }

    private function assertNotSelfApproval(SalesPointRegistration $r, int $userId): void
    {
        if (config('sales_point_agreement.block_self_approval') && (int) $r->created_by === $userId) {
            throw new DomainException('You cannot approve or reject a registration you created.');
        }
    }

    private function disk()
    {
        return Storage::disk(config('sales_point_agreement.disk'));
    }

    /** Create a registration in `pending` status together with its files. */
    public function create(array $data, int $userId): SalesPointRegistration
    {
        $stored = [];

        try {
            return DB::transaction(function () use ($data, $userId, &$stored) {
                $registration = SalesPointRegistration::create(
                    $this->attributes($data) + [
                        // temporary unique value; replaced with the id-based number below (race-safe)
                        'reference_no' => 'TMP-'.Str::uuid(),
                        'status' => SalesPointRegistration::STATUS_PENDING,
                        'created_by' => $userId,
                        'terms_version' => (int) config('sales_point_agreement.terms_version'),
                        'terms_snapshot' => array_values((array) config('sales_point_agreement.terms')),
                        'person_signature_path' => '',
                        'company_signature_path' => '',
                    ]
                );

                $registration->reference_no = 'SPR-'.str_pad((string) $registration->id, 4, '0', STR_PAD_LEFT);
                $registration->person_signature_path = $this->storeSignature($registration, 'person', $data['person_signature'], $stored);
                $registration->company_signature_path = $this->storeSignature($registration, 'company', $data['company_signature'], $stored);

                if (! empty($data['photo'])) {
                    $registration->photo_path = $this->storeUpload($registration, $data['photo'], 'photo', $stored);
                }

                $registration->save();
                $this->syncDocuments($registration, $data['documents'] ?? [], $stored);

                $this->record($registration, 'created', $userId);

                return $registration->fresh();
            });
        } catch (\Throwable $e) {
            $this->discard($stored); // do not leave orphan sensitive files behind
            throw $e;
        }
    }

    /** Update a pending/rejected registration. A rejected one is re-submitted as pending. */
    public function update(SalesPointRegistration $registration, array $data, ?int $userId = null): SalesPointRegistration
    {
        $stored = [];
        $obsolete = [];

        try {
            $registration = DB::transaction(function () use ($registration, $data, $userId, &$stored, &$obsolete) {
                $registration = SalesPointRegistration::whereKey($registration->id)->lockForUpdate()->firstOrFail();

                if (! $registration->isEditable()) {
                    throw new DomainException('Only pending or rejected registrations can be edited.');
                }

                $registration->fill($this->attributes($data));
                $this->freezeTerms($registration); // re-frozen on every (re)submission

                if (! empty($data['person_signature'])) {
                    $obsolete[] = $registration->person_signature_path;
                    $registration->person_signature_path = $this->storeSignature($registration, 'person', $data['person_signature'], $stored);
                }
                if (! empty($data['company_signature'])) {
                    $obsolete[] = $registration->company_signature_path;
                    $registration->company_signature_path = $this->storeSignature($registration, 'company', $data['company_signature'], $stored);
                }
                if (! empty($data['photo'])) {
                    $obsolete[] = $registration->photo_path;
                    $registration->photo_path = $this->storeUpload($registration, $data['photo'], 'photo', $stored);
                }

                if ($registration->status === SalesPointRegistration::STATUS_REJECTED) {
                    $registration->fill([
                        'status' => SalesPointRegistration::STATUS_PENDING,
                        'rejected_by' => null,
                        'rejected_at' => null,
                        'reject_reason' => null,
                    ]);
                }

                $registration->save();
                $this->syncDocuments($registration, $data['documents'] ?? [], $stored, $obsolete);

                $this->record($registration, 'updated', $userId);

                return $registration->fresh();
            });
        } catch (\Throwable $e) {
            $this->discard($stored);
            throw $e;
        }

        $this->discard($obsolete); // only after the DB commit succeeded

        return $registration;
    }

    public function delete(SalesPointRegistration $registration, ?int $userId = null): void
    {
        DB::transaction(function () use ($registration, $userId) {
            $registration = SalesPointRegistration::whereKey($registration->id)->lockForUpdate()->firstOrFail();

            if ($registration->isApproved()) {
                throw new DomainException('Approved registrations cannot be deleted.');
            }

            $this->record($registration, 'deleted', $userId);
            $registration->delete(); // soft delete; files are kept
        });
    }

    /**
     * Resolve a stored file for streaming. Returns [path, mime|null, download name] or null.
     */
    public function resolveFile(SalesPointRegistration $registration, string $kind): ?array
    {
        $path = match ($kind) {
            'photo' => $registration->photo_path,
            'person_signature' => $registration->person_signature_path,
            'company_signature' => $registration->company_signature_path,
            default => null,
        };

        if ($path) {
            return $this->disk()->exists($path) ? [$path, null, $kind] : null;
        }

        if (array_key_exists($kind, config('sales_point_agreement.document_types'))) {
            $doc = $registration->documents()->where('type', $kind)->first();
            if ($doc && $this->disk()->exists($doc->path)) {
                return [$doc->path, $doc->mime, $doc->original_name ?: $kind];
            }
        }

        return null;
    }

    /**
     * Validate a PNG data-URL from the signature pad and re-encode it with GD so that
     * only a clean PNG is ever stored. Returns raw PNG bytes.
     */
    public function sanitizeSignature(string $dataUrl): string
    {
        if (! function_exists('imagecreatefromstring')) {
            throw new RuntimeException('The PHP GD extension is required to process signatures.');
        }

        // prefix check + strict base64 decode (no regex over a multi-MB payload)
        $prefix = 'data:image/png;base64,';
        if (! str_starts_with($dataUrl, $prefix)) {
            throw new InvalidArgumentException('Signature must be a PNG image.');
        }

        $binary = base64_decode(preg_replace('/\s+/', '', substr($dataUrl, strlen($prefix))), true);
        if ($binary === false || strlen($binary) > 2_000_000) {
            throw new InvalidArgumentException('Signature image is invalid or too large.');
        }

        $info = @getimagesizefromstring($binary);
        if (! $info || $info[2] !== IMAGETYPE_PNG || $info[0] > 3000 || $info[1] > 3000 || $info[0] < 20 || $info[1] < 10) {
            throw new InvalidArgumentException('Signature image is invalid.');
        }

        $img = @imagecreatefromstring($binary);
        if (! $img) {
            throw new InvalidArgumentException('Signature image is invalid.');
        }

        if ($this->isBlank($img)) {
            imagedestroy($img);
            throw new InvalidArgumentException('Signature is empty. Please sign inside the box.');
        }

        imagesavealpha($img, true);
        ob_start();
        imagepng($img);
        $clean = ob_get_clean();
        imagedestroy($img);

        return $clean;
    }

    /** True when a (downscaled, white-flattened) image has no visible ink. */
    private function isBlank($img): bool
    {
        $w = 64;
        $h = 32;
        $small = imagecreatetruecolor($w, $h);
        imagefill($small, 0, 0, imagecolorallocate($small, 255, 255, 255));
        imagealphablending($small, true);
        imagecopyresampled($small, $img, 0, 0, 0, 0, $w, $h, imagesx($img), imagesy($img));

        $ink = 0;
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $c = imagecolorat($small, $x, $y);
                if ((($c >> 16) & 255) < 245 || (($c >> 8) & 255) < 245 || ($c & 255) < 245) {
                    $ink++;
                }
            }
        }
        imagedestroy($small);

        return $ink < 2;
    }

    private function storeSignature(SalesPointRegistration $registration, string $who, string $dataUrl, array &$stored): string
    {
        try {
            $png = $this->sanitizeSignature($dataUrl);
        } catch (InvalidArgumentException $e) {
            // report against the right form field
            throw ValidationException::withMessages(["{$who}_signature" => $e->getMessage()]);
        }

        $path = $this->dir($registration)."/signature_{$who}_".Str::random(24).'.png';
        $this->disk()->put($path, $png);
        $stored[] = $path;

        return $path;
    }

    /** Best-effort cleanup that must never mask the original exception. */
    private function discard(array $paths): void
    {
        foreach (array_filter($paths) as $path) {
            try {
                $this->disk()->delete($path);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    private function storeUpload(SalesPointRegistration $registration, UploadedFile $file, string $prefix, array &$stored): string
    {
        // extension comes from the detected mime type, never from the client file name
        $ext = $file->guessExtension() ?: 'bin';
        $path = $this->dir($registration)."/{$prefix}_".Str::random(32).'.'.$ext;
        $this->disk()->putFileAs(dirname($path), $file, basename($path));
        $stored[] = $path;

        return $path;
    }

    private function syncDocuments(SalesPointRegistration $registration, array $files, array &$stored, array &$obsolete = []): void
    {
        foreach (array_keys(config('sales_point_agreement.document_types')) as $type) {
            $file = $files[$type] ?? null;
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $existing = $registration->documents()->where('type', $type)->first();
            if ($existing) {
                $obsolete[] = $existing->path;
            }

            SalesPointRegistrationDocument::updateOrCreate(
                ['registration_id' => $registration->id, 'type' => $type],
                [
                    'path' => $this->storeUpload($registration, $file, $type, $stored),
                    'original_name' => Str::limit(preg_replace('/[^\w.\- ]/u', '_', $file->getClientOriginalName()), 120, ''),
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]
            );
        }
    }

    private function dir(SalesPointRegistration $registration): string
    {
        return 'sales-point-registrations/'.$registration->id;
    }

    /** Whitelist the scalar attributes that come from the form. */
    private function attributes(array $data): array
    {
        $products = $data['products'] ?? [];
        $normalized = [];
        foreach (['chocolate', 'attar', 'tasbih', 'pen', 'others'] as $key) {
            $normalized[$key] = filter_var($products[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        return [
            'shop_name' => $data['shop_name'],
            'owner_name' => $data['owner_name'],
            'passport_no' => $data['passport_no'],
            'uid_no' => $data['uid_no'],
            'eid_no' => $data['eid_no'],
            'trade_license_no' => $data['trade_license_no'],
            'email' => $data['email'],
            'mobile_country_code' => $data['mobile_country_code'] ?? '+971',
            'mobile' => $data['mobile'],
            'whatsapp_country_code' => $data['whatsapp_country_code'] ?? '+971',
            'whatsapp' => $data['whatsapp'],
            'address' => $data['address'],
            'location_map' => $data['location_map'],
            'products' => $normalized,
            'products_other_text' => $normalized['others'] ? ($data['products_other_text'] ?? null) : null,
            'dev_tab' => (bool) ($data['dev_tab'] ?? false),
            'dev_pos_machine' => (bool) ($data['dev_pos_machine'] ?? false),
            'dev_sim_card' => (bool) ($data['dev_sim_card'] ?? false),
            'dev_active_data' => (bool) ($data['dev_active_data'] ?? false),
            'sim_card_provided' => (bool) ($data['sim_card_provided'] ?? false),
            'delivery_date' => $data['delivery_date'],
            'delivery_time' => $data['delivery_time'],
            'signed_date' => $data['signed_date'],
            'signed_place' => $data['signed_place'],
        ];
    }
}
