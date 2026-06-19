<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/** Serves the catalogs (events/operators/fields/variables) that drive the builder UI. */
class MetaController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'events'    => config('whatsupninja.events'),
            'operators' => config('whatsupninja.operators'),
            'fields'    => config('whatsupninja.fields'),
            'variables' => config('whatsupninja.variables'),
        ]);
    }
}
