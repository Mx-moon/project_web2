<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // List all accounts
    public function index()
    {
        $this->adminOnly();

        $users = User::with('location')->orderBy('name')->get();

        return view('users.index', compact('users'));
    }

    // Show the "Add User" form
    public function create()
    {
        $this->adminOnly();

        $user      = new User(['role' => 'kasikas']);
        $locations = Location::where('active', true)->orderBy('name')->get();

        return view('users.form', compact('user', 'locations'));
    }

    // Save a new account
    public function store(Request $request)
    {
        $this->adminOnly();

        // 1. Check the form
        $data = $request->validate($this->rules($request));

        // 2. Never save the password as typed: scramble it first
        $data['password'] = Hash::make($data['password']);

        // 3. The admin does not belong to one location
        if ($data['role'] === 'admin') {
            $data['location_id'] = null;
        }

        $user = User::create($data);

        return redirect()->route('users.index')->with('status', $user->name.' was added.');
    }

    // Show the "Edit User" form
    public function edit(User $user)
    {
        $this->adminOnly();

        $locations = Location::where('active', true)->orderBy('name')->get();

        return view('users.form', compact('user', 'locations'));
    }

    // Save the changes to an account
    public function update(Request $request, User $user)
    {
        $this->adminOnly();

        // 1. Check the form
        $data = $request->validate($this->rules($request, $user));

        // 2. The admin must not lock themselves out
        if ($user->id === auth()->id() && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'You cannot remove your own admin role.'])->withInput();
        }

        // 3. Change the password only if a new one was typed
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // 4. The admin does not belong to one location
        if ($data['role'] === 'admin') {
            $data['location_id'] = null;
        }

        $user->update($data);

        return redirect()->route('users.index')->with('status', $user->name.' was updated.');
    }

    // Delete an account
    public function destroy(User $user)
    {
        $this->adminOnly();

        // You cannot delete the account you are using
        if ($user->id === auth()->id()) {
            return back()->withErrors(['delete' => 'You cannot delete the account you are logged in with.']);
        }

        // Accounts with order history stay, so old orders keep their records
        $hasOrders    = Order::where('requested_by', $user->id)->exists();
        $hasMovements = StockMovement::where('user_id', $user->id)->exists();

        if ($hasOrders || $hasMovements) {
            return back()->withErrors(['delete' => $user->name.' has order history, so the account cannot be deleted.']);
        }

        // Delete the photo file, then the account
        if ($user->photo) {
            Storage::disk(config('filesystems.photo_disk'))->delete($user->photo);
        }

        $name = $user->name;
        $user->delete();

        return back()->with('status', $name.' was deleted.');
    }


    // The admin accepts a new account request
    public function approve(User $user)
    {
        $this->adminOnly();

        $user->update(['status' => 'active']);

        return back()->with('status', $user->name.' was approved and can now sign in.');
    }

    // The admin turns down a request (the pending account is deleted)
    public function reject(User $user)
    {
        $this->adminOnly();
        abort_unless($user->status === 'pending', 422);

        $name = $user->name;
        $user->delete();

        return back()->with('status', $name.'\'s request was rejected.');
    }

    // The checks for the form, used by both Add and Edit
    private function rules(Request $request, ?User $user = null): array
    {
        // When editing, the person's own username and email are allowed to stay the same
        $userId = null;
        if ($user) {
            $userId = $user->id;
        }

        // A location is required for everyone except the admin
        $locationRules = ['nullable', 'exists:locations,id'];
        if ($request->input('role') !== 'admin') {
            array_unshift($locationRules, 'required');
        }

        // The password is required for a new account, optional when editing
        $passwordRule = 'required';
        if ($user) {
            $passwordRule = 'nullable';
        }

        return [
            'name'        => ['required', 'string', 'max:100'],
            'username'    => ['required', 'alpha_dash', 'max:30', Rule::unique('users', 'username')->ignore($userId)],
            'email'       => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($userId)],
            'role'        => ['required', 'in:admin,finali,kasikas'],
            'location_id' => $locationRules,
            'password'    => [$passwordRule, 'string', 'min:8', 'confirmed'],
        ];
    }

    // Only the admin may use the Users pages
    private function adminOnly(): void
    {
        abort_unless(request()->user()->role === 'admin', 403);
    }
}