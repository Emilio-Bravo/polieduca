<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    // Añadir a favoritos
    public function store(Material $material)
    {
        auth()->user()->bookmarks()->attach($material->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Material añadido a favoritos'
        ]);
    }

    // Eliminar de favoritos
    public function destroy(Material $material)
    {
        auth()->user()->bookmarks()->detach($material->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Material removido de favoritos'
        ]);
    }

    public function index(Request $request)
    {
        $bookmarks = $request->user()
            ->bookmarks()
            ->select('id', 'title', 'file_path', 'semester', 'unit', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Transformar datos para Android
        $formattedBookmarks = $bookmarks->getCollection()->map(function ($material) {
            return [
                'id' => $material->id,
                'title' => $material->title,
                'file_url' => asset("storage/{$material->file_path}"),
                'semester' => $material->semester,
                'unit' => $material->unit,
                'created_at' => $material->created_at->toIso8601String()
            ];
        });

        return response()->json([
            'status' => 'success',
            'bookmarks' => $formattedBookmarks,
            'pagination' => [
                'current_page' => $bookmarks->currentPage(),
                'total_pages' => $bookmarks->lastPage(),
                'total_items' => $bookmarks->total(),
                'per_page' => $bookmarks->perPage()
            ]
        ]);
    }
}
