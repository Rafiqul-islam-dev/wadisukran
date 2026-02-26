<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\AgentAccount;
use App\Models\AgentBill;
use App\Models\User;
use App\Services\AgentAccountService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use ZipArchive;

class AccountsSummeryController extends Controller
{
    protected $agentAccountService;

    public function __construct(AgentAccountService $agentAccountService)
    {
        $this->agentAccountService = $agentAccountService;
    }
    public function index(Request $request){
        $agents_list = User::where('user_type', 'agent')
                    ->where('status', 'active')
                    ->get();

        $users = User::where('user_type', 'agent')
                    ->where('status', 'active')
                    ->when($request->agent_id, function ($q) use ($request) {
                        $q->where('id', $request->agent_id);
                    })
                    ->get();

        $last_bill = AgentAccount::where('type', 'posting')->latest()->first();
        $agents = [];
        foreach($users as $user){
            $last_posting = AgentAccount::where('user_id', $user->id)->where('type', 'posting')->latest('updated_at')->first();
            $account = AgentAccount::where('user_id', $user->id)
                ->when($last_posting?->created_at, function ($q) use ($last_posting) {
                    $q->where('created_at', '>', $last_posting->created_at);
                })
                ->when($request->to, function ($q) use ($request) {
                    $q->where('created_at', '<=', Carbon::parse($request->to)->endOfDay());
                })
                ->select('type', DB::raw('SUM(amount) as total_amount'))
                ->groupBy('type')
                ->get()
                ->pluck('total_amount', 'type');

            $agents[] = [
                'id' => $user->id,
                'name' => $user->name,
                'address' => $user->address,
                'total_sell' => !empty($account['sell']) ? $account['sell'] : 0,
                'total_commission' => !empty($account['commission']) ? $account['commission'] : 0,
                'total_win' => !empty($account['win']) ? $account['win'] : 0,
                'total_claim' => !empty($account['claim']) ? $account['claim'] : 0,
                'old_due' => !empty($last_posting->old_due) ? $last_posting->old_due : 0,
            ];
        }

        return Inertia::render('Accounts/Summery', [
            'users' => $agents,
            'agents' => $agents_list,
            'from_date' => $last_bill?->created_at ? Carbon::parse($last_bill->created_at)->format('Y-m-d') : null,
        ]);
    }

    public function generateBill(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        $latest_bill = AgentBill::latest()->first();
        if($latest_bill && Carbon::parse($request->to)->lt(Carbon::parse($latest_bill->to_date))) {
            return response()->json(['success' => false, 'message' => 'A bill has already been generated for a later date. Please check the date range.'], 422);
        }

        $bill_exists = AgentBill::whereDate('to_date', Carbon::parse($request->to))->first();
        if($bill_exists) {
            return response()->json(['success' => false, 'message' => 'A bill has already been generated for the selected date. Please check the date.'], 422);
        }
        $users = User::where('user_type', 'agent')
                    ->where('status', 'active')
                    ->get();

        $zip = new ZipArchive;
        $zipFileName = 'agent-bills-'.now()->format('Y-m-d-H-i-s').'.zip';
        $zipDir = public_path('uploads/agent_bill');
        $zipPath = $zipDir.'/'.$zipFileName;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return response()->json(['success' => false, 'message' => 'Cannot create zip']);
        }

        foreach ($users as $user) {
            $last_posting = AgentAccount::where('user_id', $user->id)
                ->where('type', 'posting')
                ->latest()
                ->first();

            $account = AgentAccount::where('user_id', $user->id)
                ->when($last_posting?->created_at, function ($q) use ($last_posting) {
                    $q->where('created_at', '>', $last_posting->created_at);
                })
                ->when($request->to, function ($q) use ($request) {
                    $q->where('created_at', '<=', Carbon::parse($request->to)->endOfDay());
                })
                ->select('type', DB::raw('SUM(amount) as total_amount'))
                ->groupBy('type')
                ->get()
                ->pluck('total_amount', 'type');

            $data = [
                'id' => $user->id,
                'from' => $request->from ? Carbon::parse($request->from)->format('Y-m-d H:i:s') : Carbon::parse(AgentAccount::first()->created_at)->startOfDay()->format('Y-m-d H:i:s'),
                'to' =>  Carbon::parse($request->to)->endOfDay()->format('Y-m-d H:i:s'),
                'name' => $user->name,
                'address' => $user->address,
                'total_sell' => $account['sell'] ?? 0,
                'total_commission' => $account['commission'] ?? 0,
                'total_win' => $account['win'] ?? 0,
                'total_claim' => $account['claim'] ?? 0,
                'old_due' => $last_posting?->old_due ?? 0,
            ];

            $pdf = Pdf::loadView('pdf.agent_bill', ['agent' => $data]);

            $pdfFileName = 'bill_agent_'.$user->name.'_'.now()->format('Y-m-d-H-i-s').'.pdf';
            $pdfPath = storage_path("app/temp/$pdfFileName");

            $pdf->save($pdfPath);
            $zip->addFile($pdfPath, $pdfFileName);

            $data = [
                'user_id' => $user->id,
                'type'    => 'posting',
                'created_at' => Carbon::parse($request->to)->endOfDay(),
                'amount'  => 0,
                'description' => 'Bill Generated',
            ];

            $this->agentAccountService->store($data);
        }

        $zip->close();

        AgentBill::create([
            'from_date' => $request->from ?? AgentAccount::first()?->created_at,
            'to_date' => $request->to,
            'zip' => 'uploads/agent_bill/'.$zipFileName,
            'created_by' => Auth::id()
        ]);

        return response()->download($zipPath, $zipFileName, [
            'Content-Type' => 'application/zip'
        ]);
    }

    public function bills(){
        $bills = AgentBill::with('creator')->latest()->paginate(10);
        return Inertia::render('Accounts/Bills', [
            'bills' => $bills
        ]);
    }
}
