<?php

namespace App\Modules\Clubs\Repositories;

use App\Modules\Clubs\Filters\ClubsFilter;
use App\Modules\Clubs\Interfaces\ClubInterface;
use App\Modules\Clubs\Models\Address;
use App\Modules\Clubs\Models\Club;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

final class ClubRepository extends BaseRepository implements ClubInterface
{
    public function __construct(Club $model, private Address $address)
    {
        parent::__construct($model);
    }

    public function getFilter(Builder $builder): ClubsFilter
    {
        return new ClubsFilter($builder);
    }

    public function onBeforeResult(Builder $builder): void
    {
        $builder->with('address.country');
    }

    public function getById(string $id, array $attributes = []): Club
    {
        return $this->model->newQuery()->with('address.country')->findOrFail($id);
    }

    public function createClub(array $validated): Club
    {
        $addressId = null;

        if (! empty($validated['address_line_1'])) {
            $address = $this->address->newQuery()->create([
                'address_line_1' => $validated['address_line_1'],
                'address_line_2' => $validated['address_line_2'] ?? null,
                'city' => $validated['city'] ?? null,
                'state' => $validated['state'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'country_code' => $validated['country_code'],
            ]);
            $addressId = $address->id;
        }

        return $this->model->newQuery()->create([
            'name' => $validated['name'],
            'abbreviation' => $validated['abbreviation'],
            'logo_url' => $validated['logo_url'] ?? null,
            'address_id' => $addressId,
            'phone_number' => $validated['phone_number'] ?? null,
        ]);
    }

    public function updateClub(array $validated, string $clubId): Club
    {
        $club = $this->model->newQuery()->findOrFail($clubId);

        if (! empty($validated['address_line_1'])) {
            $addressData = [
                'address_line_1' => $validated['address_line_1'],
                'address_line_2' => $validated['address_line_2'] ?? null,
                'city' => $validated['city'] ?? null,
                'state' => $validated['state'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'country_code' => $validated['country_code'],
            ];

            if ($club->address) {
                $club->address->update($addressData);
            } else {
                $address = $this->address->newQuery()->create($addressData);
                $club->address_id = $address->id;
            }
        }

        $club->update([
            'name' => $validated['name'],
            'abbreviation' => $validated['abbreviation'],
            'logo_url' => $validated['logo_url'] ?? $club->logo_url,
            'phone_number' => $validated['phone_number'] ?? null,
        ]);

        return $club;
    }

    public function deleteClub(string $clubId): true
    {
        $club = $this->model->newQuery()->findOrFail($clubId);
        $club->address?->delete();
        $club->delete();

        return true;
    }
}
