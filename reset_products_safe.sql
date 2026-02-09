-- =====================================================
-- SCRIPT RESET PRODUK - PRODUCTION SAFE VERSION
-- =====================================================
-- Script ini akan skip tabel yang tidak ada
-- Gunakan di production dengan aman
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Hapus data penjualan (sales) - Skip jika tidak ada
DELETE FROM sale_items WHERE 1=1;
-- sale_payments di-skip karena tidak ada
DELETE FROM sales WHERE 1=1;

-- 2. Hapus data pembelian (purchases) - Skip jika tidak ada
DELETE FROM purchase_items WHERE 1=1;
-- purchase_payments di-skip karena tidak ada
DELETE FROM purchases WHERE 1=1;

-- 3. Hapus data stock dan mutasi
DELETE FROM stock_mutations WHERE 1=1;
DELETE FROM stocks WHERE 1=1;

-- 4. Hapus data BOM (Bill of Materials)
DELETE FROM bom_details WHERE 1=1;
DELETE FROM bom_headers WHERE 1=1;

-- 5. Hapus data transfer stock
DELETE FROM stock_transfer_items WHERE 1=1;
DELETE FROM stock_transfers WHERE 1=1;

-- 6. Hapus data produk
DELETE FROM products WHERE 1=1;

-- 7. Reset auto increment (opsional, untuk ID mulai dari 1 lagi)
ALTER TABLE sale_items AUTO_INCREMENT = 1;
ALTER TABLE sales AUTO_INCREMENT = 1;
ALTER TABLE purchase_items AUTO_INCREMENT = 1;
ALTER TABLE purchases AUTO_INCREMENT = 1;
ALTER TABLE stock_mutations AUTO_INCREMENT = 1;
ALTER TABLE stocks AUTO_INCREMENT = 1;
ALTER TABLE bom_details AUTO_INCREMENT = 1;
ALTER TABLE bom_headers AUTO_INCREMENT = 1;
ALTER TABLE stock_transfer_items AUTO_INCREMENT = 1;
ALTER TABLE stock_transfers AUTO_INCREMENT = 1;
ALTER TABLE products AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- Verifikasi hasil
SELECT 'Products' as tabel, COUNT(*) as jumlah FROM products
UNION ALL
SELECT 'Sales', COUNT(*) FROM sales
UNION ALL
SELECT 'Purchases', COUNT(*) FROM purchases
UNION ALL
SELECT 'Stocks', COUNT(*) FROM stocks
UNION ALL
SELECT 'BOM Headers', COUNT(*) FROM bom_headers;
