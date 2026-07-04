@extends('app.layout')

@section('title', $page['title'])

@section('content')
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
    <div class="mb-5">
        <h2 class="text-xl font-bold text-gray-800">{{ $page['title'] }}</h2>
        <p class="text-sm text-gray-500 mt-1">Lengkapi data profil usaha Anda untuk memudahkan pelanggan dan driver menemukan toko Anda.</p>
    </div>

    <form method="POST" action="{{ route('app.umkm.profile.save') }}">
        @csrf

        {{-- Store Name --}}
        <div class="mb-4">
            <label for="store_name" class="block text-sm font-semibold text-gray-700 mb-1">Nama Toko <span class="text-red-500">*</span></label>
            <input type="text" name="store_name" id="store_name" value="{{ old('store_name', $data->store_name ?? '') }}" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" required>
        </div>

        {{-- Description --}}
        <div class="mb-4">
            <label for="description" class="block text-sm font-semibold text-gray-700 mb-1">Deskripsi Toko</label>
            <textarea name="description" id="description" rows="3" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">{{ old('description', $data->description ?? '') }}</textarea>
        </div>

        {{-- Address --}}
        <div class="mb-4">
            <label for="address" class="block text-sm font-semibold text-gray-700 mb-1">Alamat Lengkap <span class="text-red-500">*</span></label>
            <textarea name="address" id="address" rows="2" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" required>{{ old('address', $data->address ?? '') }}</textarea>
        </div>

        {{-- Phone --}}
        <div class="mb-4">
            <label for="phone" class="block text-sm font-semibold text-gray-700 mb-1">Nomor Telepon / WhatsApp</label>
            <input type="text" name="phone" id="phone" value="{{ old('phone', $data->phone ?? '') }}" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
        </div>

        {{-- Category --}}
        <div class="mb-4">
            <label for="category" class="block text-sm font-semibold text-gray-700 mb-1">Kategori Barang <span class="text-red-500">*</span></label>
            <select name="category" id="category" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white" required>
                <option value="" disabled selected>Pilih Kategori</option>
                <option value="ringan" {{ old('category', $data->category ?? '') === 'ringan' ? 'selected' : '' }}>Ringan (Makanan, Camilan, Dokumen)</option>
                <option value="sedang" {{ old('category', $data->category ?? '') === 'sedang' ? 'selected' : '' }}>Sedang (Pakaian, Paket Sedang)</option>
                <option value="besar" {{ old('category', $data->category ?? '') === 'besar' ? 'selected' : '' }}>Besar (Barang Elektronik, Sembako Berat)</option>
            </select>
        </div>

        {{-- Open Status --}}
        <div class="mb-4">
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-700">Status Toko</span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_open" value="1" class="sr-only peer" {{ old('is_open', $data->is_open ?? false) ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                    <span class="ml-2 text-sm font-medium text-gray-700">Buka Toko</span>
                </label>
            </div>
        </div>

        {{-- Coordinates --}}
        <div class="grid grid-cols-2 gap-3 mb-6">
            <div>
                <label for="latitude" class="block text-sm font-semibold text-gray-700 mb-1">Latitude <span class="text-red-500">*</span></label>
                <input type="number" step="any" name="latitude" id="latitude" value="{{ old('latitude', $data->latitude ?? '') }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" required>
            </div>
            <div>
                <label for="longitude" class="block text-sm font-semibold text-gray-700 mb-1">Longitude <span class="text-red-500">*</span></label>
                <input type="number" step="any" name="longitude" id="longitude" value="{{ old('longitude', $data->longitude ?? '') }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" required>
            </div>
        </div>

        {{-- Submit --}}
        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 px-4 rounded-lg text-sm transition-colors">
            Simpan Profil
        </button>
    </form>
</div>
@endsection
