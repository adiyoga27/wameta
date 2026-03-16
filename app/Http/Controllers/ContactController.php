<?php

namespace App\Http\Controllers;

use App\Imports\ContactsImport;
use App\Exports\ContactsExport;
use App\Models\Contact;
use App\Models\ContactCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();

        // Categories for the current user (or all for superadmin)
        $categories = $isSuperAdmin
            ? ContactCategory::withCount('contacts')->latest()->get()
            : ContactCategory::where('user_id', $user->id)->withCount('contacts')->latest()->get();

        // Build contact query with optional category filter
        $query = $isSuperAdmin
            ? Contact::with(['user', 'category'])
            : Contact::with('category')->where('user_id', $user->id);

        $categoryId = $request->input('category_id');
        if ($categoryId === 'uncategorized') {
            $query->whereNull('category_id');
        } elseif ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        // Search
        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $contacts = $query->latest()->paginate(50)->appends($request->query());

        return view('contacts.index', compact('contacts', 'categories', 'categoryId', 'search'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string',
            'name' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:contact_categories,id',
        ]);

        $phone = preg_replace('/[^0-9]/', '', $data['phone']);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        Contact::create([
            'user_id' => auth()->id(),
            'phone' => $phone,
            'name' => $data['name'] ?? null,
            'category_id' => $data['category_id'] ?? null,
        ]);

        return back()->with('success', 'Kontak berhasil ditambahkan!');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
            'category_id' => 'nullable|exists:contact_categories,id',
        ]);

        try {
            $categoryId = $request->input('category_id');
            Excel::import(new ContactsImport($categoryId), $request->file('file'));
            return back()->with('success', 'Kontak berhasil diimport dari Excel!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $categoryId = $request->input('category_id');
        $search = $request->input('search');

        $fileName = 'kontak';
        if ($categoryId === 'uncategorized') {
            $fileName .= '_tanpa_kategori';
        } elseif ($categoryId) {
            $cat = ContactCategory::find($categoryId);
            if ($cat) $fileName .= '_' . \Str::slug($cat->name);
        }
        if ($search) {
            $fileName .= '_search_' . \Str::slug($search);
        }
        $fileName .= '_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new ContactsExport($user->id, $isSuperAdmin, $categoryId, $search), $fileName);
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();
        return back()->with('success', 'Kontak berhasil dihapus!');
    }

    // ---- Category CRUD ----

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string|max:500',
        ]);

        ContactCategory::create([
            'user_id' => auth()->id(),
            'name' => $data['name'],
            'color' => $data['color'] ?? '#25D366',
            'description' => $data['description'] ?? null,
        ]);

        return back()->with('success', 'Kategori berhasil ditambahkan!');
    }

    public function updateCategory(Request $request, ContactCategory $category)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string|max:500',
        ]);

        $category->update([
            'name' => $data['name'],
            'color' => $data['color'] ?? $category->color,
            'description' => $data['description'] ?? $category->description,
        ]);

        return back()->with('success', 'Kategori berhasil diupdate!');
    }

    public function destroyCategory(ContactCategory $category)
    {
        $category->delete(); // contacts will have category_id set to null (onDelete('set null'))
        return back()->with('success', 'Kategori berhasil dihapus!');
    }

    public function updateContactCategory(Request $request, Contact $contact)
    {
        $data = $request->validate([
            'category_id' => 'nullable|exists:contact_categories,id',
        ]);

        $contact->update(['category_id' => $data['category_id']]);

        return back()->with('success', 'Kategori kontak berhasil diupdate!');
    }
}
