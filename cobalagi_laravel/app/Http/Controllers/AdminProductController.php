<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminProductController extends Controller
{
    public function index()
    {
        $products = Product::where('is_deleted', 0)->orderBy('created_at', 'DESC')->get();
        return view('admin.products.index', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'harga' => 'required|numeric',
            'kategori' => 'required|string',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'tersedia' => 'nullable|boolean',
        ]);

        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $filename = time() . '_' . $file->getClientOriginalName();
            // Storing in public relative to project root if possible, or just standard
            $file->move(public_path('assets/images/products'), $filename);
            $validated['gambar'] = $filename;
        }

        $validated['tersedia'] = $request->has('tersedia') ? 1 : 0;
        $product = Product::create($validated);

        ActivityLog::create([
            'admin_user' => Auth::guard('admin')->user()->username,
            'action' => 'Create Product',
            'details' => "Menambah produk baru: {$product->nama}"
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'harga' => 'required|numeric',
            'kategori' => 'required|string',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'tersedia' => 'nullable|boolean',
        ]);

        if ($request->hasFile('gambar')) {
            // Delete old image if exists and it's not a remote URL
            if ($product->gambar && file_exists(public_path('assets/images/products/' . $product->gambar))) {
                unlink(public_path('assets/images/products/' . $product->gambar));
            }
            $file = $request->file('gambar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('assets/images/products'), $filename);
            $validated['gambar'] = $filename;
        }

        $validated['tersedia'] = $request->has('tersedia') ? 1 : 0;
        $product->update($validated);

        ActivityLog::create([
            'admin_user' => Auth::guard('admin')->user()->username,
            'action' => 'Update Product',
            'details' => "Mengubah produk: {$product->nama}"
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diupdate');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->update(['is_deleted' => 1]);

        ActivityLog::create([
            'admin_user' => Auth::guard('admin')->user()->username,
            'action' => 'Delete Product',
            'details' => "Menghapus produk: {$product->nama}"
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil dihapus');
    }
}
