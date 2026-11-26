<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    // Show the profile page
    public function show()
    {
        return view('profile');
    }

    // Save the user's own name, username and (optionally) password
    public function update(Request $request)
    {
        $user = User::findOrFail($request->user()->id);

        $data = $request->validate([
            'name'             => ['required', 'string', 'max:100'],
            'username'         => ['required', 'alpha_dash', 'max:30', Rule::unique('users', 'username')->ignore($user->id)],
            'current_password' => ['required_with:password', 'nullable', 'current_password'],
            'password'         => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name     = $data['name'];
        $user->username = $data['username'];

        // Change the password only if a new one was typed
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return back()->with('status', 'Your account was updated.');
    }
}