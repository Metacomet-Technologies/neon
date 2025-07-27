<?php

declare(strict_types=1);

namespace App\Nova;

use App\Models\NeonCommand as ModelsNeonCommand;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

final class NeonCommand extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\NeonCommand>
     */
    public static $model = ModelsNeonCommand::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'command';

    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Discord Commands';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'command',
        'description',
        'response',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @return array<int, Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Text::make('Command')
                ->sortable()
                ->rules('required', 'max:255'),

            Textarea::make('Description')
                ->rules('nullable'),

            Textarea::make('Response')
                ->rules('nullable'),

            Text::make('Guild ID')
                ->sortable()
                ->rules('required'),

            BelongsTo::make('Guild')->sortable(),

            Boolean::make('Is Enabled')
                ->sortable(),

            Boolean::make('Is Public')
                ->sortable(),

            Boolean::make('Is Embed')
                ->sortable(),

            Text::make('Embed Title')
                ->rules('nullable', 'max:255'),

            Textarea::make('Embed Description')
                ->rules('nullable'),

            Number::make('Embed Color')
                ->rules('nullable', 'integer'),

            BelongsTo::make('Created By', 'createdByUser', User::class)
                ->sortable(),

            BelongsTo::make('Updated By', 'updatedByUser', User::class)
                ->sortable(),

            DateTime::make('Created At')
                ->sortable()
                ->readonly(),

            DateTime::make('Updated At')
                ->sortable()
                ->readonly(),
        ];
    }
}
