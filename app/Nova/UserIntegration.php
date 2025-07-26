<?php

declare(strict_types=1);

namespace App\Nova;

use App\Models\UserIntegration as ModelsUserIntegration;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

final class UserIntegration extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\UserIntegration>
     */
    public static $model = ModelsUserIntegration::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'provider';

    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'User Management';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'provider',
        'provider_id',
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

            BelongsTo::make('User')->sortable(),

            Text::make('Provider')
                ->sortable()
                ->rules('required'),

            Text::make('Provider ID')
                ->sortable()
                ->rules('required'),

            Code::make('Data')
                ->json()
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
