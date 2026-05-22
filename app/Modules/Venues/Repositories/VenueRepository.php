<?php

namespace App\Modules\Venues\Repositories;

use App\Modules\Venues\Filters\VenuesFilter;
use App\Modules\Venues\Interfaces\VenueInterface;
use App\Modules\Venues\Models\Address;
use App\Modules\Venues\Models\Venue;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

final class VenueRepository extends BaseRepository implements VenueInterface
{
    public function __construct(Venue $model, private Address $address)
    {
        parent::__construct($model);
    }

    public function getFilter(Builder $builder): VenuesFilter
    {
        return new VenuesFilter($builder);
    }

    public function onBeforeResult(Builder $builder): void
    {
        $builder->with('address.country')->withCount('mats');
    }

    public function getById(string $id, array $attributes = []): Venue
    {
        return $this->model->newQuery()
            ->with('address.country', 'mats')
            ->findOrFail($id);
    }

    public function createVenue(array $validated): Venue
    {
        $address = $this->address->newQuery()->create([
            'address_line_1' => $validated['address_line_1'],
            'address_line_2' => $validated['address_line_2'] ?? null,
            'city' => $validated['city'],
            'state' => $validated['state'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'country_code' => $validated['country_code'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ]);

        return $this->model->newQuery()->create([
            'name' => $validated['name'],
            'address_id' => $address->id,
        ]);
    }

    public function updateVenue(array $validated, string $venueId): Venue
    {
        $venue = $this->model->newQuery()->findOrFail($venueId);

        $venue->address->update([
            'address_line_1' => $validated['address_line_1'],
            'address_line_2' => $validated['address_line_2'] ?? null,
            'city' => $validated['city'],
            'state' => $validated['state'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'country_code' => $validated['country_code'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ]);

        $venue->update(['name' => $validated['name']]);

        return $venue;
    }

    public function deleteVenue(string $venueId): true
    {
        $venue = $this->model->newQuery()->findOrFail($venueId);
        $venue->address?->delete();
        $venue->delete();

        return true;
    }
}
