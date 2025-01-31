<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\NeonCommand;
use Illuminate\Foundation\Http\FormRequest;

final class NeonCommandRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        /** @var \Illuminate\Http\Request $request */
        $request = request();

        /** @var string $guildId */
        $guildId = $request->route('serverId');

        /** @var string $method */
        $method = $request->isMethod('POST') ? 'POST' : 'PUT';

        /** @var \App\Models\NeonCommand $model */
        $model = new NeonCommand;

        /** @var string $tableName */
        $tableName = $model->getTable();

        /** @var array<string, array<int, string>> $rules */
        $rules = [
            'command' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => ['nullable', 'string'],
            'response' => ['nullable', 'string', 'required_if:is_embed,false'],
            'is_enabled' => ['required', 'boolean'],
            'is_public' => ['required', 'boolean'],
            'is_embed' => ['required', 'boolean'],
            'embed_title' => ['nullable', 'string', 'max:255', 'required_if:is_embed,true'],
            'embed_description' => ['nullable', 'string', 'required_if:is_embed,true'],
        ];

        return $rules;
    }
}
