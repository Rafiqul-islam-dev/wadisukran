<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\AgentAccount;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use ZipArchive;

class AccountsSummeryController extends Controller
{
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
            $last_posting = AgentAccount::where('user_id', $user->id)->where('type', 'posting')->latest()->first();
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
                'old_due' => !empty($last_posting->old_due) ? 500.00 : 0,
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
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);
        $users = User::where('user_type', 'agent')
                    ->where('status', 'active')
                    ->get();

        $zip = new ZipArchive;
        $zipFileName = 'agent-bills.zip';
        $zipDir = public_path('uploads/agent_bill');
        $zipPath = $zipDir.'/'.$zipFileName;
        // make dir if not exists
        // if (!File::exists($zipDir)) {
        //     File::makeDirectory($zipDir, 0755, true);
        // }

        // open zip
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
                'name' => $user->name,
                'address' => $user->address,
                'total_sell' => $account['sell'] ?? 0,
                'total_commission' => $account['commission'] ?? 0,
                'total_win' => $account['win'] ?? 0,
                'total_claim' => $account['claim'] ?? 0,
                'old_due' => $last_posting?->old_due ?? 0,
            ];

            $pdf = Pdf::loadView('pdf.agent_bill', ['agent' => $data]);

            $pdfFileName = 'bill_agent_'.$user->id.'.pdf';
            $pdfPath = storage_path("app/temp/$pdfFileName");

            $pdf->save($pdfPath);
            $zip->addFile($pdfPath, $pdfFileName);
        }

        $zip->close();

        return response()->download($zipPath, $zipFileName, [
            'Content-Type' => 'application/zip'
        ]);
    }
}
