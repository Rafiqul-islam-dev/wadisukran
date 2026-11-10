<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectSalesPointRegistrationRequest;
use App\Http\Requests\SalesPointRegistrationRequest;
use App\Models\SalesPointRegistration;
use App\Services\SalesPointRegistrationService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SalesPointRegistrationController extends Controller
{
    public function __construct(protected SalesPointRegistrationService $service) {}

    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = trim((string) $request->query('search', ''));

        $registrations = SalesPointRegistration::query()
            ->with('creator:id,name')
            ->when(in_array($status, ['pending', 'approved', 'rejected'], true), fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
                $q->where(function ($q) use ($like) {
                    $q->where('reference_no', 'like', $like)
                        ->orWhere('shop_name', 'like', $like)
                        ->orWhere('owner_name', 'like', $like)
                        ->orWhere('mobile', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = SalesPointRegistration::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('Agent/Registration/Index', [
            'registrations' => $registrations,
            'filters' => ['status' => $status, 'search' => $search],
            'counts' => [
                'all' => (int) $counts->sum(),
                'pending' => (int) ($counts['pending'] ?? 0),
                'approved' => (int) ($counts['approved'] ?? 0),
                'rejected' => (int) ($counts['rejected'] ?? 0),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Agent/Registration/Create', [
            'documentTypes' => config('sales_point_agreement.document_types'),
            'terms' => config('sales_point_agreement.terms'),
        ]);
    }

    public function store(SalesPointRegistrationRequest $request)
    {
        $data = $request->validated();
        $data['photo'] = $request->file('photo');
        $data['documents'] = $request->file('documents', []);

        // signature problems surface as ValidationException (per-field) from the service
        $registration = $this->service->create($data, Auth::id());

        return redirect()
            ->route('agents.registrations.show', $registration)
            ->with('success', "Registration {$registration->reference_no} submitted for approval.");
    }

    public function show(SalesPointRegistration $registration)
    {
        $registration->load(['documents', 'creator:id,name', 'approver:id,name', 'rejecter:id,name', 'events' => fn ($q) => $q->limit(50), 'events.user:id,name']);

        return Inertia::render('Agent/Registration/Show', [
            'registration' => $this->present($registration),
            'documentTypes' => config('sales_point_agreement.document_types'),
            'terms' => $registration->terms_snapshot ?: config('sales_point_agreement.terms'),
        ]);
    }

    public function edit(SalesPointRegistration $registration)
    {
        abort_unless($registration->isEditable(), 403, 'Only pending or rejected registrations can be edited.');
        $registration->load('documents');

        return Inertia::render('Agent/Registration/Edit', [
            'registration' => $this->present($registration),
            'documentTypes' => config('sales_point_agreement.document_types'),
            'terms' => config('sales_point_agreement.terms'),
        ]);
    }

    public function update(SalesPointRegistrationRequest $request, SalesPointRegistration $registration)
    {
        abort_unless($registration->isEditable(), 403, 'Only pending or rejected registrations can be edited.');

        $data = $request->validated();
        $data['photo'] = $request->file('photo');
        $data['documents'] = $request->file('documents', []);

        try {
            $registration = $this->service->update($registration, $data, Auth::id());
        } catch (DomainException $e) { // someone approved it between our check and the locked re-check
            abort(409, $e->getMessage());
        }

        return redirect()
            ->route('agents.registrations.show', $registration)
            ->with('success', 'Registration updated and submitted for approval.');
    }

    public function destroy(SalesPointRegistration $registration)
    {
        abort_if($registration->isApproved(), 403, 'Approved registrations cannot be deleted.');

        try {
            $this->service->delete($registration, Auth::id());
        } catch (DomainException $e) {
            abort(409, $e->getMessage());
        }

        return redirect()->route('agents.registrations.index')->with('success', 'Registration deleted.');
    }

    /** Non-blocking duplicate hint for the form (same trade licence or email on another registration). */
    public function duplicates(Request $request)
    {
        $data = $request->validate([
            'trade_license_no' => 'nullable|string|max:100',
            'email' => 'nullable|string|max:255',
            'exclude' => 'nullable|integer',
        ]);

        $licence = trim((string) ($data['trade_license_no'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        if ($licence === '' && $email === '') {
            return response()->json(['matches' => []]);
        }

        $rows = SalesPointRegistration::query()
            ->when(! empty($data['exclude']), fn ($q) => $q->where('id', '!=', $data['exclude']))
            ->where(function ($q) use ($licence, $email) {
                if ($licence !== '') {
                    $q->orWhere('trade_license_no', $licence);
                }
                if ($email !== '') {
                    $q->orWhere('email', $email);
                }
            })
            ->latest()
            ->limit(5)
            ->get(['id', 'reference_no', 'shop_name', 'status', 'trade_license_no', 'email']);

        return response()->json(['matches' => $rows->map(fn ($r) => [
            'id' => $r->id,
            'reference_no' => $r->reference_no,
            'shop_name' => $r->shop_name,
            'status' => $r->status,
            'same' => array_values(array_filter([
                $licence !== '' && $r->trade_license_no === $licence ? 'trade license' : null,
                $email !== '' && strcasecmp($r->email, $email) === 0 ? 'email' : null,
            ])),
        ])->values()]);
    }

    public function approve(SalesPointRegistration $registration)
    {
        try {
            $registration = $this->service->approve($registration, Auth::id());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e); // PDF/storage failure: the registration stays pending (transaction rolled back)

            return back()->with('error', 'The agreement PDF could not be generated, so the registration was NOT approved. Please try again.');
        }

        return redirect()
            ->route('agents.registrations.show', $registration)
            ->with('success', "Registration {$registration->reference_no} approved. Agreement PDF generated.");
    }

    public function reject(RejectSalesPointRegistrationRequest $request, SalesPointRegistration $registration)
    {
        try {
            $registration = $this->service->reject($registration, Auth::id(), $request->validated('reason'));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('agents.registrations.show', $registration)
            ->with('success', "Registration {$registration->reference_no} rejected.");
    }

    public function regeneratePdf(SalesPointRegistration $registration)
    {
        try {
            $this->service->regeneratePdf($registration, Auth::id());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'The agreement PDF could not be regenerated. The previous PDF was kept.');
        }

        return back()->with('success', 'Agreement PDF regenerated.');
    }

    /** Approved agreements only. Inline for the preview, `?download=1` forces a download. */
    public function pdf(Request $request, SalesPointRegistration $registration)
    {
        abort_unless($registration->isApproved() && $registration->pdf_path, 404);

        $disk = Storage::disk(config('sales_point_agreement.disk'));
        abort_unless($disk->exists($registration->pdf_path), 404);

        $this->service->record($registration, $request->boolean('download') ? 'pdf_downloaded' : 'pdf_viewed', Auth::id());

        return $disk->response(
            $registration->pdf_path,
            "Wadi-Shukran-Agreement-{$registration->reference_no}.pdf",
            ['Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'],
            $request->boolean('download') ? 'attachment' : 'inline'
        );
    }

    /** Stream a private file (photo, signature, document). Never exposes a public URL. */
    public function file(SalesPointRegistration $registration, string $kind)
    {
        $file = $this->service->resolveFile($registration, $kind);
        abort_if($file === null, 404);

        // opening an uploaded ID/licence document is audited (photo and signatures are not: they load with every page view)
        if (array_key_exists($kind, config('sales_point_agreement.document_types'))) {
            $this->service->record($registration, 'document_viewed', Auth::id(), ['kind' => $kind]);
        }

        [$path, $mime, $name] = $file;

        // PDFs are downloaded (Chrome's PDF viewer does not render CSP-sandboxed documents);
        // images are shown inline under a locked-down CSP.
        $isPdf = $mime === 'application/pdf';

        return Storage::disk(config('sales_point_agreement.disk'))->response($path, $name, array_filter([
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => $isPdf ? null : "sandbox; default-src 'none'; img-src 'self' data:",
            'Cache-Control' => 'private, no-store',
        ]), $isPdf ? 'attachment' : 'inline');
    }

    /** Shape for the frontend: sensitive IDs only here (permission-guarded routes), no storage paths. */
    private function present(SalesPointRegistration $r): array
    {
        return $r->makeVisible(['passport_no', 'uid_no', 'eid_no'])->toArray() + [
            'has_photo' => (bool) $r->photo_path,
            'document_types_uploaded' => $r->documents->pluck('type')->values(),
        ];
    }
}
