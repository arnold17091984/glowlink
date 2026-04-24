<?php

namespace App\Http\Controllers;

use App\Actions\Coupon\RedeemRewardAction;
use App\Http\Requests\RedeemRequest;
use Exception;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Route;

class RedeemRewardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    #[Route('POST', '/redeem')]
    public function __invoke(RedeemRequest $request)
    {
        $validatedData = $request->validated();
        try {

            $redeem = app(RedeemRewardAction::class)->execute($validatedData['couponCode'], $validatedData['userId']);

            return response()->json([
                'lose_title' => $redeem['lose_title'],
                'title1' => $redeem['title1'],
                'title2' => $redeem['title2'],
                'title3' => $redeem['title3'],
                'imageUrl' => $redeem['imageUrl'],
                'description' => $redeem['description'],
                'is_win' => $redeem['is_win'],
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong!',
                'error' => $e->getMessage(),
            ], 500);
        }

    }
}
