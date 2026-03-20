<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class WithdrawRequest extends Model
{
    protected $fillable =
    [
        'seller_id',
        'amount',
        'status',
        'note',
        'payment_method',
        'payment_details',
        'charge_fee'
    ];

    protected $casts = [
        'payment_details' => 'array',
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
