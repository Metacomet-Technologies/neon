<?php

declare(strict_types=1);

namespace App\Nova;

use App\Models\CommandUsageMetric as ModelsCommandUsageMetric;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

final class CommandUsageMetric extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\CommandUsageMetric>
     */
    public static $model = ModelsCommandUsageMetric::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'command_slug';

    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Analytics';

    /**
     * Indicates if the resource should be displayed in the sidebar.
     *
     * @var bool
     */
    public static $displayInNavigation = false;

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'command_slug',
        'guild_id',
        'error_category',
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

            Select::make('Command Type')
                ->options([
                    'native' => 'Native',
                    'neon' => 'Neon',
                ])
                ->sortable()
                ->rules('required'),

            Text::make('Command Slug')
                ->sortable()
                ->rules('required'),

            Text::make('Command Hash')
                ->hideFromIndex()
                ->readonly(),

            Text::make('Guild ID')
                ->sortable()
                ->rules('required'),

            Text::make('User Hash')
                ->hideFromIndex()
                ->readonly()
                ->help('Hashed Discord User ID for privacy'),

            Text::make('Channel Type')
                ->sortable()
                ->rules('nullable'),

            Code::make('Parameter Signature')
                ->json()
                ->hideFromIndex()
                ->help('Tokenized parameter patterns'),

            Number::make('Parameter Count')
                ->sortable(),

            Boolean::make('Had Errors')
                ->sortable(),

            Text::make('Execution Duration (ms)')
                ->sortable()
                ->rules('nullable'),

            DateTime::make('Executed At')
                ->sortable()
                ->rules('required'),

            Date::make('Date')
                ->sortable()
                ->readonly(),

            Number::make('Hour')
                ->min(0)
                ->max(23)
                ->hideFromIndex(),

            Number::make('Day of Week')
                ->min(0)
                ->max(6)
                ->hideFromIndex(),

            Select::make('Status')
                ->options([
                    'success' => 'Success',
                    'failed' => 'Failed',
                    'timeout' => 'Timeout',
                ])
                ->sortable()
                ->rules('required'),

            Text::make('Error Category')
                ->sortable()
                ->rules('nullable'),

            DateTime::make('Created At')
                ->sortable()
                ->readonly(),

            DateTime::make('Updated At')
                ->sortable()
                ->readonly(),
        ];
    }
}
