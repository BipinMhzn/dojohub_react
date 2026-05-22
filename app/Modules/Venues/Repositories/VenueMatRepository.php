<?php

namespace App\Modules\Venues\Repositories;

use App\Modules\Venues\Interfaces\VenueMatInterface;
use App\Modules\Venues\Models\VenueMat;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

final class VenueMatRepository extends BaseRepository implements VenueMatInterface
{
    public function __construct(VenueMat $model)
    {
        parent::__construct($model);
    }

    public function getFilter(Builder $builder): mixed
    {
        return $builder;
    }

    public function createMat(string $venueId, array $validated): VenueMat
    {
        return $this->model->newQuery()->create([
            'venue_id' => $venueId,
            'mat_number' => $validated['mat_number'],
            'display_name' => $validated['display_name'] ?? null,
        ]);
    }

    public function updateMat(string $matId, array $validated): VenueMat
    {
        $mat = $this->model->newQuery()->findOrFail($matId);
        $mat->update([
            'mat_number' => $validated['mat_number'],
            'display_name' => $validated['display_name'] ?? null,
        ]);

        return $mat;
    }

    public function deleteMat(string $matId): true
    {
        $this->model->newQuery()->findOrFail($matId)->delete();

        return true;
    }
}
