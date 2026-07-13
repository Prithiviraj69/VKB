<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkerDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    // GET /api/documents
    public function index(Request $request)
    {
        $worker = $request->user()->worker;

        if (!$worker) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $documents = $worker->documents()->latest('uploaded_at')->get();
        return response()->json(['success' => true, 'data' => $documents]);
    }

    // POST /api/documents
    public function store(Request $request)
    {
        $worker = $request->user()->worker;

        if (!$worker) {
            return response()->json(['success' => false, 'message' => 'Complete your profile first'], 422);
        }

        $request->validate([
            'doc_type'   => 'required|string',
            'doc_number' => 'nullable|string',
            'file'       => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $path    = $request->file('file')->store('documents/' . $worker->id, 'public');
        $fileUrl = Storage::url($path);

        $document = WorkerDocument::create([
            'worker_id'   => $worker->id,
            'doc_type'    => $request->doc_type,
            'doc_number'  => $request->doc_number,
            'file_url'    => $fileUrl,
            'uploaded_at' => now(),
            'is_verified' => false,
        ]);

        return response()->json(['success' => true, 'message' => 'Document uploaded', 'data' => $document], 201);
    }

    // DELETE /api/documents/{id}
    public function destroy(Request $request, $id)
    {
        $worker   = $request->user()->worker;
        $document = WorkerDocument::where('worker_id', $worker?->id)->findOrFail($id);
        $document->delete();

        return response()->json(['success' => true, 'message' => 'Document deleted']);
    }
}
