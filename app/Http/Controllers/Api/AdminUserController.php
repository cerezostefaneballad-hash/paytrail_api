<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index()
    {
        return Admin::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|unique:admins|min:3|max:50',
            'full_name' => 'required|string|max:100',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
                'not_in:password,123456,admin,password123,admin123,letmein,qwerty,abc123',
            ],
        ], [
            'password.regex' => 'Password must contain uppercase, lowercase, number, and special character (@$!%*#?&)',
            'password.not_in' => 'Password is too weak. Choose a stronger password.',
            'password.min' => 'Password must be at least 8 characters.',
        ]);

        $data['password'] = Hash::make($data['password']);
        return Admin::create($data);
    }

    public function update(Request $request, Admin $admin)
    {
        $data = $request->validate([
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('admins')->ignore($admin->id),
            ],
            'full_name' => 'required|string|max:100',
            'password' => [
                'nullable',
                'string',
                'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
                'not_in:password,123456,admin,password123,admin123,letmein,qwerty,abc123',
            ],
        ], [
            'password.regex' => 'Password must contain uppercase, lowercase, number, and special character (@$!%*#?&)',
            'password.not_in' => 'Password is too weak. Choose a stronger password.',
            'password.min' => 'Password must be at least 8 characters.',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $admin->update($data);
        return $admin->fresh();
    }

    public function destroy(Admin $admin)
    {
        if (Admin::count() <= 1) {
            return response()->json(['message' => 'Cannot delete the only admin account'], 422);
        }
        
        $admin->delete();
        return response()->json(['message' => 'Deleted']);
    }
}