-- =====================================================
-- SCRIPT RESET PARTIAL (KEEP 'mie 001' & COMPONENTS)
-- =====================================================
-- Script ini menghapus data TAPI menjaga produk tertentu
-- beserta resep (BOM) dan bahan bakunya.
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Hapus Transaksi (Sales, Purchase, Stock Mutation) - SEMUA BERSIH
DELETE FROM sale_items WHERE 1=1;
DELETE FROM sales WHERE 1=1;
DELETE FROM purchase_items WHERE 1=1;
DELETE FROM purchases WHERE 1=1;
DELETE FROM stock_mutations WHERE 1=1;
DELETE FROM stocks WHERE 1=1;
DELETE FROM stock_transfer_items WHERE 1=1;
DELETE FROM stock_transfers WHERE 1=1;

-- 2. Hapus BOM Header yang BUKAN milik 'mie 001'
-- (Menggunakan subquery aman untuk MySQL)
DELETE FROM bom_headers 
WHERE product_id NOT IN (
    SELECT id FROM (SELECT id FROM products WHERE sku = 'mie 001') AS p
);

-- 3. Hapus BOM Details yang headernya sudah terhapus
DELETE FROM bom_details 
WHERE bom_id NOT IN (
    SELECT id FROM (SELECT id FROM bom_headers) AS h
);

-- 4. Hapus Produk
-- KECUALI 'mie 001'
-- DAN KECUALI produk yang jadi bahan baku (komponen) dari BOM yang tersisa
DELETE FROM products 
WHERE sku != 'mie 001'
AND id NOT IN (
    SELECT component_product_id FROM (SELECT component_product_id FROM bom_details) AS d
);

-- 5. Reset Auto Increment (Hanya untuk tabel yang kosong total)
ALTER TABLE sale_items AUTO_INCREMENT = 1;
ALTER TABLE sales AUTO_INCREMENT = 1;
ALTER TABLE purchase_items AUTO_INCREMENT = 1;
ALTER TABLE purchases AUTO_INCREMENT = 1;
ALTER TABLE stock_mutations AUTO_INCREMENT = 1;
ALTER TABLE stocks AUTO_INCREMENT = 1;
ALTER TABLE stock_transfer_items AUTO_INCREMENT = 1;
ALTER TABLE stock_transfers AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Sisa Produk' as info, sku, name FROM products;
