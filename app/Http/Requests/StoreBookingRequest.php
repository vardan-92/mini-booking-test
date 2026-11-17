<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'slot_id' => ['bail', 'required', 'integer:strict', 'exists:slots,id'],
            'customer_name' => ['bail', 'required', 'string', 'max:255'],
            'customer_email' => ['bail', 'required', 'string', 'max:254', 'email:rfc'],
        ];
    }

    public function messages(): array
    {
        return [
            'slot_id.required' => 'Выберите слот.',
            'slot_id.integer' => 'Укажите корректный номер слота.',
            'slot_id.exists' => 'Этот слот не найден в расписании.',
            'customer_name.required' => 'Введите имя.',
            'customer_name.string' => 'Имя должно быть текстом.',
            'customer_name.max' => 'Имя должно содержать не более 255 символов.',
            'customer_email.required' => 'Введите email.',
            'customer_email.string' => 'Email должен быть текстом.',
            'customer_email.max' => 'Email должен содержать не более 254 символов.',
            'customer_email.email' => 'Введите корректный email.',
        ];
    }
}
