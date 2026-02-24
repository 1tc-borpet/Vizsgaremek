<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of all users (admin only)
     */
    public function index(Request $request)
    {
        try {
            // Admin check
            if (!$request->user() || !$request->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nincs engedélye ehhez a művelethez.',
                ], Response::HTTP_FORBIDDEN);
            }

            $users = User::paginate(15);

            return response()->json([
                'success' => true,
                'data' => $users,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a felhasználók lekérésekor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get user profile
     */
    public function show(Request $request, string $id = null)
    {
        try {
            // Ha nincs ID, akkor a jelenlegi felhasználó profilja
            if ($id === null) {
                $user = $request->user();
                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nincs bejelentkezve.',
                    ], Response::HTTP_UNAUTHORIZED);
                }
            } else {
                // Ellenőrzés: a felhasználó csak saját profilját vagy az admin láthatja
                if ($request->user()->id != $id && !$request->user()->isAdmin()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nincs engedélye ennek a profilnak a megtekintésére.',
                    ], Response::HTTP_FORBIDDEN);
                }
                $user = User::findOrFail($id);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'avatar_url' => $user->avatar_url,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'A felhasználó nem található.',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a profil lekérésekor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update user profile
     */
    public function update(Request $request, string $id)
    {
        try {
            // Ellenőrzés: a felhasználó csak saját profilját vagy az admin módosíthatja
            if ($request->user()->id != $id && !$request->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nincs engedélye ennek a profilnak a módosítására.',
                ], Response::HTTP_FORBIDDEN);
            }

            $user = User::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'phone' => 'nullable|string|unique:users,phone,' . $id . '|regex:/^[0-9\+\-\(\)\s]+$/',
                'avatar_url' => 'nullable|url',
            ]);

            // Admin csak az admin változtathat role-t és is_active-et
            if ($request->user()->isAdmin()) {
                $adminValidated = $request->validate([
                    'role' => 'sometimes|in:user,admin,restaurant_owner',
                    'is_active' => 'sometimes|boolean',
                ]);
                $validated = array_merge($validated, $adminValidated);
            }

            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Profil sikeresen frissítve.',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'avatar_url' => $user->avatar_url,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'A felhasználó nem található.',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validációs hiba.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a profil frissítésekor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete user (admin only)
     */
    public function destroy(string $id, Request $request)
    {
        try {
            // Admin check
            if (!$request->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nincs engedélye ennek a művelethez.',
                ], Response::HTTP_FORBIDDEN);
            }

            // Ellenőrzés: nem tudja magát törölni
            if ($request->user()->id == $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nem tudod magad törölni.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $user = User::findOrFail($id);
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Felhasználó sikeresen törölve.',
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'A felhasználó nem található.',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a felhasználó törléskor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Deactivate user (admin only)
     */
    public function deactivate(Request $request, string $id)
    {
        try {
            // Admin check
            if (!$request->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nincs engedélye ennek a művelethez.',
                ], Response::HTTP_FORBIDDEN);
            }

            $user = User::findOrFail($id);

            $user->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Felhasználó sikeresen deaktiválva.',
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'A felhasználó nem található.',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a deaktiváláskor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Activate user (admin only)
     */
    public function activate(Request $request, string $id)
    {
        try {
            // Admin check
            if (!$request->user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nincs engedélye ennek a művelethez.',
                ], Response::HTTP_FORBIDDEN);
            }

            $user = User::findOrFail($id);

            $user->update(['is_active' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Felhasználó sikeresen aktiválva.',
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'A felhasználó nem található.',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba az aktiváláskor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
