<?php

namespace App\Modules\Venues\Repositories;

use App\Modules\Venues\Filters\VenuesFilter;
use App\Modules\Venues\Interfaces\VenueInterface;
use App\Modules\Venues\Models\Address;
use App\Modules\Venues\Models\Venue;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class VenueRepository
 */
final class VenueRepository extends BaseRepository implements VenueInterface
{
    /**
     * VenueRepository constructor.
     */
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
        $builder->with('address');
    }

    public function createVenue(array $validated): Venue
    {
        $address = $this->address->newQuery()->create([
            'address' => $validated['address'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'country' => $validated['country'],
        ]);

        return $this->model->newQuery()->create([
            'name' => $validated['name'],
            'mat_count' => $validated['mat_count'],
            'address_id' => $address->id,
        ]);
    }

    public function updateVenue(array $validated, string $venueId): Venue
    {
        $venueModel = $this->model->newQuery()->findOrFail($venueId);

        $venueModel->address->update([
            'address' => $validated['address'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'country' => $validated['country'],
        ]);

        $venueModel->update([
            'name' => $validated['name'],
            'mat_count' => $validated['mat_count'],
        ]);

        return $venueModel;
    }

    public function getById(string $id, array $attributes = []): Venue
    {
        return $this->model->newQuery()->with('address')->findOrFail($id);
    }

    public function deleteVenue(string $venueId): true
    {
        $venueModel = $this->model->newQuery()->findOrFail($venueId);
        $venueModel->address?->delete();

        $venueModel->delete();
        return true;
    }
}
