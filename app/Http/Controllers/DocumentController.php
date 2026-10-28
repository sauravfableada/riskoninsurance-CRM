<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource grouped / filterable by customer.
     */
    public function index(Request $request)
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('documents')) {
            if (!\Illuminate\Support\Facades\Schema::hasColumn('documents', 'customer_id')) {
                try {
                    \Illuminate\Support\Facades\Schema::table('documents', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->unsignedBigInteger('customer_id')->nullable()->after('id')->index();
                    });
                } catch (\Throwable $e) {}
            }
        }

        $selectedCustomerId = $request->input('customer_id');
        $search = $request->input('search');

        $customers = Customer::orderBy('name')->get();

        $with = ['user'];
        if (\Illuminate\Support\Facades\Schema::hasColumn('documents', 'customer_id')) {
            $with[] = 'customer';
        }
        $query = Document::with($with);

        if ($selectedCustomerId && \Illuminate\Support\Facades\Schema::hasColumn('documents', 'customer_id')) {
            $query->where('customer_id', $selectedCustomerId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
                if (\Illuminate\Support\Facades\Schema::hasColumn('documents', 'customer_id')) {
                    $q->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%")
                           ->orWhere('phone', 'like', "%{$search}%");
                    });
                }
            });
        }

        $documents = $query->latest()->paginate(15)->withQueryString();

        return view('crm.documents.index', compact('documents', 'customers', 'selectedCustomerId', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        return view('crm.documents.create', compact('customers'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Document $document)
    {
        return redirect()->route('documents.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Document $document)
    {
        $customers = Customer::orderBy('name')->get();
        return view('crm.documents.edit', compact('document', 'customers'));
    }

    /**
     * Download the specified document.
     */
    public function download(Document $document)
    {
        if (Storage::disk('public')->exists($document->file_path)) {
            return Storage::disk('public')->download($document->file_path, $document->title . '.' . $document->file_type);
        }

        return back()->with('error', 'File not found. It may have been deleted from the server.');
    }
}

