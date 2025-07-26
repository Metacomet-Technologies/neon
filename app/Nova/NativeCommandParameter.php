<?php

declare(strict_types=1);

namespace App\Nova;

use App\Models\NativeCommandParameter as ModelsNativeCommandParameter;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;

final class NativeCommandParameter extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\NativeCommandParameter>
     */
    public static $model = ModelsNativeCommandParameter::class;

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
        ];
    }
}
