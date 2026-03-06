<?php

namespace App\Imports;

use App\Models\Contact;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ContactsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $phone = $row['phone'] ?? $row['nomor'] ?? $row['no_hp'] ?? $row['telephone'] ?? $row['telepon'] ?? null;
        $name = $row['name'] ?? $row['nama'] ?? null;

        if (!$phone) return null;

        // Clean phone number
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        if (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        return new Contact([
            'user_id' => Auth::id(),
            'phone' => $phone,
            'name' => $name,
        ]);
    }
}
