<?php

namespace App\Http\Requests\Api\V1;

use App\Services\PowerDNSService;
use Illuminate\Foundation\Http\FormRequest;

class StoreRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creatableTypes = array_diff(PowerDNSService::SUPPORTED_TYPES, ['SOA']);
        $typeList = implode(',', $creatableTypes).','.strtolower(implode(',', $creatableTypes));

        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:'.$typeList],
            'content' => ['required', 'string'],
            'ttl' => ['nullable', 'integer', 'min:1', 'max:2147483647'],
            'prio' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'disabled' => ['nullable', 'boolean'],
        ];
    }
}
