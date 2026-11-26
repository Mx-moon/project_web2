<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Check the username and password, then log the person in
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // A correct password but not approved yet: tell them to wait
        $waiting = User::where('username', $credentials['username'])->where('status', 'pending')->first();
        if ($waiting && Hash::check($credentials['password'], $waiting->password)) {
            return back()->withErrors(['username' => 'Your account is still waiting for the admin to approve it.'])->onlyInput('username');
        }

        // Only approved (active) accounts can log in
        $worked = Auth::attempt($credentials + ['status' => 'active']);

        if (! $worked) {
            return back()->withErrors(['username' => 'Wrong username or password.'])->onlyInput('username');
        }

        $request->session()->regenerate();

        return redirect()->route('overview');
    }

    // Show the "Request an account" form
    public function showRegister()
    {
        $locations = Location::where('active', true)->orderBy('type')->orderBy('name')->get();

        return view('register', compact('locations'));
    }

    // Save the request. The account stays "pending" until the admin approves it.
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'username'    => ['required', 'alpha_dash', 'max:30', 'unique:users,username'],
            'email'       => ['required', 'email', 'max:100', 'unique:users,email'],
            'role'        => ['required', 'in:finali,kasikas'],
            'location_id' => ['required', 'exists:locations,id'],
            'password'    => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Kasikas works in a showroom, FiNALi works in a warehouse
        $location = Location::find($data['location_id']);
        $needs    = $data['role'] === 'kasikas' ? 'showroom' : 'warehouse';

        if ($location->type !== $needs) {
            return back()->withErrors(['location_id' => 'Kasikas staff need a showroom and FiNALi staff need a warehouse.'])->withInput();
        }

        $data['password'] = Hash::make($data['password']);
        $data['status']   = 'pending';

        User::create($data);

        return redirect()->route('login')->with('status', 'Request sent! You can sign in after the admin approves your account.');
    }

    // Log the person out
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}