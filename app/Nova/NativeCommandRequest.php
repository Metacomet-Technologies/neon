<?php

namespace App\Nova;

use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class NativeCommandRequest extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\NativeCommandRequest>
     */
    public static $model = \App\Models\NativeCommandRequest::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    public static $group = 'Neon';

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
     * @return array<int, \Laravel\Nova\Fields\Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),
            Text::make('Guild Id'),
            Text::make('Channel Id'),
            Text::make('Discord User Id'),
            Text::make('Message Content'),
            Code::make('Command')
                ->json(),
            Code::make('Additional Parameters')
                ->json(),
            Text::make('Status'),
            DateTime::make('Executed At'),
            DateTime::make('Failed At'),
            Code::make('Error Message')
                ->json(),
            DateTime::make('Created At'),
            DateTime::make('Updated At'),
        ];
    }
}
