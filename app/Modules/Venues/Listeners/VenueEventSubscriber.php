<?php

namespace App\Modules\Venues\Listeners;

use App\Modules\Venues\Exceptions\VenueInUseException;
use App\Modules\Venues\Exceptions\VenueMatInUseException;
use App\Modules\Venues\Models\Venue;
use App\Modules\Venues\Models\VenueMat;
use App\System\Tournament\Models\Tournament;
use App\System\Tournament\Models\TournamentMatch;
use Illuminate\Events\Dispatcher;

final class VenueEventSubscriber
{
    public function handleVenueDeleting(Venue $venue): void
    {
        if (Tournament::where('venue_id', $venue->id)->exists()) {
            throw new VenueInUseException(
                "Cannot delete \"{$venue->name}\" because it is assigned to one or more tournaments."
            );
        }
    }

    public function handleVenueMatDeleting(VenueMat $mat): void
    {
        if (TournamentMatch::where('mat_id', $mat->id)->exists()) {
            throw new VenueMatInUseException(
                'Cannot remove this mat because it is assigned to one or more tournament matches.'
            );
        }
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            'eloquent.deleting: '.Venue::class => 'handleVenueDeleting',
            'eloquent.deleting: '.VenueMat::class => 'handleVenueMatDeleting',
        ];
    }
}
