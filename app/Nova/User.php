<?php

declare(strict_types=1);

namespace App\Nova;

use Laravel\Nova\Fields\Avatar;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

final class User extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\User>
     */
    public static $model = \App\Models\User::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    public static $group = 'Admin';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'name', 'email',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @return array<int, \Laravel\Nova\Fields\Field|\Laravel\Nova\Panel|\Laravel\Nova\ResourceTool|\Illuminate\Http\Resources\MergeValue>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Avatar::make('Avatar')
                ->thumbnail(fn ($value) => $value)
                ->preview(fn ($value) => $value)
                ->disableDownload()
                ->exceptOnForms(),

            Text::make('Name')
                ->sortable()
                ->filterable()
                ->exceptOnForms(),

            Text::make('Email')
                ->sortable()
                ->filterable()
                ->exceptOnForms(),

            Boolean::make('Is Admin', 'is_admin')
                ->sortable()
                ->filterable()
                ->default(false)
                ->rules('boolean'),

            Boolean::make('Is On Mailing List', 'is_on_mailing_list')
                ->sortable()
                ->filterable()
                ->default(true)
                ->rules('boolean'),

        ];
    }
}
