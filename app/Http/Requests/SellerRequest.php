<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SellerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() : bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'                  => ['required', 'string', 'max:190'],
            'email'                 => [
                'required',
                'email',
                'max:190',
                Rule::unique("users", "email")->ignore($this->route('seller.id'))
            ],
            'password'              => [
                $this->route('seller.id') ? 'nullable' : 'required',
                'string',
                'min:6'
            ],
            'password_confirmation' => [$this->route('seller.id') ? 'nullable' : 'required', 'string', 'min:6', 'same:password'],
            'username'              => [
                'nullable',
                'max:190',
                Rule::unique("users", "username")->ignore($this->route('seller.id'))
            ],
            'device_token'          => ['nullable', 'string'],
            'web_token'             => ['nullable', 'string'],
            'phone'                 => [
                'nullable',
                'string',
                'max:20',
                Rule::unique("users", "phone")->ignore($this->route('seller.id'))
            ],
            'status'                => ['required', 'numeric', 'max:24'],
            'role_id'               => ['required', 'numeric'],
            'country_code'          => ['required', 'string', 'max:20'],
            'company_name'          => ['required', 'string', 'max:30'],
            'category'          => ['required', 'string', 'max:200'],
            'commission'          => ['required', 'numeric'],
             'photo' => [
                $this->route('seller.id') ? 'nullable' : 'required',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048'
            ],

            'license_photo' => [
                $this->route('seller.id') ? 'nullable' : 'nullable',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048'
            ],

            'nid_photo' => [
                $this->route('seller.id') ? 'nullable' : 'nullable',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048'
            ],
        ];
    }

    public function messages(){
        return [
            'password_confirmation.same' => 'Password confirmation does not match.',
            "role_id.required" => "The role field is required."
        ];
    }
}
