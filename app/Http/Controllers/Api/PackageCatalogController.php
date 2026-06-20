<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class PackageCatalogController extends Controller
{
public function index(): JsonResponse
{
    $packages = Package::where('is_active', true)
        ->orderBy('price', 'asc')
        ->get();

    $packages->transform(function ($package) {
        $package->package_url = $package->thumbnail ? asset(Storage::disk('public')->url($package->thumbnail)) : null;
        return $package;
    });

    return response()->json([
        'data' => $packages,
    ]);
}

    public function show(Package $package): JsonResponse
    {
        if (!$package->is_active) {
            return response()->json([
                'message' => 'Paket tidak tersedia'
            ], 404);
        }

        $package->package_url = $package->thumbnail ? asset(Storage::disk('public')->url($package->thumbnail)) : null;

        return response()->json([
            'data' => $package,
        ]);
    }
}