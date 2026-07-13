<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\State;
use App\Models\District;
use App\Models\Trade;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    // GET /api/states
    public function states()
    {
        $states = State::where('is_active', true)->orderBy('name')->get();
        return response()->json(['success' => true, 'data' => $states]);
    }

    // GET /api/districts/{state_id}
    public function districts($stateId)
    {
        $districts = District::where('state_id', $stateId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        return response()->json(['success' => true, 'data' => $districts]);
    }

    // GET /api/trades
    public function trades()
    {
        $trades = Trade::where('is_active', true)->orderBy('name')->get();
        return response()->json(['success' => true, 'data' => $trades]);
    }
}
