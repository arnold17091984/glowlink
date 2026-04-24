<?php

namespace App\Http\Controllers;

use App\Actions\Referral\ReferralAction;
use App\DataTransferObjects\ReferralData;
use App\Http\Requests\ReferralRequest;
use Exception;
use Spatie\RouteAttributes\Attributes\Route;

class ReferralController extends Controller
{
    public function __construct(

    ) {
    }

    #[Route('POST', '/referral')]
    public function __invoke(ReferralRequest $request)
    {
        try {
            $validatedData = $request->validated();

            app(ReferralAction::class)->execute(ReferralData::fromArray((array) $validatedData));

            return response()->json([
                'message' => 'Registered successfully',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
