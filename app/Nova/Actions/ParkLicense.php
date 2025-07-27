<?php

declare(strict_types=1);

namespace App\Nova\Actions;

use App\Models\License;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;

final class ParkLicense extends Action
{
    use InteractsWithQueue;
    use Queueable;

    /**
     * The displayable name of the action.
     *
     * @var string
     */
    public $name = 'Park License';

    /**
     * Perform the action on the given models.
     *
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $parked = 0;

        foreach ($models as $license) {
            if ($license instanceof License && $license->isActive() && $license->isAssigned()) {
                $license->park();
                $parked++;
            }
        }

        return ActionResponse::message("Successfully parked {$parked} license(s).");
    }

    /**
     * Get the fields available on the action.
     *
     * @return array<int, Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [];
    }
}
