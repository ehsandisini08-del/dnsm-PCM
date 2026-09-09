<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', 'in:NATIVE,MASTER,SLAVE,native,master,slave'],
            'master' => ['nullable', 'ip'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'dns_server_id' => ['nullable', 'exists:dns_servers,id'],
            'status' => ['nullable', 'in:active,disabled,suspended'],
        ];
    }
}
