<?php

namespace App\Modules\Tournaments\Repositories;

use App\Modules\Coaches\Models\Coach;
use App\Modules\Tournaments\Exceptions\PlayerAlreadyRegisteredException;
use App\Modules\Tournaments\Exceptions\TournamentCancelledException;
use App\Modules\Tournaments\Interfaces\RegistrationInterface;
use App\Modules\Tournaments\Models\Club;
use App\Modules\Tournaments\Models\Player;
use App\Modules\Tournaments\Models\Tournament;
use App\Modules\Tournaments\Models\TournamentRegistration;
use App\Modules\Tournaments\Models\TournamentRegistrationCoach;
use App\Modules\Tournaments\Models\TournamentRegistrationPayment;
use App\System\Tournament\Foundation\Enums\RegistrationStatusEnum;
use App\System\Tournament\Foundation\Enums\TournamentStatusEnum;
use App\System\Tournament\Models\TournamentCategory;
use BaseSystem\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Class RegistrationRepository
 */
final class RegistrationRepository extends BaseRepository implements RegistrationInterface
{
    public function __construct(
        private readonly Tournament $tournament,
        private readonly TournamentCategory $category,
        private readonly TournamentRegistration $registration,
        private readonly Player $player,
        private readonly Club $club,
        private readonly Coach $coach,
        private readonly TournamentRegistrationCoach $registrationCoach,
        private readonly TournamentRegistrationPayment $payment,
    ) {
        parent::__construct($registration);
    }

    public function getFilter(Builder $builder): void {}

    public function getTournamentById(string $tournamentId): Tournament
    {
        return $this->tournament->newQuery()
            ->with(['venue' => function ($query) {
                $query->select(['id', 'name']);
            }])
            ->findOrFail($tournamentId);
    }

    public function getCategoryById(string $categoryId): TournamentCategory
    {
        return $this->category->newQuery()
            ->with(['tournament.venue', 'fee'])
            ->findOrFail($categoryId);
    }

    public function getCategoryRegistrations(TournamentCategory $category): Collection
    {
        return $this->registration->newQuery()
            ->where('tournament_category_id', $category->id)
            ->with([
                'player',
                'club',
                'weighIns' => function ($query): void {
                    $query->orderByDesc('weighed_at');
                },
                'coaches.coach',
                'payments' => function ($query): void {
                    $query->orderByDesc('paid_at');
                },
            ])
            ->get();
    }

    public function getCoachList(): Collection
    {
        return $this->coach->newQuery()->orderBy('full_name')->get(['id', 'full_name']);
    }

    public function getClubList(): Collection
    {
        return $this->club->newQuery()->orderBy('name')->get();
    }

    public function getPlayerList(TournamentCategory $category): Collection
    {
        $maxDob = Carbon::now()->subYears($category->min_age)->endOfDay();
        $minDob = Carbon::now()->subYears($category->max_age)->startOfDay();

        $registeredPlayerIds = $this->registration->newQuery()
            ->where('tournament_category_id', $category->id)
            ->pluck('player_id');

        return $this->player->newQuery()
            ->whereBetween('dob', [$minDob, $maxDob])
            ->whereBetween('weight', [$category->min_weight, $category->max_weight])
            ->where('gender', $category->gender)
            ->whereNotIn('id', $registeredPlayerIds)
            ->with(['clubs' => function ($query): void {
                $query->wherePivotNull('ended_at')->select('clubs.id', 'clubs.name');
            }])
            ->orderBy('name')
            ->get();
    }

    /**
     * @throws PlayerAlreadyRegisteredException
     * @throws ValidationException
     */
    public function getRegisteringPlayerById(TournamentCategory $category, string $playerId, ?string $clubId = null, ?string $registrationId = null): Player
    {
        $maxDob = Carbon::now()->subYears($category->min_age)->endOfDay();
        $minDob = Carbon::now()->subYears($category->max_age)->startOfDay();

        $player = $this->player->newQuery()
            ->whereBetween('dob', [$minDob, $maxDob])
            ->whereBetween('weight', [$category->min_weight, $category->max_weight])
            ->where('gender', $category->gender)
            ->where('id', $playerId)
            ->with(['clubs' => function ($query): void {
                $query->wherePivotNull('ended_at');
            }])
            ->firstOrFail();

        if ($clubId !== null && ! $player->clubs->contains('id', $clubId)) {
            throw ValidationException::withMessages([
                'club_id' => 'The selected club is not one of the player\'s active clubs.',
            ]);
        }

        $existing = $this->registration->newQuery()
            ->where('tournament_category_id', $category->id)
            ->where('player_id', $playerId)
            ->whereNot('id', $registrationId)
            ->first();

        if ($existing) {
            throw new PlayerAlreadyRegisteredException;
        }

        return $player;
    }

