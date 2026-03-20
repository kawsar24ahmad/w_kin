<?php

namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class SellerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            "id"           => $this->id,
            "name"         => $this->name,
            "username"     => $this->username,
            "email"        => $this->email,
            "phone"        => $this->phone === null ? '' : $this->phone,
            "status"       => $this->status,
            "role_id"      => optional($this->roles[0])->id,
            "role"         => optional($this->roles[0])->name,
            "image"        => $this->image,
            "country_code" => $this->country_code,
           'balance' => number_format($this->balance, 2, '.', ''),

             // seller table info
            "seller_info" => $this->whenLoaded('seller', function () {
                return [
                    "company_name" => $this->seller->company_name ?? '',
                    "category"     => $this->seller->category ?? '',
                    "commission"   => $this->seller->commission ?? '',
                    "license_photo"=> $this->seller->license ?? '',
                    "nid_photo"    => $this->seller->nid ?? '',
                ];
            }),
        ];
    }
}
