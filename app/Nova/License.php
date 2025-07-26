<?php

declare(strict_types=1);

namespace App\Nova;

use App\Models\License as ModelsLicense;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

final class License extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\License>
     */
    public static $model = ModelsLicense::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'stripe_id', 'assigned_guild_id',
    ];

    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return 'Licenses';
    }

    /**
     * Get the displayable singular label of the resource.
     *
     * @return string
     */
    public static function singularLabel()
    {
        return 'License';
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @return array<int, Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            BelongsTo::make('User')
                ->searchable()
                ->sortable(),

            Select::make('Type')
                ->options([
                    ModelsLicense::TYPE_SUBSCRIPTION => 'Subscription',
                    ModelsLicense::TYPE_LIFETIME => 'Lifetime',
                ])
                ->displayUsingLabels()
                ->sortable(),

            Badge::make('Status')
                ->map([
                    ModelsLicense::STATUS_ACTIVE => 'success',
                    ModelsLicense::STATUS_PARKED => 'warning',
                ])
                ->sortable(),

            Text::make('Stripe ID')
                ->readonly()
                ->hideFromIndex(),

            BelongsTo::make('Guild', 'guild', Guild::class)
                ->nullable()
                ->searchable()
                ->sortable(),

            DateTime::make('Last Assigned At')
                ->sortable()
                ->readonly(),

            DateTime::make('Created At')
                ->sortable()
                ->readonly()
                ->hideFromIndex(),

            DateTime::make('Updated At')
                ->sortable()
                ->readonly()
                ->hideFromIndex(),
        ];
    }

    /**
     * Get the filters available for the resource.
     *
     * @return array<int, Filters\Filter>
     */
    public function filters(NovaRequest $request): array
    {
        return [
            new Filters\LicenseStatus,
        ];
    }

    /**
     * Get the actions available for the resource.
     *
     * @return array<int, Actions\Action>
     */
    public function actions(NovaRequest $request): array
    {
        return [
            new Actions\ParkLicense,
        ];
    }
}
