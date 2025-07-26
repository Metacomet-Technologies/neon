<?php

declare(strict_types=1);

namespace App\Nova;

use App\Models\WelcomeSetting as ModelsWelcomeSetting;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

final class WelcomeSetting extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\WelcomeSetting>
     */
    public static $model = ModelsWelcomeSetting::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'guild_id';

    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Discord Settings';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
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

            Text::make('Guild ID')
                ->sortable()
                ->rules('required'),

            BelongsTo::make('Guild')->sortable(),

            Text::make('Channel ID')
                ->sortable()
                ->rules('required'),

            Textarea::make('Message')
                ->rules('nullable'),

            Boolean::make('Is Active')
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
