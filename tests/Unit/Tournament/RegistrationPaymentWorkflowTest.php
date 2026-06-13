<?php

namespace Tests\Unit\Tournament;

use App\Modules\Tournaments\Exceptions\TournamentCancelledException;
use App\Modules\Tournaments\Interfaces\RegistrationInterface;
use App\System\Club\Models\Club;
use App\System\Player\Models\Player;
use App\System\Tournament\Foundation\Enums\CategoryStatusEnum;
use App\System\Tournament\Foundation\Enums\PaymentMethodEnum;
use App\System\Tournament\Foundation\Enums\RegistrationStatusEnum;
use App\System\Tournament\Foundation\Enums\TournamentStatusEnum;
use App\System\Tournament\Models\Tournament;
use App\System\Tournament\Models\TournamentCategory;
use App\System\Tournament\Models\TournamentRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationPaymentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function repository(): RegistrationInterface
    {
        return app(RegistrationInterface::class);
    }

    private function makeRegistration(?Tournament $tournament = null): TournamentRegistration
    {
        $tournament ??= Tournament::factory()->create();
        $category = TournamentCategory::create([
            'tournament_id' => $tournament->id,
            'gender' => 0,
            'age_category' => 8,
            'min_age' => 18,
            'max_age' => 34,
            'weight_category' => 0,
            'min_weight' => 60,
            'max_weight' => 90,
            'status' => CategoryStatusEnum::RegistrationClosed,
        ]);

        return TournamentRegistration::create([
            'tournament_category_id' => $category->id,
            'club_id' => Club::factory()->create()->id,
            'player_id' => Player::factory()->create()->id,
            'player_age' => 25,
            'status' => RegistrationStatusEnum::Confirmed,
        ]);
    }

    public function test_a_payment_is_recorded_against_the_registration(): void
    {
        $registration = $this->makeRegistration();
        $category = $this->repository()->getCategoryById($registration->tournament_category_id);

        $payment = $this->repository()->recordPayment($category, $registration->id, [
            'amount_paid' => 50,
            'currency' => 'npr',
            'method' => PaymentMethodEnum::Cash->value,
            'reference' => 'TXN-1',
        ]);

        $this->assertEquals(50, $payment->amount_paid);
        $this->assertSame('NPR', $payment->currency);
        $this->assertSame(PaymentMethodEnum::Cash, $payment->method);
        $this->assertNotNull($payment->paid_at, 'paid_at defaults to now when omitted.');
    }

    public function test_the_payment_method_is_cast_to_the_enum(): void
    {
        $registration = $this->makeRegistration();
        $category = $this->repository()->getCategoryById($registration->tournament_category_id);

        $this->repository()->recordPayment($category, $registration->id, [
            'amount_paid' => 30,
            'currency' => 'USD',
            'method' => PaymentMethodEnum::Online->value,
        ]);

        $this->assertSame(PaymentMethodEnum::Online, $registration->payments()->first()->method);
    }

    public function test_total_paid_sums_across_multiple_payments(): void
    {
        $registration = $this->makeRegistration();
        $category = $this->repository()->getCategoryById($registration->tournament_category_id);

        $this->repository()->recordPayment($category, $registration->id, ['amount_paid' => 20, 'currency' => 'NPR', 'method' => 0]);
        $this->repository()->recordPayment($category, $registration->id, ['amount_paid' => 35.50, 'currency' => 'NPR', 'method' => 1]);

        $this->assertEquals(55.50, $registration->payments()->sum('amount_paid'));
    }

    public function test_a_payment_can_be_deleted(): void
    {
        $registration = $this->makeRegistration();
        $category = $this->repository()->getCategoryById($registration->tournament_category_id);

        $payment = $this->repository()->recordPayment($category, $registration->id, [
            'amount_paid' => 40,
            'currency' => 'NPR',
            'method' => 0,
        ]);

        $this->repository()->deletePayment($category, $registration->id, $payment->id);

        $this->assertSoftDeleted('tournament_registration_payments', ['id' => $payment->id]);
        $this->assertEquals(0, $registration->payments()->sum('amount_paid'));
    }

    public function test_deleting_a_payment_blocked_when_tournament_cancelled(): void
    {
        $registration = $this->makeRegistration();
        $category = $this->repository()->getCategoryById($registration->tournament_category_id);
        $payment = $this->repository()->recordPayment($category, $registration->id, [
            'amount_paid' => 40,
            'currency' => 'NPR',
            'method' => 0,
        ]);

        $registration->category->tournament->update(['status' => TournamentStatusEnum::Cancelled]);
        $category = $this->repository()->getCategoryById($registration->tournament_category_id);

        $this->expectException(TournamentCancelledException::class);

        $this->repository()->deletePayment($category, $registration->id, $payment->id);
    }

    public function test_recording_a_payment_blocked_when_tournament_cancelled(): void
    {
        $tournament = Tournament::factory()->create(['status' => TournamentStatusEnum::Cancelled]);
        $registration = $this->makeRegistration($tournament);
        $category = $this->repository()->getCategoryById($registration->tournament_category_id);

        $this->expectException(TournamentCancelledException::class);

        $this->repository()->recordPayment($category, $registration->id, [
            'amount_paid' => 10,
            'currency' => 'NPR',
            'method' => 0,
        ]);
    }
}
