<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\Seller;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\SellerService;
use App\Services\EmployeeService;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\SellerRequest;
use App\Http\Requests\EmployeeRequest;
use App\Http\Requests\PaginateRequest;
use App\Http\Resources\SellerResource;
use App\Libraries\QueryExceptionLibrary;

class SellerController extends Controller
{
    private SellerService $sellerService;

    public function __construct(SellerService $sellerService)
    {
        // parent::__construct();
        $this->sellerService = $sellerService;
    }
    public function index(PaginateRequest $request)
    {
        try {
            return SellerResource::collection($this->sellerService->list($request));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }
    public function products(User $seller)
    {
        try {

            $products = Product::with(['productOrders'])
                ->withSum('productOrders', 'quantity')
                ->where('creator_id', $seller->id)
                ->where('creator_type', User::class)
                ->get();

            $productsReportArray = [];

            $productsReportArray['total_products'] = $products->count();

            $productsReportArray['total_sold_quantity'] = abs(
                $products->sum('product_orders_sum_quantity')
            );

            $productsReportArray['total_earning'] = $products->sum(function ($product) {
                return $product->productOrders->sum(function ($order) {
                    return abs($order->quantity * $order->price);
                });
            });

            $productsReportArray['products'] = $products;

            return $productsReportArray;

        } catch (Exception $exception) {
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }
    public function store(SellerRequest $request)
    {
        try {
            return new SellerResource($this->sellerService->store($request));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }
    public function show(User $seller)
    {
        try {
            return new SellerResource($this->sellerService->show($seller));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }
    public function update(SellerRequest $request, User $seller)
    {
        try {
            return new SellerResource($this->sellerService->update($request, $seller));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }





    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:users,id',
        ]);

        $users = User::with('seller', 'addresses')->whereIn('id', $request->ids)->get();

        try {
            DB::transaction(function () use ($users) {
                foreach ($users as $user) {

                    // Delete related addresses
                  if ($user->addresses->count()) {
                        $user->addresses()->delete();
                    }

                    // Delete related seller row
                    if ($user->seller) {

                        // Delete license and NID files if exist
                         if ($user->hasMedia('profile')) {
                            $user->clearMediaCollection('profile');
                        }
                         if ($user->seller->hasMedia('license')) {
                            $user->seller->clearMediaCollection('license');
                        }
                         if ($user->seller->hasMedia('nid')) {
                            $user->seller->clearMediaCollection('nid');
                        }

                        // Delete the seller row
                        $user->seller()->delete();
                    }

                    // Finally delete the user
                    $user->delete();
                }
            });

            return response()->json([
                'status' => true,
                'message' => 'Selected sellers deleted successfully',
            ]);

        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong: ' . $exception->getMessage(),
            ], 422);
        }
    }

}
