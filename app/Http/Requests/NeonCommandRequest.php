<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class NeonCommandRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $request = request();

        $rules = [
            'command' => ['required', 'string', 'max:255'],
            'description' => ['string', 'max:2000', 'nullable'],
            'response' => ['required', 'string', 'max:2000'],
            'is_enabled' => ['required', 'boolean'],
            'is_public' => ['required', 'boolean'],
        ];

        // If the request is a POST request, we need to add the unique rule for the command field
        if ($request->isMethod('POST')) {
            $rules['command'][] = 'unique:neon_commands,command';
        }

        return $rules;

    }
}
