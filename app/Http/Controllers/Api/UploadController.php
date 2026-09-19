<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function completionPhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,heic', 'max:8192'],
        ]);

        $path = $request->file('photo')->storeAs(
            'completions/' . $request->user()->id,
            Str::uuid() . '.' . $request->file('photo')->extension(),
            'public',
        );

        if ($path === false) {
            return response()->json(['error' => 'upload_failed'], 500);
        }

        return response()->json([
            'url' => $request->getSchemeAndHttpHost(). '/storage/' .$path
        ], 201);
    }
}
