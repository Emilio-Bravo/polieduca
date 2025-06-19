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
        $user = $request->user();

        // Base query con optimizaciones
        $bookmarks = $user->bookmarks()
            ->with(['user:id,name']) // Ejemplo de relación opcional
            ->select('materials.id', 'title', 'file_path', 'semester', 'unit', 'created_at')
            ->when($request->semester, fn($q, $semester) => $q->where('semester', $semester))
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Transformación
        $formattedBookmarks = $bookmarks->getCollection()->map(function ($material) {
            return [
                'id' => $material->id,
                'title' => $material->title,
                'file_url' => $material->file_path ? asset("storage/{$material->file_path}") : null,
                'semester' => $material->semester,
                'unit' => $material->unit,
                'professor' => $material->user->name ?? 'Sin profesor', // Si cargaste la relación
                'created_at' => $material->created_at->toIso8601String()
            ];
        });

        return response()->json([
            'status' => 'success',
            'bookmarks' => $formattedBookmarks,
            'pagination' => [
                'current_page' => $bookmarks->currentPage(),
                'total_pages' => $bookmarks->lastPage(),
                'total_items' => $bookmarks->total()
            ]
        ]);
    }
}
