<?php

declare(strict_types=1);

namespace App\Nova;

use App\Models\Guild as ModelsGuild;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

final class Guild extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Guild>
     */
    public static $model = ModelsGuild::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'name',
    ];

    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return 'Discord Guilds';
    }

    /**
     * Get the displayable singular label of the resource.
     *
     * @return string
     */
    public static function singularLabel()
    {
        return 'Guild';
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @return array<int, Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            Text::make('ID', 'id')
                ->sortable()
                ->readonly()
                ->help('Discord Guild ID (Snowflake)'),

            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('Icon')
                ->nullable()
                ->hideFromIndex()
                ->help('Discord icon hash'),

            Number::make('Active Licenses', function () {
                return $this->activeLicenses()->count();
            })
                ->sortable()
                ->readonly(),

            HasMany::make('Licenses'),

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
}
