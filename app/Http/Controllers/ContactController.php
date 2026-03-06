<?php

namespace App\Http\Controllers;

use App\Imports\ContactsImport;
use App\Models\Contact;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ContactController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $contacts = $user->isSuperAdmin()
            ? Contact::with('user')->latest()->paginate(50)
            : Contact::where('user_id', $user->id)->latest()->paginate(50);

        return view('contacts.index', compact('contacts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string',
            'name' => 'nullable|string|max:255',
        ]);

        $phone = preg_replace('/[^0-9]/', '', $data['phone']);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        Contact::create([
            'user_id' => auth()->id(),
            'phone' => $phone,
            'name' => $data['name'] ?? null,
        ]);

        return back()->with('success', 'Kontak berhasil ditambahkan!');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new ContactsImport, $request->file('file'));
            return back()->with('success', 'Kontak berhasil diimport dari Excel!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();
        return back()->with('success', 'Kontak berhasil dihapus!');
    }
}
