<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\NeonCommand;
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

        /** @var array<string, array<string, string>> $rules */
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
            'embed_color' => ['nullable', 'required_if:is_embed,true', 'integer'],
        ];

        if ($method === 'POST') {
            $rules['command'][] = Rule::unique($tableName)->where(function (Builder $query) use ($guildId) {
                $query->where('command', request()->input('command'))
                    ->where('guild_id', $guildId);
            });
        }

        return $rules;
    }
}
