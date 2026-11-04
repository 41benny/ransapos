<?php

return [
    // Klasifikasi laporan saja; tidak mengubah pilihan/form kasir atau akun historis.
    'operating_coa_codes' => ['6-135', '6-139', '6-120'],
    'operating_name_patterns' => [
        '%lainnya%', '%kebersihan%', '%habis pakai%', '%pln%', '%listrik%', '%pulsa%',
    ],

    // Pembayaran belanja stok tidak boleh dibebankan lagi di samping HPP penjualan.
    'stock_coa_codes' => ['1-145'],
    'stock_name_patterns' => ['%stok%', '%persediaan%', '%bahan baku%', '%barang dagangan%'],

    // Isi kode kategori di sini jika biayanya sudah termasuk harga pokok resep/produk.
    // Pengeluaran kategori tersebut tetap biaya kas; jangan ditambahkan ke HPP lagi.
    'included_in_product_cost_coa_codes' => [],
];
