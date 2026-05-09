<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AccountEntryRequest;
use App\Models\AccountEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AccountEntryController extends Controller
{
    public function index(): View
    {
        $entriesQuery = AccountEntry::query()
            ->latest('paid_at')
            ->latest();

        return view('admin.accounts.index', [
            'entries' => (clone $entriesQuery)->paginate(12),
            'summary' => [
                'entries_count' => (clone $entriesQuery)->count(),
                'total_amount' => (float) (clone $entriesQuery)->sum('amount'),
                'attachments_count' => (clone $entriesQuery)
                    ->where(function ($query): void {
                        $query->whereNotNull('image_path')
                            ->orWhereNotNull('image_paths')
                            ->orWhereNotNull('attachment_path');
                    })
                    ->count(),
            ],
            'panelRole' => 'admin',
        ]);
    }

    public function store(AccountEntryRequest $request): RedirectResponse
    {
        $data = $request->entryData();
        $uploadedImages = collect($request->file('images', []));

        if ($uploadedImages->isEmpty() && $request->hasFile('image')) {
            $uploadedImages = collect([$request->file('image')]);
        }

        $storedImagePaths = $uploadedImages
            ->filter()
            ->map(fn ($image) => $image->store('account-entries/images', 'public'))
            ->values()
            ->all();

        if ($storedImagePaths !== []) {
            $data['image_paths'] = $storedImagePaths;
        }

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('account-entries/files', 'public');
            $data['attachment_name'] = $request->file('attachment')->getClientOriginalName();
        }

        AccountEntry::query()->create($data);

        return redirect()
            ->route('admin.accounts.index')
            ->with('status', app()->isLocale('ar') ? 'تم حفظ قيد الحساب بنجاح.' : 'Account entry saved successfully.');
    }

    public function destroy(AccountEntry $accountEntry): RedirectResponse
    {
        $this->deleteStoredFiles($accountEntry->imagePaths());
        $this->deleteStoredFile($accountEntry->attachment_path);
        $accountEntry->delete();

        return redirect()
            ->route('admin.accounts.index')
            ->with('status', app()->isLocale('ar') ? 'تم حذف قيد الحساب.' : 'Account entry deleted successfully.');
    }

    private function deleteStoredFiles(array $paths): void
    {
        foreach ($paths as $path) {
            $this->deleteStoredFile($path);
        }
    }

    private function deleteStoredFile(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}