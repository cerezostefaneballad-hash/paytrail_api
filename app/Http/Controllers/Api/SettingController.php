<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function show() { return Setting::latest()->first(); }

    public function store(Request $r)
    {
        $data = $r->validate([
            'acad_year'     => 'required',
            'semester'      => 'required',
            'semestral_fee' => 'required|numeric',
        ]);
        return Setting::create($data);
    }
}