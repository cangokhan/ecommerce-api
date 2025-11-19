<?php

namespace App\Http\Controllers;

use App\Models\XmlSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class XmlSourceController extends Controller
{
    /**
     * Display a listing of XML sources.
     */
    public function index(): JsonResponse
    {
        $sources = XmlSource::latest()->get();

        return response()->json([
            'sources' => $sources,
        ]);
    }

    /**
     * Store a newly created XML source.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'is_active' => 'nullable|boolean',
            'import_interval_hours' => 'nullable|integer|min:1|max:168', // Max 1 week
            'preferred_import_time' => 'nullable|date_format:H:i',
        ]);

        $source = XmlSource::create([
            'name' => $validated['name'],
            'url' => $validated['url'],
            'is_active' => $validated['is_active'] ?? true,
            'import_interval_hours' => $validated['import_interval_hours'] ?? 24,
            'preferred_import_time' => $validated['preferred_import_time'] ?? null,
        ]);

        return response()->json([
            'message' => 'XML source created successfully',
            'source' => $source,
        ], 201);
    }

    /**
     * Display the specified XML source.
     */
    public function show(XmlSource $xmlSource): JsonResponse
    {
        return response()->json([
            'source' => $xmlSource,
        ]);
    }

    /**
     * Update the specified XML source.
     */
    public function update(Request $request, XmlSource $xmlSource): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'url' => 'sometimes|required|url|max:500',
            'is_active' => 'nullable|boolean',
            'import_interval_hours' => 'nullable|integer|min:1|max:168',
            'preferred_import_time' => 'nullable|date_format:H:i',
        ]);

        $xmlSource->update($validated);

        return response()->json([
            'message' => 'XML source updated successfully',
            'source' => $xmlSource->fresh(),
        ]);
    }

    /**
     * Remove the specified XML source.
     */
    public function destroy(XmlSource $xmlSource): JsonResponse
    {
        $xmlSource->delete();

        return response()->json([
            'message' => 'XML source deleted successfully',
        ]);
    }

    /**
     * Manually trigger import for a source.
     */
    public function import(XmlSource $xmlSource): JsonResponse
    {
        if (!$xmlSource->is_active) {
            return response()->json([
                'message' => 'XML source is inactive',
            ], 400);
        }

        \App\Jobs\ImportProductsFromXmlJob::dispatch($xmlSource->id);

        return response()->json([
            'message' => 'Import job has been queued',
            'source' => $xmlSource,
        ]);
    }
}

