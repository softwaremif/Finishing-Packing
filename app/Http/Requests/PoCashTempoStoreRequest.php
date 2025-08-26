<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PoCashTempoStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'nobukti' => 'nullable|string|max:50',
            'noinv' => 'required|string|max:50',
            'tglinv' => 'required|date',
            'abpk' => 'required|exists:ab,abpk', // asumsi tabel terms
            'curpk' => 'required|exists:cur,curpk', // asumsi tabel currencies
            'suppk' => 'required|exists:sup,suppk', // asumsi tabel suppliers
            'kelpk' => 'required|exists:kel,kelpk', // asumsi tabel jenis
            'mifpk' => 'required|exists:mif,mifpk', // asumsi tabel untuk
            'totbeli' => 'nullable|numeric|min:0',
            'details' => 'required|array|min:1', // Minimal 1 detail
            'details.*.brgnm' => 'required|string|max:255',
            'details.*.hrgbeli' => 'required|numeric|min:0',
            'details.*.jmlbeli' => 'required|numeric|min:0',
            'details.*.unit' => 'required|string|max:50',
        ];
    }

    public function messages()
    {
        return [
            'nobukti.required' => 'Nomor bukti wajib diisi.',
            'nobukti.string' => 'Nomor bukti harus berupa teks.',
            'nobukti.max' => 'Nomor bukti tidak boleh lebih dari 50 karakter.',

            'noinv.required' => 'Nomor invoice wajib diisi.',
            'noinv.string' => 'Nomor invoice harus berupa teks.',
            'noinv.max' => 'Nomor invoice tidak boleh lebih dari 50 karakter.',

            'tglinv.required' => 'Tanggal invoice wajib diisi.',
            'tglinv.date' => 'Tanggal invoice harus berupa format tanggal yang valid.',

            'abpk.required' => 'Term wajib diisi.',
            'abpk.exists' => 'Term tidak ditemukan dalam data.',

            'curpk.required' => 'mata uang wajib diisi.',
            'curpk.exists' => 'mata uang tidak ditemukan dalam data.',

            'suppk.required' => 'Supplier wajib diisi.',
            'suppk.exists' => 'Supplier tidak ditemukan dalam data.',

            'kelpk.required' => 'Jenis pembelian wajib diisi.',
            'kelpk.exists' => 'Jenis pembelian tidak ditemukan dalam data.',

            'mifpk.required' => 'Untuk/MIF wajib diisi.',
            'mifpk.exists' => 'Data "untuk" tidak ditemukan dalam data.',

            'totbeli.required' => 'Total pembelian wajib diisi.',
            'totbeli.numeric' => 'Total pembelian harus berupa angka.',
            'totbeli.min' => 'Total pembelian tidak boleh kurang dari 0.',

            'details.required' => 'Detail pembelian minimal harus ada satu.',
            'details.array' => 'Format detail pembelian tidak valid.',
            'details.min' => 'Minimal harus ada satu detail pembelian.',

            'details.*.brgnm.required' => 'Nama barang wajib diisi.',
            'details.*.brgnm.string' => 'Nama barang harus berupa teks.',
            'details.*.brgnm.max' => 'Nama barang tidak boleh lebih dari 255 karakter.',

            'details.*.hrgbeli.required' => 'Harga beli barang wajib diisi.',
            'details.*.hrgbeli.numeric' => 'Harga beli harus berupa angka.',
            'details.*.hrgbeli.min' => 'Harga beli tidak boleh kurang dari 0.',

            'details.*.jmlbeli.required' => 'Jumlah beli wajib diisi.',
            'details.*.jmlbeli.numeric' => 'Jumlah beli harus berupa angka.',
            'details.*.jmlbeli.min' => 'Jumlah beli tidak boleh kurang dari 0.',

            'details.*.unit.required' => 'Satuan barang wajib diisi.',
            'details.*.unit.string' => 'Satuan harus berupa teks.',
            'details.*.unit.max' => 'Satuan tidak boleh lebih dari 50 karakter.',
        ];
    }
}
