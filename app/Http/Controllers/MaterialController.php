<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Material;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        // 1. Base query con selección de campos
        $query = Material::query()
            ->select('id', 'title', 'file_path', 'semester', 'unit', 'user_id', 'created_at')
            ->with(['user:id,name']) // Carga el nombre del profesor
            ->orderBy('created_at', 'desc');

        // 2. Aplicar filtros dinámicos
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%"); // Búsqueda por profesor
                    });
            });
        }

        // 3. Filtros exactos
        if ($semester = $request->query('semester')) {
            $query->where('semester', $semester);
        }

        if ($unit = $request->query('unit')) {
            $query->where('unit', $unit);
        }

        // 4. Paginación y transformación
        $materials = $query->paginate(10);

        $formattedMaterials = $materials->getCollection()->map(function ($material) {
            return [
                'id' => $material->id,
                'title' => $material->title,
                'professor' => $material->user->name, // Nombre del profesor
                'file_url' => asset("storage/{$material->file_path}"),
                'semester' => $material->semester,
                'unit' => $material->unit,
                'created_at' => $material->created_at->toIso8601String(),
                'is_bookmarked' => auth()->user() ? auth()->user()->bookmarks()->where('material_id', $material->id)->exists() : false
            ];
        });

        return response()->json([
            'status' => 'success',
            'content' => $formattedMaterials,
            'filters' => [ // Opcional: Devuelve los filtros aplicados
                'search' => $request->query('search'),
                'semester' => $request->query('semester'),
                'unit' => $request->query('unit')
            ],
            'pagination' => [
                'current_page' => $materials->currentPage(),
                'total_pages' => $materials->lastPage()
            ]
        ]);
    }
    public function store(Request $request)
    {
        try {
            $validated = $this->validateRequest($request);

            $filePath = $this->uploadFile($request);

            $material = Material::create([
                'user_id' => $validated['user_id'], // Asignar el ID del usuario autenticado
                'title' => $validated['title'],
                'semester' => $validated['semester'] ?? null,
                'unit' => $validated['unit'] ?? null,
                'file_path' => $filePath,
                'rating' => $validated['rating'] ?? 0,
            ]);

            return response()->json([
                'status' => 'created',
                'data' => $material
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $material = Material::findOrFail($id);

            $user = auth()->user(); // Obtiene el usuario autenticado

            // Debug: Verifica antes de la autorización
            \Illuminate\Support\Facades\Log::debug("Datos pre-autorización", [
                'authenticated_user_id' => $user->id,
                'material_user_id' => $material->user_id
            ]);

            if (!Gate::allows('update', $material)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No tienes permiso. Requieres ser el dueño o administrador',
                    'debug' => [
                        'user_id' => $user->id,
                        'material_owner_id' => $material->user_id,
                        'user_role' => $user->role
                    ]
                ], 403);
            }

            // Validación para update (puede que el archivo no sea requerido)
            $validated = $request->validate([
                'title' => 'sometimes|string|max:100',
                'file' => 'sometimes|file|mimes:pdf,docx,pptx|max:102400',
                'rating' => 'sometimes|integer|between:0,5',
                'semester' => 'sometimes|integer|between:1,6',
                'unit' => 'sometimes|integer|between:1,4'
            ]);

            // Actualizar archivo si viene en la request
            if ($request->hasFile('file')) {
                Storage::disk('public')->delete($material->file_path); // Borrar el antiguo
                $validated['file_path'] = $this->uploadFile($request);
            }

            $material->update($validated);

            return response()->json([
                'status' => 'updated',
                'data' => $material
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['status' => 'Material not found'], 404);
        }
    }

    public function destroy($id)
    {
        try {
            $material = Material::findOrFail($id);

            // Autorización
            if (!Gate::allows('delete', $material)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No tienes permiso para eliminar este material'
                ], 403);
            }

            // Eliminar archivo físico
            Storage::disk('public')->delete($material->file_path);

            // Eliminar registro
            $material->delete();

            return response()->json([
                'status' => 'deleted'
            ], 204);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['status' => 'Material not found'], 404);
        }
    }

    public function show($id)
    {
        $material = Material::findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $material
        ]);
    }

    private function uploadFile(Request $request): string
    {
        $file = $request->file('file');
        $hashName = Str::random(40) . '.' . $file->extension();
        return $file->storeAs('materials', $hashName, 'public');
    }

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:100',
            'file' => 'required|file|mimes:pdf,docx,pptx|max:102400',
            'rating' => 'nullable|integer|between:0,5',
            'semester' => 'nullable|integer|between:1,6',
            'unit' => 'nullable|integer|between:1,4',
            'user_id' => 'required|exists:users,id'
        ]);
    }
}

