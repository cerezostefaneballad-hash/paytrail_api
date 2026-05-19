<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index() { return Admin::all(); }

    public function store(Request $r)
    {
        $data = $r->validate([
            'username'  => 'required|unique:admins',
            'full_name' => 'required',
            'password'  => 'required|min:6',
        ]);
        $data['password'] = Hash::make($data['password']);
        return Admin::create($data);
    }

    public function update(Request $r, Admin $admin)
    {
        $data = $r->validate([
            'username'  => 'required|unique:admins,username,' . $admin->id,
            'full_name' => 'required',
            'password'  => 'nullable|min:6',
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
        $admin->delete();
        return response()->json(['message' => 'Deleted']);
    }
}