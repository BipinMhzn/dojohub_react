<?php

namespace App\Modules\Tournaments\Repositories;

use App\Modules\Tournaments\Exceptions\TournamentCancelledException;
use App\Modules\Tournaments\Exceptions\WeighInNotAllowedException;
use App\Modules\Tournaments\Interfaces\WeighInInterface;
use App\Modules\Tournaments\Models\TournamentRegistration;
use App\Modules\Tournaments\Models\TournamentWeighIn;
use App\System\Tournament\Foundation\Enums\CategoryStatusEnum;
use App\System\Tournament\Foundation\Enums\TournamentStatusEnum;
use App\System\Tournament\Models\TournamentCategory;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class WeighInRepository
 */
final class WeighInRepository extends BaseRepository implements WeighInInterface
{
    public function __construct(
        private readonly TournamentCategory $category,
        private readonly TournamentRegistration $registration,
        TournamentWeighIn $model,
    ) {
        parent::__construct($model);
    }

    public function getFilter(Builder $builder): void {}

    public function getCategoryById(string $categoryId): TournamentCategory
    {
        return $this->category->newQuery()
            ->with('tournament')
            ->findOrFail($categoryId);
    }

    /**
     * @throws TournamentCancelledException
     * @throws WeighInNotAllowedException
     */
    public function recordWeighIn(TournamentCategory $category, array $validated): TournamentWeighIn
    {
        if ($category->tournament->status === TournamentStatusEnum::Cancelled) {
            throw new TournamentCancelledException;
        }

        if (! in_array($category->status, [CategoryStatusEnum::RegistrationClosed, CategoryStatusEnum::BracketGenerated], true)) {
            throw new WeighInNotAllowedException;
        }

        $registration = $this->registration->newQuery()
            ->where('tournament_category_id', $category->id)
            ->with('player')
            ->findOrFail($validated['registration_id']);

        $weight = (float) $validated['weight'];
        $madeWeight = $weight >= (float) $category->min_weight && $weight <= (float) $category->max_weight;

        $weighIn = $this->model->newQuery()->create([
            'registration_id' => $registration->id,
            'weight' => $weight,
            'weighed_at' => now(),
            'weighed_by' => auth()->id(),
            'made_weight' => $madeWeight,
            'notes' => $validated['notes'] ?? null,
        ]);

        // The weigh-in is the authoritative live weight, so keep the player's profile in sync.
        if ($registration->player && (float) $registration->player->weight !== $weight) {
            $registration->player->update(['weight' => $weight]);
        }

        return $weighIn;
    }
}
