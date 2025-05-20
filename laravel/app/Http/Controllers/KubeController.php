<?php

// app/Http/Controllers/KubeController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class KubeController extends Controller
{
    public function proxy()
    {
        try {
           $token = trim(env('KUBE_TOKEN'));

            $response = Http::withToken($token)
                ->withoutVerifying()
                ->get('https://172.17.0.1:16443/api');

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to connect to Kubernetes API',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

