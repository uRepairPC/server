<?php

namespace App\Http\Controllers\Stat;

use App\Enums\Permissions;
use Illuminate\Http\Request;
use App\Http\Json\GlobalFile;
use App\Http\Requests\GlobalRequest;
use App\Http\Controllers\Controller;
use App\Events\Settings\EGlobalUpdate;
use App\Http\Resources\GlobalJsonResource;

class GlobalController extends Controller
{
    /**
     * Add middleware depends on user permissions.
     *
     * @param  Request  $request
     * @return array
     */
    public function permissions(Request $request): array
    {
        return [
            'store' => Permissions::GLOBAL_SETTINGS,
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $json = (new GlobalFile)->getData();

        return response()->json(new GlobalJsonResource($json));
    }

    /**
     * Update all resources in storage.
     *
     * @param  GlobalRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(GlobalRequest $request)
    {
        $globalFile = new GlobalFile;
        $data = $request->validated();

        $globalFile->transformDataAndRequestFiles($data);
        $globalFile->mergeAndSaveToFile($data);

        $jsonResource = new GlobalJsonResource($data);
        event(new EGlobalUpdate($jsonResource));

        return response()->json([
            'message' => __('app.settings.global'),
            'data' => $jsonResource,
        ]);
    }
}