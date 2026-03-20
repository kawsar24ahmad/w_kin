<?php

namespace App\Http\Controllers;

use App\Models\WithdrawRequest;
use Illuminate\Http\Request;

class WithdrawRequestController extends Controller
{
   public function requestWithdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:bkash,nagad,bank,cash',
            'payment_details' => 'required|string',
            'note' => 'nullable|string'
        ]);

        $seller = auth()->user();
        $withdrawAmount = $request->amount;

        // 1. Calculate Fees
        $feePercent = match ($request->payment_method) {
            'bkash', 'nagad' => 1,
            'bank'           => 2,
            default          => 0,
        };

        $chargeFee = ($withdrawAmount * $feePercent) / 100;

        // DECISION: Is the fee ON TOP of the amount or INSIDE the amount?
        // Let's assume the user pays the fee ON TOP (Total cost = Amount + Fee).
        $totalDeduction = $withdrawAmount + $chargeFee;

        if ($seller->balance < $totalDeduction) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient balance to cover the amount and processing fees.'
            ], 422);
        }

        // 2. Database Transaction for Safety
        return \DB::transaction(function () use ($seller, $request, $withdrawAmount, $chargeFee, $totalDeduction) {

            $withdraw = WithdrawRequest::create([
                'seller_id'       => $seller->id,
                'amount'          => $withdrawAmount,
                'charge_fee'      => $chargeFee,
                'payment_method'  => $request->payment_method,
                'payment_details' => $request->payment_details,
                'status'          => 'pending',
                'note'            => $request->note
            ]);

            // 3. Deduct total (Amount + Fee)
            $seller->decrement('balance', $totalDeduction);

            return response()->json([
                'status' => true,
                'message' => 'Withdrawal requested successfully',
                'data' => $withdraw,
                'balance' => $seller->fresh()->balance
            ]);
        });
    }
    // public function requestWithdraw(Request $request)
    // {
    //     $request->validate([
    //         'amount' => 'required|numeric|min:1',
    //         'status' => 'nullable',
    //         'note' => 'nullable',
    //         'payment_method' => 'required',
    //         'payment_details' => 'required'
    //     ]);

    //     $seller = auth()->user();

    //     if ($seller->balance < $request->amount) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Insufficient balance'
    //         ], 422);
    //     }
    //     $withdrawAmount =$request->amount;
    //     $payment_method =$request->payment_method;
    //     $charge_fee = ( $payment_method == 'bkash' || $payment_method == 'nagad') ? ($withdrawAmount  * 2 / 100) : ( $payment_method == 'bank') ? ($withdrawAmount  * 1 / 100) : 0;



    //     $withdraw = WithdrawRequest::create([
    //         'seller_id' => $seller->id,
    //         'amount' => $withdrawAmount,
    //         'payment_method' => $request->payment_method,
    //         'charge_fee' => $charge_fee ,
    //         'payment_details' => $request->payment_details,
    //         'status' => 'pending',
    //     ]);


    //     $seller->decrement('balance', ());

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Withdrawal requested successfully',
    //         'data' => $withdraw,
    //         'balance' => $seller->balance,
    //     ]);
    // }

    public function listRequests()
    {
        $user = auth()->user();

        if ($user->hasRole('Admin')) {
            $requests = WithdrawRequest::with('seller')->latest()->get();
        } else {
            $requests = WithdrawRequest::where('seller_id', $user->id)->latest()->get();
        }

        return response()->json([
            'status' => true,
            'data' => $requests,
            'balance' => $user->balance,
            'isAdmin' => $user->hasRole('Admin'),

        ]);
    }

    // Add this method in WithdrawRequestController.php

    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        $admin = auth()->user();
        if (!$admin->hasRole('Admin')) {
            return response()->json(['status'=>false,'message'=>'Unauthorized'],403);
        }

        $withdraw = WithdrawRequest::with('seller')->findOrFail($id);

        // Only pending requests can be updated
        if ($withdraw->status != 'pending') {
            return response()->json(['status'=>false,'message'=>'Only pending requests can be updated'],422);
        }

        $withdraw->status = $request->status;
        $withdraw->save();

        // If rejected, refund seller balance
        if ($request->status == 'rejected') {
            $withdraw->seller->increment('balance', $withdraw->amount);
        }

        return response()->json([
            'status' => true,
            'message' => "Withdraw request {$request->status} successfully",
            'data' => $withdraw
        ]);
    }
}
