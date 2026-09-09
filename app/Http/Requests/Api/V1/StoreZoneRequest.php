<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:domains,name',
                'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/i',
            ],
            'type' => ['nullable', 'string', 'in:NATIVE,MASTER,SLAVE,native,master,slave'],
            'master' => ['nullable', 'required_if:type,SLAVE,slave', 'ip'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'dns_server_id' => ['nullable', 'exists:dns_servers,id'],
            'status' => ['nullable', 'in:active,disabled,suspended'],
            'custom_ns' => ['nullable', 'array'],
            'custom_ns.*' => ['string'],
        ];
    }
}
