<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class FileUploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('image');
        $path = 'uploads/' . date('Y/m/d');
        $filename = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();

        $stored = Storage::disk('s3')->put($path . '/' . $filename, file_get_contents($file->getRealPath()), 'public');

        $fullPath = $path . '/' . $filename;
        $url = Storage::disk('s3')->url($fullPath);

        return response()->json([
            'success' => true,
            'path' => $fullPath,
            'url' => $url,
        ], 201);
    }
}
