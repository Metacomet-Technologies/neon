<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

        $guildId = $request->route('serverId');

        $method = $request->isMethod('POST') ? 'POST' : 'PUT';

        $rules = [
            'description' => ['string', 'max:2000', 'nullable'],
            'response' => ['required', 'string', 'max:2000'],
            'is_enabled' => ['required', 'boolean'],
            'is_public' => ['required', 'boolean'],
        ];

        if ($method === 'PUT') {
            $rules['command'] = [
                'string',
                'max:50',
                'required',
                Rule::unique('neon_commands')->where(function (Builder $query) use ($guildId) {
                    $query->where('command', request()->input('command'))
                        ->where('guild_id', $guildId);
                }),
            ];
        }

        return $rules;
    }
}