    /**
     * @throws TournamentCancelledException
     */
    public function destroyTournamentRegistration(TournamentCategory $category, string $registrationId): true
    {
        if ($category->tournament->status === TournamentStatusEnum::Cancelled) {
            throw new TournamentCancelledException;
        }

        $registration = $this->registration->newQuery()
            ->where('tournament_category_id', $category->id)
            ->findOrFail($registrationId);

        $registration->delete();

        return true;
    }

    /**
     * @throws TournamentCancelledException
     */
    public function withdrawRegistration(TournamentCategory $category, string $registrationId): true
    {
        if ($category->tournament->status === TournamentStatusEnum::Cancelled) {
            throw new TournamentCancelledException;
        }

        $registration = $this->registration->newQuery()
            ->where('tournament_category_id', $category->id)
            ->findOrFail($registrationId);

        $registration->update(['status' => RegistrationStatusEnum::Withdrawn]);
        $registration->delete();

        return true;
    }

    /**
     * @throws TournamentCancelledException
     * @throws ValidationException
     */
    public function assignCoach(TournamentCategory $category, string $registrationId, string $coachId): TournamentRegistrationCoach
    {
        $this->assertTournamentNotCancelled($category);

        $registration = $this->findCategoryRegistration($category, $registrationId);
        $this->coach->newQuery()->findOrFail($coachId);

        $existing = $this->registrationCoach->newQuery()
            ->withTrashed()
            ->where('registration_id', $registration->id)
            ->where('coach_id', $coachId)
            ->first();

        if ($existing && ! $existing->trashed()) {
            throw ValidationException::withMessages([
                'coach_id' => 'This coach is already assigned to the registration.',
            ]);
        }

        if ($existing) {
            $existing->restore();

            return $existing;
        }

        return $this->registrationCoach->newQuery()->create([
            'registration_id' => $registration->id,
            'coach_id' => $coachId,
        ]);
    }

    /**
     * @throws TournamentCancelledException
     */
    public function removeCoach(TournamentCategory $category, string $registrationId, string $coachId): true
    {
        $this->assertTournamentNotCancelled($category);

        $registration = $this->findCategoryRegistration($category, $registrationId);

        $this->registrationCoach->newQuery()
            ->where('registration_id', $registration->id)
            ->where('coach_id', $coachId)
            ->delete();

        return true;
    }

    /**
     * @param  array<string, mixed>  $validated
     *
     * @throws TournamentCancelledException
     */
    public function recordPayment(TournamentCategory $category, string $registrationId, array $validated): TournamentRegistrationPayment
    {
        $this->assertTournamentNotCancelled($category);

        $registration = $this->findCategoryRegistration($category, $registrationId);

        return $this->payment->newQuery()->create([
            'registration_id' => $registration->id,
            'amount_paid' => $validated['amount_paid'],
            'currency' => strtoupper((string) $validated['currency']),
            'paid_at' => $validated['paid_at'] ?? Carbon::now(),
            'method' => $validated['method'],
            'reference' => $validated['reference'] ?? null,
        ]);
    }

    /**
     * @throws TournamentCancelledException
     */
    public function deletePayment(TournamentCategory $category, string $registrationId, string $paymentId): true
    {
        $this->assertTournamentNotCancelled($category);

        $registration = $this->findCategoryRegistration($category, $registrationId);

        $this->payment->newQuery()
            ->where('registration_id', $registration->id)
            ->where('id', $paymentId)
            ->delete();

        return true;
    }

    private function findCategoryRegistration(TournamentCategory $category, string $registrationId): TournamentRegistration
    {
        return $this->registration->newQuery()
            ->where('tournament_category_id', $category->id)
            ->findOrFail($registrationId);
    }

    /**
     * @throws TournamentCancelledException
     */
    private function assertTournamentNotCancelled(TournamentCategory $category): void
    {
        if ($category->tournament->status === TournamentStatusEnum::Cancelled) {
            throw new TournamentCancelledException;
        }
    }
}
