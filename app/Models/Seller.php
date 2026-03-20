<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Seller extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'company_name',
        'category',
        'commission',
    ];
     public function getLicenseAttribute(): string
    {
        if (!empty($this->getFirstMediaUrl('license'))) {
            return asset($this->getFirstMediaUrl('license'));
        }
        return asset('images/required/profile.png');
    }

    // Accessor for NID photo
    public function getNidAttribute(): string
    {
        if (!empty($this->getFirstMediaUrl('nid'))) {
            return asset($this->getFirstMediaUrl('nid'));
        }
        return asset('images/required/profile.png');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
