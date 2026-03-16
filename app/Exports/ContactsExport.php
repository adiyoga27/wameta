<?php

namespace App\Exports;

use App\Models\Contact;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ContactsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected $userId;
    protected $isSuperAdmin;
    protected $categoryId;
    protected $search;

    public function __construct($userId, $isSuperAdmin, $categoryId = null, $search = null)
    {
        $this->userId = $userId;
        $this->isSuperAdmin = $isSuperAdmin;
        $this->categoryId = $categoryId;
        $this->search = $search;
    }

    public function query()
    {
        $query = $this->isSuperAdmin
            ? Contact::with(['user', 'category'])
            : Contact::with('category')->where('user_id', $this->userId);

        if ($this->categoryId === 'uncategorized') {
            $query->whereNull('category_id');
        } elseif ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('phone', 'like', "%{$this->search}%");
            });
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama',
            'Nomor HP',
            'Kategori',
            'Ditambahkan Pada'
        ];
    }

    public function map($contact): array
    {
        return [
            $contact->id,
            $contact->name ?: 'Tanpa Nama',
            $contact->phone,
            $contact->category ? $contact->category->name : 'Tanpa Kategori',
            $contact->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
