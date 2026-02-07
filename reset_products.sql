-- =====================================================
-- SCRIPT RESET SEMUA DATA PRODUK DAN TRANSAKSI TERKAIT
-- =====================================================
-- PERINGATAN: Script ini akan menghapus SEMUA data produk
-- dan transaksi yang berhubungan (penjualan, pembelian, stock, dll)
-- Gunakan hanya untuk data dummy/testing!
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Hapus data penjualan (sales)
DELETE FROM sale_items;
DELETE FROM sale_payments;
DELETE FROM sales;

-- 2. Hapus data pembelian (purchases)
DELETE FROM purchase_items;
DELETE FROM purchase_payments;
DELETE FROM purchases;

-- 3. Hapus data stock dan mutasi
DELETE FROM stock_mutations;
DELETE FROM stocks;

-- 4. Hapus data BOM (Bill of Materials)
DELETE FROM bom_details;
DELETE FROM bom_headers;

-- 5. Hapus data transfer stock
DELETE FROM stock_transfer_items;
DELETE FROM stock_transfers;

-- 6. Hapus data produk
DELETE FROM products;

-- 7. Reset auto increment (opsional, untuk ID mulai dari 1 lagi)
ALTER TABLE sale_items AUTO_INCREMENT = 1;
ALTER TABLE sale_payments AUTO_INCREMENT = 1;
ALTER TABLE sales AUTO_INCREMENT = 1;
ALTER TABLE purchase_items AUTO_INCREMENT = 1;
ALTER TABLE purchase_payments AUTO_INCREMENT = 1;
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
