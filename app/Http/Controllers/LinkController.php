<?php

namespace App\Http\Controllers;

use App\Http\Resources\LinkResource;
use App\Models\Link;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\RouteAttributes\Attributes\Get;

class LinkController extends Controller
{
    /**
     * Handle the incoming request.
     */
    #[Get('/link/{slug}')]
    public function show(string $slug)
    {
        try {
            $linkData = QueryBuilder::for(Link::class)
                ->where('slug', $slug)
                ->where('is_active', true)
                ->firstOrFail();

            return new LinkResource($linkData);
        } catch (ModelNotFoundException $exception) {
            return response()->json(['message' => 'Link not found.'], 404);
        }
    }
}
