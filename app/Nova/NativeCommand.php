<?php

declare (strict_types = 1);

namespace App\Nova;

use Laravel\Nova\Actions\ExportAsCsv;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

final class NativeCommand extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\NativeCommand>
     */
    public static $model = \App\Models\NativeCommand::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'slug';

    public static $group = 'Neon';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
        'slug',
        'description',
        'class',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @return array<int, \Laravel\Nova\Fields\Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),
            Text::make('Slug')
                ->sortable()
                ->filterable()
                ->rules('required', 'max:255')
                ->creationRules('unique:native_commands,slug')
                ->updateRules('unique:native_commands,slug,{{resourceId}}'),
            Textarea::make('Description')
                ->sortable()
                ->filterable()
                ->alwaysShow()
                ->rules('nullable'),
            Text::make('Class')
                ->sortable()
                ->filterable()
                ->rules('required', 'max:255'),
            Textarea::make('Usage')
                ->sortable()
                ->filterable()
                ->alwaysShow()
                ->rules('nullable'),
            Textarea::make('Example')
                ->sortable()
                ->filterable()
                ->alwaysShow()
                ->rules('nullable'),
            Boolean::make('Is Active')
                ->sortable()
                ->filterable()
                ->default(true)
                ->rules('required', 'boolean'),
            DateTime::make('Created At')
                ->sortable()
                ->filterable()
                ->exceptOnForms(),
            DateTime::make('Updated At')
                ->sortable()
                ->filterable()
                ->exceptOnForms(),
        ];
    }

    public function actions(NovaRequest $request)
    {
        return [
            ExportAsCsv::make()->nameable(),
        ];
    }
}
