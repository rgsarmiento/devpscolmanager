<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Distributor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('distributor')->orderBy('name')->get();
        $distributors = Distributor::orderBy('name')->get();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'distributors' => $distributors,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'distributor_id' => 'nullable|exists:distributors,id',
        ]);

        $role = empty($validated['distributor_id']) ? 'admin' : 'distributor';

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'distributor_id' => $validated['distributor_id'] ?: null,
            'role' => $role,
        ]);

        return back()->with('flash.banner', 'Usuario creado exitosamente.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'distributor_id' => 'nullable|exists:distributors,id',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->distributor_id = $validated['distributor_id'] ?: null;
        $user->role = empty($validated['distributor_id']) ? 'admin' : 'distributor';
        
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('flash.banner', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('flash.bannerStyle', 'danger')->with('flash.banner', 'No puedes eliminar tu propio usuario.');
        }

        $user->delete();

        return back()->with('flash.banner', 'Usuario eliminado exitosamente.');
    }
}
