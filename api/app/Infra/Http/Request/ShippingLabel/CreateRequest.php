<?php

namespace App\Infra\Http\Request\ShippingLabel;

use App\Core\Application\ShippingLabel\Create;
use Illuminate\Foundation\Http\FormRequest;

final class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_address' => ['required', 'array'],
            'from_address.name' => ['required', 'string'],
            'from_address.street1' => ['required', 'string'],
            'from_address.street2' => ['nullable', 'string'],
            'from_address.city' => ['required', 'string'],
            'from_address.state' => ['required', 'string', 'size:2'],
            'from_address.zip' => ['required', 'string', 'regex:/^\d{5}(-\d{4})?$/'],
            'from_address.country' => ['required', 'string'],
            'to_address' => ['required', 'array'],
            'to_address.name' => ['required', 'string'],
            'to_address.street1' => ['required', 'string'],
            'to_address.street2' => ['nullable', 'string'],
            'to_address.city' => ['required', 'string'],
            'to_address.state' => ['required', 'string', 'size:2'],
            'to_address.zip' => ['required', 'string', 'regex:/^\d{5}(-\d{4})?$/'],
            'to_address.country' => ['required', 'string'],
            'parcel' => ['required', 'array'],
            'parcel.weight_oz' => ['required', 'numeric', 'gt:0'],
            'parcel.length_in' => ['required', 'numeric', 'gt:0'],
            'parcel.width_in' => ['required', 'numeric', 'gt:0'],
            'parcel.height_in' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_address.zip.regex' => 'The from address ZIP code must be a valid United States ZIP code.',
            'to_address.zip.regex' => 'The destination address ZIP code must be a valid United States ZIP code.',
        ];
    }

    public function toCommand(): Create
    {
        return new Create(
            fromAddress: $this->validated('from_address'),
            toAddress: $this->validated('to_address'),
            parcel: $this->validated('parcel'),
        );
    }
}
