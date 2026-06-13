<?php

namespace App\Modules\Tournaments\Controllers;

use App\Modules\Tournaments\Exceptions\TournamentCancelledException;
use App\Modules\Tournaments\Interfaces\RegistrationInterface;
use App\Modules\Tournaments\Requests\PaymentStoreRequest;
use BaseSystem\Controllers\BaseWebController;
use Illuminate\Http\RedirectResponse;

/**
 * Class RegistrationPaymentsController
 */
final class RegistrationPaymentsController extends BaseWebController
{
    public function __construct(
        private readonly RegistrationInterface $repository,
    ) {}

    public function store(PaymentStoreRequest $request, string $tournamentId, string $categoryId, string $registrationId): RedirectResponse
    {
        try {
            $category = $this->repository->getCategoryById($categoryId);
            $this->repository->recordPayment($category, $registrationId, $request->validated());

            return back()->with('success', 'Payment recorded successfully.');
        } catch (TournamentCancelledException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(string $tournamentId, string $categoryId, string $registrationId, string $paymentId): RedirectResponse
    {
        try {
            $category = $this->repository->getCategoryById($categoryId);
            $this->repository->deletePayment($category, $registrationId, $paymentId);

            return back()->with('success', 'Payment removed successfully.');
        } catch (TournamentCancelledException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
