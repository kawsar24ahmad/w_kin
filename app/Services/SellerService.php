<?php

namespace App\Services;

use Exception;
use App\Enums\Ask;
use App\Models\User;
use App\Enums\Role as EnumRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\EmployeeRequest;
use App\Http\Requests\PaginateRequest;
use App\Http\Requests\ChangeImageRequest;
use App\Http\Requests\SellerRequest;
use App\Http\Requests\UserChangePasswordRequest;
use App\Libraries\QueryExceptionLibrary;


class SellerService
{
    public $user;
    public $phoneFilter = ['phone'];
    public $roleFilter = ['role_id'];
    public $userFilter = ['name', 'email', 'username', 'status', 'phone'];
    public $blockRoles = [EnumRole::ADMIN, EnumRole::CUSTOMER];


    /**
     * @throws Exception
     */
    public function list(PaginateRequest $request)
    {
        try {
            $requests    = $request->all();
            $method      = $request->get('paginate', 0) == 1 ? 'paginate' : 'get';
            $methodValue = $request->get('paginate', 0) == 1 ? $request->get('per_page', 10) : '*';
            $orderColumn = $request->get('order_column') ?? 'id';
            $orderType   = $request->get('order_type') ?? 'desc';

            return User::with('media', 'addresses', 'roles', 'seller')->where(
                function ($query) use ($requests) {
                    $query->whereHas('roles', function ($query) {
                        $query->where('id',  EnumRole::SELLER);
                        // $query->where('id', '!=', EnumRole::CUSTOMER);
                    });
                    foreach ($requests as $key => $request) {
                        if (in_array($key, $this->roleFilter)) {
                            $query->whereHas('roles', function ($query) use ($request, $key) {
                                $query->where('id', '=', $request);
                            });
                        }
                        if (in_array($key, $this->userFilter)) {
                            if ($key == 'phone') {
                                $query->whereRaw("CONCAT(country_code, phone) LIKE ?", ["%{$request}%"]);
                            } else {
                                $query->where($key, 'like', '%' . $request . '%');
                            }
                        }
                    }
                }
            )->orderBy($orderColumn, $orderType)->$method(
                $methodValue
            );
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    public function store(SellerRequest $request)
    {
        try {
            if (!in_array($request->role_id, $this->blockRoles)) {
                DB::transaction(function () use ($request) {
                    $this->user = User::create([
                        'name'              => $request->name,
                        'email'             => $request->email,
                        'phone'             => $request->phone,
                        'username'          => $this->username($request->email),
                        'password'          => bcrypt($request->password),
                        'status'            => $request->status,
                        'email_verified_at' => now(),
                        'country_code'      => $request->country_code,
                        'is_guest'          => Ask::NO,
                    ]);

                    $this->user->assignRole((int) $request->role_id);

                    // profile photo
                    if ($request->hasFile('photo')) {
                        $this->user->addMediaFromRequest('photo')->toMediaCollection('profile');
                    }

                  $seller =   $this->user->seller()->create([
                        'company_name' => $request->company_name,
                        'category' => $request->category,
                        'commission' => $request->commission,
                    ]);



                    // license photo
                    if ($request->hasFile('license_photo')) {
                        $seller
                            ->addMediaFromRequest('license_photo')
                            ->toMediaCollection('license');
                    }

                    // nid photo
                    if ($request->hasFile('nid_photo')) {
                       $seller
                            ->addMediaFromRequest('nid_photo')
                            ->toMediaCollection('nid');
                    }

                });
                return $this->user;
            } else {
                throw new Exception(trans('all.message.permission_denied'), 422);
            }
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            DB::rollBack();
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function update(SellerRequest $request, User $seller)
    {
        try {
            if (!in_array($request->role_id, $this->blockRoles) && !in_array(
                optional($seller->roles[0])->id,
                $this->blockRoles
            )) {
                DB::transaction(function () use ($seller, $request) {
                    $this->user               = $seller;
                    $this->user->name         = $request->name;
                    $this->user->email        = $request->email;
                    $this->user->phone        = $request->phone;
                    $this->user->status       = $request->status;
                    $this->user->country_code = $request->country_code;
                    if ($request->password) {
                        $this->user->password = Hash::make($request->password);
                    }
                    $this->user->save();
                });
                $this->user->syncRoles((int)  $request->role_id);


                  // profile photo
                    if ($request->hasFile('photo')) {
                        if ($this->user->hasMedia('profile')) {
                            $this->user->clearMediaCollection('profile');
                        }
                        $this->user->addMediaFromRequest('photo')->toMediaCollection('profile');
                    }

                  $seller = $this->user->seller()->updateOrCreate(
                        ['user_id' => $this->user->id],
                        [
                            'company_name' => $request->company_name,
                            'category' => $request->category,
                            'commission' => $request->commission,
                        ]
                    );



                    // license photo
                    if ($request->hasFile('license_photo')) {
                        if ($seller->hasMedia('license')) {
                            $seller->clearMediaCollection('license');
                        }
                        $seller
                            ->addMediaFromRequest('license_photo')
                            ->toMediaCollection('license');
                    }

                    // nid photo
                    if ($request->hasFile('nid_photo')) {
                        if ($seller->hasMedia('nid')) {
                            $seller->clearMediaCollection('nid');
                        }
                       $seller
                            ->addMediaFromRequest('nid_photo')
                            ->toMediaCollection('nid');
                    }

                return $this->user;
            } else {
                throw new Exception(trans('all.message.permission_denied'), 422);
            }
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            DB::rollBack();
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
   public function show(User $seller): User
    {
        try {
            if (!in_array(optional($seller->roles[0])->id, $this->blockRoles)) {
                return $seller->load('seller');
            } else {
                throw new Exception(trans('all.message.permission_denied'), 422);
            }
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */

    public function destroy(User $employee)
    {
        try {
            if (!in_array(optional($employee->roles[0])->id, $this->blockRoles)) {
                if ($employee->hasRole(optional($employee->roles[0])->id)) {
                    DB::transaction(function () use ($employee) {
                        $employee->addresses()->delete();
                        $employee->delete();
                    });
                } else {
                    throw new Exception(trans('all.message.permission_denied'), 422);
                }
            } else {
                throw new Exception(trans('all.message.permission_denied'), 422);
            }
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            DB::rollBack();
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    private function username($email): string
    {
        $emails = explode('@', $email);
        return $emails[0] . mt_rand();
    }

    /**
     * @throws Exception
     */
    public function changePassword(UserChangePasswordRequest $request, User $employee): User
    {
        try {
            if (!in_array(optional($employee->roles[0])->id, $this->blockRoles)) {
                $employee->password = Hash::make($request->password);
                $employee->save();
                return $employee;
            } else {
                throw new Exception(trans('all.message.permission_denied'), 422);
            }
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function changeImage(ChangeImageRequest $request, User $seller)
    {
        try {
                if ($request->image) {
                    $seller->clearMediaCollection('profile');
                    $seller->addMediaFromRequest('image')->toMediaCollection('profile');
                }
                return $seller;

        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }
}
