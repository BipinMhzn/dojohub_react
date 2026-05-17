<?php

namespace App\Modules\Clubs\Repositories;

use App\Modules\Clubs\Filters\ClubsFilter;
use App\Modules\Clubs\Interfaces\ClubInterface;
use App\Modules\Clubs\Models\Address;
use App\Modules\Clubs\Models\Club;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class ClubRepository
 */
final class ClubRepository extends BaseRepository implements ClubInterface
{
    /**
     * ClubRepository constructor.
     */
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
        $builder->with('address');
    }

    public function createClub(array $validated): Club
    {
        $address = $this->address->newQuery()->create([
            'address' => $validated['address'],
            'city'    => $validated['city'],
            'state'   => $validated['state'],
            'country' => $validated['country'],
        ]);

        return $this->model->newQuery()->create([
            'name'         => $validated['name'],
            'abbreviation' => $validated['abbreviation'],
            'logo_url'     => $validated['logo_url'] ?? null,
            'address_id'   => $address->id,
            'phone_number' => $validated['phone_number'] ?? null,
        ]);
    }

    public function updateClub(array $validated, string $clubId): Club
    {
        $clubModel = $this->model->newQuery()->findOrFail($clubId);

        $clubModel->address->update([
            'address' => $validated['address'],
            'city'    => $validated['city'],
            'state'   => $validated['state'],
            'country' => $validated['country'],
        ]);

        $clubModel->update([
            'name'         => $validated['name'],
            'abbreviation' => $validated['abbreviation'],
            'logo_url'     => $validated['logo_url'] ?? $clubModel->logo_url,
            'phone_number' => $validated['phone_number'] ?? null,
        ]);

        return $clubModel;
    }

    public function deleteClub(string $clubId): true
    {
        $clubModel = $this->model->newQuery()->findOrFail($clubId);
        $clubModel->address?->delete();
        $clubModel->delete();

        return true;
    }

    public function getById(string $id, array $attributes = []): Club
    {
        return $this->model->newQuery()->with('address')->findOrFail($id);
    }
}

