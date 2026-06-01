-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versi server:                 8.3.0 - MySQL Community Server - GPL
-- OS Server:                    Win64
-- HeidiSQL Versi:               12.11.0.7065
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Membuang struktur basisdata untuk tele_store_cheat
CREATE DATABASE IF NOT EXISTS `tele_store_cheat` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `tele_store_cheat`;

-- membuang struktur untuk table tele_store_cheat.cache
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Membuang data untuk tabel tele_store_cheat.cache: ~0 rows (lebih kurang)
DELETE FROM `cache`;

-- membuang struktur untuk table tele_store_cheat.cache_locks
CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Membuang data untuk tabel tele_store_cheat.cache_locks: ~0 rows (lebih kurang)
DELETE FROM `cache_locks`;

-- membuang struktur untuk table tele_store_cheat.configs
CREATE TABLE IF NOT EXISTS `configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order` json DEFAULT NULL,
  `bot` json DEFAULT NULL,
  `captions` json DEFAULT NULL,
  `payments` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Membuang data untuk tabel tele_store_cheat.configs: ~1 rows (lebih kurang)
DELETE FROM `configs`;
INSERT INTO `configs` (`id`, `order`, `bot`, `captions`, `payments`, `created_at`, `updated_at`) VALUES
	(1, '{"string": "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ", "exp_order": 60, "prefix_order": "KM-", "count_pending": 4, "transaksi_delay": 2, "length_random_order": 12}', '{"image": "https://dgstoreid.xyz/assets/images/logo/1757737759.png", "contact": "https://t.me/akiracode4"}', '{"orders": [{"key": "menu_start", "content": "hello {firstname}"}, {"key": "menu_order", "content": "🎮 Daftar Game - CHEAT GAME\\n\\nPilih game di bawah ini untuk melihat produk yang tersedia."}, {"key": "menu_providers", "content": "Produk untuk {game}\\n\\n🔥 : Unggulan | 🎟️ : Bisa Pakai Bonus\\n\\nPilih produk di bawah ini untuk melihat detail harga."}, {"key": "menu_denoms", "content": "📦 Produk: {produk}\\n\\nSilakan pilih masa aktif yang diinginkan:"}, {"key": "menu_confirm_order", "content": "🛒 Konfirmasi Pembelian\\n\\nProduk: {produk} ({denom})\\nHarga: {price}\\n────────────────────\\n💰Silakan pilih metode pembayaran:"}, {"key": "invoice_order", "content": "<b>INVOICE PESANAN</b>\\n\\n<b>ID Invoice:</b> <code>{invoice_id}</code>\\n<b>Status Pembayaran:</b> {status_pembayaran}\\n<b>Status Proses:</b> {status_proses}\\n<b>Berlaku Sampai:</b> {expired_at}\\n\\n<b>Detail Produk</b>\\n🎮 Game: {game}\\n🏢 Provider: {provider}\\n💎 Denom: {denom}\\n💰 Harga: {price}\\n\\n<b>Metode Pembayaran</b>\\n💳 Payment: {payment}\\n👤 Atas Nama: {name_account}\\n🔢 Nomor Rekening: <code>{number_account}</code>\\n🏦 Virtual Account: <code>{virtual_account}</code>\\n💼 Nomor Pembayaran: <code>{nomor_pembayaran}</code>\\n\\n<b>Instruksi</b>\\n{instruksi}"}, {"key": "invoice", "content": "<b>✅ PEMBELIAN BERHASIL</b>\\n\\n🧾 <b>ID Invoice:</b> <code>{invoice_id}</code>\\n🎮 <b>Game:</b> {game}\\n🏢 <b>Provider:</b> {provider}\\n💎 <b>Masa Aktif:</b> {denom}\\n💰 <b>Harga:</b> {price}\\n\\n🔑 <b>Keterangan / Lisensi:</b>\\n{notes}\\n\\n📖 <b>Tutorial:</b>\\n{tutorial}"}, {"key": "cancel_order", "content": "Berhasil membatalkan transaksi dengan invoice id : {invoice_id}"}, {"key": "confirm_cancel_order", "content": "⚠️ <b>Konfirmasi Pembatalan</b>\\n\\nKamu akan membatalkan pesanan berikut:\\n\\n🧾 <b>Invoice:</b> <code>{invoice_id}</code>\\n🎮 <b>Game:</b> {game}\\n💎 <b>Denom:</b> {denom}\\n💰 <b>Total:</b> {price}\\n\\n<i>Pesanan yang dibatalkan tidak dapat dikembalikan.</i>\\n\\nApakah kamu yakin ingin membatalkan pesanan ini?"}], "invoice": "<b>✅ PEMBELIAN BERHASIL</b>\\n\\n🧾 <b>ID Invoice:</b> <code>{invoice_id}</code>\\n🎮 <b>Game:</b> {game}\\n🏢 <b>Provider:</b> {provider}\\n💎 <b>Masa Aktif:</b> {denom}\\n💰 <b>Harga:</b> {price}\\n\\n🔑 <b>Keterangan / Lisensi:</b>\\n{notes}\\n\\n📖 <b>Tutorial:</b>\\n{tutorial}", "others_button": [{"key": "menu_history", "content": "<b>Riwayat Transaksi (Halaman {page}/{total_pages})</b>\\n\\n{list_transactions}\\n\\n<i>Gunakan perintah berikut untuk melihat detail:</i>\\n<code>/cek_invoice INVOICE_ID</code>"}, {"key": "menu_history_empty", "content": "<b>Riwayat Transaksi</b>\\n\\nKamu belum memiliki riwayat transaksi.\\n\\nKirimkan ID Invoice Anda untuk mencari detail transaksi secara langsung."}, {"key": "menu_account", "content": "<b>Informasi Akun</b>\\n\\n<b>ID User:</b> <code>{user_id}</code>\\n<b>Nama:</b> {name}\\n<b>Username:</b> {username}\\n<b>Role:</b>{role}\\n<b>Terdaftar:</b> {registered_at}"}, {"key": "menu_leaderboard_weekly", "content": "<b>LEADERBOARD MINGGUAN</b>\\n <i>7 Hari Terakhir</i>\\n\\n{list_rank}"}, {"key": "menu_leaderboard_monthly", "content": "<b>LEADERBOARD BULANAN</b>\\n<i>Awal s/d Akhir Bulan</i>\\n\\n{list_rank}"}, {"key": "menu_announcement", "content": "🎁 BONUS DEPOSIT 8%\\nIsi saldo Anda sekarang dan raih bonus 8% , langsung buka menu akun saya untuk deposit saldo"}, {"key": "menu_resetlicense", "content": "🔑 <b>Reset Kunci Lisensi</b>\\n\\nFitur ini untuk mereset kunci lisensi Anda agar dapat digunakan di perangkat baru.\\n\\nSilakan pilih produk yang sesuai:"}, {"key": "menu_select_resetlicense", "content": "Anda telah memilih <b>{provider}</b>.\\n\\n✍️ Silakan kirimkan Kunci Lisensi Anda di chat ini untuk melanjutkan proses reset."}]}', '{"pakasir": {"status": true, "api_key": "RfhgPdCzNfbDLGysdJXMYQ5d9T3wlN67", "project": "kiimodsbot"}, "wijayapay": {"status": true, "api_key": "7c61a92d37bf1ae98d6453520a", "code_merchant": "WP6931ab3de7ad9"}}', '2026-05-28 15:18:57', '2026-06-01 05:51:57');

-- membuang struktur untuk table tele_store_cheat.denoms
CREATE TABLE IF NOT EXISTS `denoms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `provider_id` int NOT NULL DEFAULT '0',
  `game_id` int NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` int NOT NULL DEFAULT '0',
  `duration` int NOT NULL DEFAULT '0',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Membuang data untuk tabel tele_store_cheat.denoms: ~0 rows (lebih kurang)
DELETE FROM `denoms`;
INSERT INTO `denoms` (`id`, `provider_id`, `game_id`, `name`, `price`, `duration`, `status`, `created_at`, `updated_at`) VALUES
	(1, 1, 1, '1 Hari', 500, 1, 'active', '2026-05-28 09:38:43', '2026-05-28 09:38:44'),
	(2, 1, 1, '3 Hari', 30000, 3, 'active', '2026-05-28 09:38:43', '2026-05-28 09:38:44'),
	(3, 1, 1, '7 Hari', 50000, 7, 'active', '2026-05-31 09:44:23', '2026-05-31 09:44:23');

-- membuang struktur untuk table tele_store_cheat.downloads
CREATE TABLE IF NOT EXISTS `downloads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int NOT NULL DEFAULT '0',
  `provider_id` int NOT NULL DEFAULT '0',
  `data` json DEFAULT NULL,
  `tutorial` longtext COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Membuang data untuk tabel tele_store_cheat.downloads: ~0 rows (lebih kurang)
DELETE FROM `downloads`;
INSERT INTO `downloads` (`id`, `game_id`, `provider_id`, `data`, `tutorial`, `status`, `created_at`, `updated_at`) VALUES
	(1, 1, 1, '[{"link": "https://drive.google.com/drive/folders/1tIzlH98db-rAYrUbzEfclS0rKIsOvx_c", "title": "Download File via GDrive"}, {"link": "https://t.me/+GDh9MvWu5sU4ZmU1", "title": "Download File via Telegram"}]', NULL, 'active', '2026-06-01 05:04:18', '2026-06-01 05:04:19');

-- membuang struktur untuk table tele_store_cheat.failed_jobs
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Membuang data untuk tabel tele_store_cheat.failed_jobs: ~0 rows (lebih kurang)
DELETE FROM `failed_jobs`;

-- membuang struktur untuk table tele_store_cheat.games
CREATE TABLE IF NOT EXISTS `games` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `providers` json DEFAULT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Membuang data untuk tabel tele_store_cheat.games: ~1 rows (lebih kurang)
DELETE FROM `games`;
INSERT INTO `games` (`id`, `providers`, `code`, `name`, `status`, `created_at`, `updated_at`) VALUES
	(1, '[1, 2]', 'MLBB', 'Mobile Legends', 'active', '2026-05-28 15:22:23', '2026-06-01 05:54:50'),
	(2, '[1]', 'CODM', 'Call Of Duty Mobile', 'active', '2026-06-01 07:33:09', '2026-06-01 07:37:26');

-- membuang struktur untuk table tele_store_cheat.histories
CREATE TABLE IF NOT EXISTS `histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `temporary_data` json DEFAULT NULL,
  `invoice_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product` json DEFAULT NULL,
  `payment` json DEFAULT NULL,
  `price` int NOT NULL DEFAULT '0',
  `notes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payment_status` enum('pending','paid','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `process_status` enum('pending','paid','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `expire_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Membuang data untuk tabel tele_store_cheat.histories: ~10 rows (lebih kurang)
DELETE FROM `histories`;
INSERT INTO `histories` (`id`, `user_id`, `temporary_data`, `invoice_id`, `product`, `payment`, `price`, `notes`, `payment_status`, `process_status`, `expire_at`, `paid_at`, `created_at`, `updated_at`) VALUES
	(1, 1, '{"userId": "5625335707", "last_name": null, "first_name": "ZX • Akira"}', 'KM-P6EWKD7P5FMM', '{"game": {"id": 1, "name": "Mobile Legends"}, "denom": {"id": 1, "name": "1 Hari"}, "provider": {"id": 1, "name": "GXFiles"}}', '{"name": "QRIS", "detail": {"url": null, "type": "manual", "qr_url": null, "instruksi": "1. Buka aplikasi yang mendukung pembayaran dengan QRIS\\r\\n2. Pilih fitur QRIS / Bayar\\r\\n3. Pindai kode QR yang diberikan oleh Merchant\\\\r\\\\nPastikan tagihan yang ditagihkan sesuai\\r\\n4. Klik tombol Konfirmasi\\r\\n5. Masukkan PIN untuk menyelesaikan pembayaran\\r\\n6. Setelah pembayaran berhasil, kamu akan dialihkan ke Halaman Hasil Pembayaran", "name_account": "POWERED BY WIJAYAPAY", "number_account": "POWERED BY WIJAYAPAY", "virtual_account": null, "nomor_pembayaran": null}, "fee_fixed": 100, "fee_percent": 3.5}', 604, NULL, 'paid', 'paid', '2026-05-30 18:56:41', NULL, '2026-05-30 10:56:41', '2026-05-30 10:56:41'),
	(2, 1, '{"userId": "5625335707", "last_name": null, "first_name": "ZX • Akira"}', 'KM-8FNI8GACAW64', '{"game": {"id": 1, "name": "Mobile Legends"}, "denom": {"id": 1, "name": "1 Hari"}, "provider": {"id": 1, "name": "GXFiles"}}', '{"name": "QRIS", "detail": {"url": null, "type": "manual", "qr_url": null, "instruksi": "1. Buka aplikasi yang mendukung pembayaran dengan QRIS\\r\\n2. Pilih fitur QRIS / Bayar\\r\\n3. Pindai kode QR yang diberikan oleh Merchant\\\\r\\\\nPastikan tagihan yang ditagihkan sesuai\\r\\n4. Klik tombol Konfirmasi\\r\\n5. Masukkan PIN untuk menyelesaikan pembayaran\\r\\n6. Setelah pembayaran berhasil, kamu akan dialihkan ke Halaman Hasil Pembayaran", "name_account": "POWERED BY WIJAYAPAY", "number_account": "POWERED BY WIJAYAPAY", "virtual_account": null, "nomor_pembayaran": null}, "fee_fixed": 100, "fee_percent": 3.5}', 604, NULL, 'pending', 'pending', '2026-05-31 00:15:07', NULL, '2026-05-30 16:15:07', '2026-05-30 16:15:07'),
	(3, 1, '{"userId": "5625335707", "last_name": null, "first_name": "ZX • Akira"}', 'KM-8FNI8GACAW64', '{"game": {"id": 1, "name": "Mobile Legends"}, "denom": {"id": 1, "name": "1 Hari"}, "provider": {"id": 1, "name": "GXFiles"}}', '{"name": "QRIS", "detail": {"url": null, "type": "manual", "qr_url": null, "instruksi": "1. Buka aplikasi yang mendukung pembayaran dengan QRIS\\r\\n2. Pilih fitur QRIS / Bayar\\r\\n3. Pindai kode QR yang diberikan oleh Merchant\\\\r\\\\nPastikan tagihan yang ditagihkan sesuai\\r\\n4. Klik tombol Konfirmasi\\r\\n5. Masukkan PIN untuk menyelesaikan pembayaran\\r\\n6. Setelah pembayaran berhasil, kamu akan dialihkan ke Halaman Hasil Pembayaran", "name_account": "POWERED BY WIJAYAPAY", "number_account": "POWERED BY WIJAYAPAY", "virtual_account": null, "nomor_pembayaran": null}, "fee_fixed": 100, "fee_percent": 3.5}', 604, NULL, 'pending', 'pending', '2026-05-31 00:15:07', NULL, '2026-05-30 16:15:07', '2026-05-30 16:15:07'),
	(4, 1, '{"userId": "5625335707", "last_name": null, "first_name": "ZX • Akira"}', 'KM-OZXZKWKX83LU', '{"game": {"id": 1, "name": "Mobile Legends"}, "denom": {"id": 1, "name": "1 Hari"}, "provider": {"id": 1, "name": "GXFiles"}}', '{"name": "QRIS", "detail": {"url": null, "type": "manual", "qr_url": null, "instruksi": "1. Buka aplikasi yang mendukung pembayaran dengan QRIS\\r\\n2. Pilih fitur QRIS / Bayar\\r\\n3. Pindai kode QR yang diberikan oleh Merchant\\\\r\\\\nPastikan tagihan yang ditagihkan sesuai\\r\\n4. Klik tombol Konfirmasi\\r\\n5. Masukkan PIN untuk menyelesaikan pembayaran\\r\\n6. Setelah pembayaran berhasil, kamu akan dialihkan ke Halaman Hasil Pembayaran", "name_account": "POWERED BY WIJAYAPAY", "number_account": "POWERED BY WIJAYAPAY", "virtual_account": null, "nomor_pembayaran": null}, "fee_fixed": 100, "fee_percent": 3.5}', 604, NULL, 'pending', 'pending', '2026-05-31 00:26:33', NULL, '2026-05-30 16:26:33', '2026-05-30 16:26:33'),
	(5, 3, '{"userId": "8275836687", "last_name": null, "first_name": "𝐊𝐈𝐈𝐌𝐎𝐃𝐒"}', 'KM-QSQA5NUJJE65', '{"game": {"id": 1, "name": "Mobile Legends"}, "denom": {"id": 1, "name": "1 Hari"}, "provider": {"id": 1, "name": "GXFiles"}}', '{"name": "QRIS", "detail": {"url": null, "type": "manual", "qr_url": null, "instruksi": "1. Buka aplikasi yang mendukung pembayaran dengan QRIS\\r\\n2. Pilih fitur QRIS / Bayar\\r\\n3. Pindai kode QR yang diberikan oleh Merchant\\\\r\\\\nPastikan tagihan yang ditagihkan sesuai\\r\\n4. Klik tombol Konfirmasi\\r\\n5. Masukkan PIN untuk menyelesaikan pembayaran\\r\\n6. Setelah pembayaran berhasil, kamu akan dialihkan ke Halaman Hasil Pembayaran", "name_account": "POWERED BY WIJAYAPAY", "number_account": "POWERED BY WIJAYAPAY", "virtual_account": null, "nomor_pembayaran": null}, "fee_fixed": 100, "fee_percent": 3.5}', 604, NULL, 'pending', 'pending', '2026-05-31 17:35:23', NULL, '2026-05-31 09:35:23', '2026-05-31 09:35:23'),
	(6, 1, '{"userId": "8275836687", "last_name": null, "first_name": "𝐊𝐈𝐈𝐌𝐎𝐃𝐒"}', 'KM-QSQA5NUJJE65', '{"game": {"id": 1, "name": "Mobile Legends"}, "denom": {"id": 1, "name": "1 Hari"}, "provider": {"id": 1, "name": "GXFiles"}}', '{"name": "QRIS", "detail": {"url": null, "type": "manual", "qr_url": null, "instruksi": "1. Buka aplikasi yang mendukung pembayaran dengan QRIS\\r\\n2. Pilih fitur QRIS / Bayar\\r\\n3. Pindai kode QR yang diberikan oleh Merchant\\\\r\\\\nPastikan tagihan yang ditagihkan sesuai\\r\\n4. Klik tombol Konfirmasi\\r\\n5. Masukkan PIN untuk menyelesaikan pembayaran\\r\\n6. Setelah pembayaran berhasil, kamu akan dialihkan ke Halaman Hasil Pembayaran", "name_account": "POWERED BY WIJAYAPAY", "number_account": "POWERED BY WIJAYAPAY", "virtual_account": null, "nomor_pembayaran": null}, "fee_fixed": 100, "fee_percent": 3.5}', 604, NULL, 'pending', 'pending', '2026-05-31 17:35:23', NULL, '2026-05-31 09:35:23', '2026-05-31 09:35:23'),
	(7, 1, '{"userId": "8275836687", "last_name": null, "first_name": "𝐊𝐈𝐈𝐌𝐎𝐃𝐒"}', 'KM-QSQA5NUJJE65', '{"game": {"id": 1, "name": "Mobile Legends"}, "denom": {"id": 1, "name": "1 Hari"}, "provider": {"id": 1, "name": "GXFiles"}}', '{"name": "QRIS", "detail": {"url": null, "type": "manual", "qr_url": null, "instruksi": "1. Buka aplikasi yang mendukung pembayaran dengan QRIS\\r\\n2. Pilih fitur QRIS / Bayar\\r\\n3. Pindai kode QR yang diberikan oleh Merchant\\\\r\\\\nPastikan tagihan yang ditagihkan sesuai\\r\\n4. Klik tombol Konfirmasi\\r\\n5. Masukkan PIN untuk menyelesaikan pembayaran\\r\\n6. Setelah pembayaran berhasil, kamu akan dialihkan ke Halaman Hasil Pembayaran", "name_account": "POWERED BY WIJAYAPAY", "number_account": "POWERED BY WIJAYAPAY", "virtual_account": null, "nomor_pembayaran": null}, "fee_fixed": 100, "fee_percent": 3.5}', 604, NULL, 'pending', 'pending', '2026-05-31 17:35:23', NULL, '2026-05-31 09:35:23', '2026-05-31 09:35:23'),
	(8, 1, '{"userId": "8275836687", "last_name": null, "first_name": "𝐊𝐈𝐈𝐌𝐎𝐃𝐒"}', 'KM-QSQA5NUJJE65', '{"game": {"id": 1, "name": "Mobile Legends"}, "denom": {"id": 1, "name": "1 Hari"}, "provider": {"id": 1, "name": "GXFiles"}}', '{"name": "QRIS", "detail": {"url": null, "type": "manual", "qr_url": null, "instruksi": "1. Buka aplikasi yang mendukung pembayaran dengan QRIS\\r\\n2. Pilih fitur QRIS / Bayar\\r\\n3. Pindai kode QR yang diberikan oleh Merchant\\\\r\\\\nPastikan tagihan yang ditagihkan sesuai\\r\\n4. Klik tombol Konfirmasi\\r\\n5. Masukkan PIN untuk menyelesaikan pembayaran\\r\\n6. Setelah pembayaran berhasil, kamu akan dialihkan ke Halaman Hasil Pembayaran", "name_account": "POWERED BY WIJAYAPAY", "number_account": "POWERED BY WIJAYAPAY", "virtual_account": null, "nomor_pembayaran": null}, "fee_fixed": 100, "fee_percent": 3.5}', 604, NULL, 'pending', 'pending', '2026-05-31 17:35:23', NULL, '2026-05-31 09:35:23', '2026-05-31 09:35:23'),
	(9, 1, '{"userId": "8275836687", "last_name": null, "first_name": "𝐊𝐈𝐈𝐌𝐎𝐃𝐒"}', 'KM-QSQA5NUJJE65', '{"game": {"id": 1, "name": "Mobile Legends"}, "denom": {"id": 1, "name": "1 Hari"}, "provider": {"id": 1, "name": "GXFiles"}}', '{"name": "QRIS", "detail": {"url": null, "type": "manual", "qr_url": null, "instruksi": "1. Buka aplikasi yang mendukung pembayaran dengan QRIS\\r\\n2. Pilih fitur QRIS / Bayar\\r\\n3. Pindai kode QR yang diberikan oleh Merchant\\\\r\\\\nPastikan tagihan yang ditagihkan sesuai\\r\\n4. Klik tombol Konfirmasi\\r\\n5. Masukkan PIN untuk menyelesaikan pembayaran\\r\\n6. Setelah pembayaran berhasil, kamu akan dialihkan ke Halaman Hasil Pembayaran", "name_account": "POWERED BY WIJAYAPAY", "number_account": "POWERED BY WIJAYAPAY", "virtual_account": null, "nomor_pembayaran": null}, "fee_fixed": 100, "fee_percent": 3.5}', 604, NULL, 'pending', 'pending', '2026-05-31 17:35:23', NULL, '2026-05-31 09:35:23', '2026-05-31 09:35:23'),
	(10, 1, '{"userId": "8275836687", "last_name": null, "first_name": "𝐊𝐈𝐈𝐌𝐎𝐃𝐒"}', 'KM-QSQA5NUJJE65', '{"game": {"id": 1, "name": "Mobile Legends"}, "denom": {"id": 1, "name": "1 Hari"}, "provider": {"id": 1, "name": "GXFiles"}}', '{"name": "QRIS", "detail": {"url": null, "type": "manual", "qr_url": null, "instruksi": "1. Buka aplikasi yang mendukung pembayaran dengan QRIS\\r\\n2. Pilih fitur QRIS / Bayar\\r\\n3. Pindai kode QR yang diberikan oleh Merchant\\\\r\\\\nPastikan tagihan yang ditagihkan sesuai\\r\\n4. Klik tombol Konfirmasi\\r\\n5. Masukkan PIN untuk menyelesaikan pembayaran\\r\\n6. Setelah pembayaran berhasil, kamu akan dialihkan ke Halaman Hasil Pembayaran", "name_account": "POWERED BY WIJAYAPAY", "number_account": "POWERED BY WIJAYAPAY", "virtual_account": null, "nomor_pembayaran": null}, "fee_fixed": 100, "fee_percent": 3.5}', 604, NULL, 'pending', 'pending', '2026-05-31 17:35:23', NULL, '2026-05-31 09:35:23', '2026-05-31 09:35:23'),
	(11, 1, '{"userId": "8275836687", "last_name": null, "first_name": "𝐊𝐈𝐈𝐌𝐎𝐃𝐒"}', 'KM-QSQA5NUJJE65', '{"game": {"id": 1, "name": "Mobile Legends"}, "denom": {"id": 1, "name": "1 Hari"}, "provider": {"id": 1, "name": "GXFiles"}}', '{"name": "QRIS", "detail": {"url": null, "type": "manual", "qr_url": null, "instruksi": "1. Buka aplikasi yang mendukung pembayaran dengan QRIS\\r\\n2. Pilih fitur QRIS / Bayar\\r\\n3. Pindai kode QR yang diberikan oleh Merchant\\\\r\\\\nPastikan tagihan yang ditagihkan sesuai\\r\\n4. Klik tombol Konfirmasi\\r\\n5. Masukkan PIN untuk menyelesaikan pembayaran\\r\\n6. Setelah pembayaran berhasil, kamu akan dialihkan ke Halaman Hasil Pembayaran", "name_account": "POWERED BY WIJAYAPAY", "number_account": "POWERED BY WIJAYAPAY", "virtual_account": null, "nomor_pembayaran": null}, "fee_fixed": 100, "fee_percent": 3.5}', 604, NULL, 'pending', 'pending', '2026-05-31 17:35:23', NULL, '2026-05-31 09:35:23', '2026-05-31 09:35:23'),
	(12, 1, '{"userId": "8275836687", "last_name": null, "first_name": "𝐊𝐈𝐈𝐌𝐎𝐃𝐒"}', 'KM-QSQA5NUJJE65', '{"game": {"id": 1, "name": "Mobile Legends"}, "denom": {"id": 1, "name": "1 Hari"}, "provider": {"id": 1, "name": "GXFiles"}}', '{"name": "QRIS", "detail": {"url": null, "type": "manual", "qr_url": null, "instruksi": "1. Buka aplikasi yang mendukung pembayaran dengan QRIS\\r\\n2. Pilih fitur QRIS / Bayar\\r\\n3. Pindai kode QR yang diberikan oleh Merchant\\\\r\\\\nPastikan tagihan yang ditagihkan sesuai\\r\\n4. Klik tombol Konfirmasi\\r\\n5. Masukkan PIN untuk menyelesaikan pembayaran\\r\\n6. Setelah pembayaran berhasil, kamu akan dialihkan ke Halaman Hasil Pembayaran", "name_account": "POWERED BY WIJAYAPAY", "number_account": "POWERED BY WIJAYAPAY", "virtual_account": null, "nomor_pembayaran": null}, "fee_fixed": 100, "fee_percent": 3.5}', 604, NULL, 'pending', 'pending', '2026-05-31 17:35:23', NULL, '2026-05-31 09:35:23', '2026-05-31 09:35:23');

-- membuang struktur untuk table tele_store_cheat.jobs
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Membuang data untuk tabel tele_store_cheat.jobs: ~0 rows (lebih kurang)
DELETE FROM `jobs`;

-- membuang struktur untuk table tele_store_cheat.job_batches
CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Membuang data untuk tabel tele_store_cheat.job_batches: ~0 rows (lebih kurang)
DELETE FROM `job_batches`;

-- membuang struktur untuk table tele_store_cheat.migrations
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Membuang data untuk tabel tele_store_cheat.migrations: ~7 rows (lebih kurang)
DELETE FROM `migrations`;
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
	(1, '0001_01_01_000000_create_users_table', 1),
	(2, '0001_01_01_000001_create_cache_table', 1),
	(3, '0001_01_01_000002_create_jobs_table', 1),
	(4, '2026_05_27_161019_create_personal_access_tokens_table', 2),
	(5, '2026_05_28_093510_create_denoms_table', 2),
	(6, '2026_05_28_095300_create_configs_table', 3),
	(7, '2026_05_28_150553_create_games_table', 4),
	(8, '2026_05_29_215138_create_histories_table', 5),
	(9, '2026_06_01_120224_create_downloads_table', 6);

-- membuang struktur untuk table tele_store_cheat.password_reset_tokens
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Membuang data untuk tabel tele_store_cheat.password_reset_tokens: ~0 rows (lebih kurang)
DELETE FROM `password_reset_tokens`;

-- membuang struktur untuk table tele_store_cheat.payments
CREATE TABLE IF NOT EXISTS `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` int DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `minimum` int NOT NULL,
  `maximum` int NOT NULL,
  `fee_fixed` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `fee_percent` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_account` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `deposit` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `pin` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `instruksi` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- Membuang data untuk tabel tele_store_cheat.payments: ~1 rows (lebih kurang)
DELETE FROM `payments`;
INSERT INTO `payments` (`id`, `provider`, `code`, `category_id`, `name`, `minimum`, `maximum`, `fee_fixed`, `fee_percent`, `number`, `name_account`, `image`, `deposit`, `pin`, `instruksi`, `status`, `created_at`, `updated_at`) VALUES
	(1, 'manual', 'QRIS', 2, 'QRIS', 1, 9999999, '100', '0.7', 'POWERED BY WIJAYAPAY', 'POWERED BY WIJAYAPAY', 'https://wijayapay.com/storage/logo/payment/qris.png', '1', '1', '1. Buka aplikasi yang mendukung pembayaran dengan QRIS\r\n2. Pilih fitur QRIS / Bayar\r\n3. Pindai kode QR yang diberikan oleh Merchant\\r\\nPastikan tagihan yang ditagihkan sesuai\r\n4. Klik tombol Konfirmasi\r\n5. Masukkan PIN untuk menyelesaikan pembayaran\r\n6. Setelah pembayaran berhasil, kamu akan dialihkan ke Halaman Hasil Pembayaran', 'active', '2025-05-31 13:52:22', '2026-04-18 15:43:57');

-- membuang struktur untuk table tele_store_cheat.personal_access_tokens
CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Membuang data untuk tabel tele_store_cheat.personal_access_tokens: ~0 rows (lebih kurang)
DELETE FROM `personal_access_tokens`;

-- membuang struktur untuk table tele_store_cheat.providers
CREATE TABLE IF NOT EXISTS `providers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `custom_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_api` int NOT NULL,
  `url` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `reset_license` enum('enabled','disabled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- Membuang data untuk tabel tele_store_cheat.providers: ~6 rows (lebih kurang)
DELETE FROM `providers`;
INSERT INTO `providers` (`id`, `name`, `custom_data`, `api_key`, `type_api`, `url`, `reset_license`, `status`, `created_at`, `updated_at`) VALUES
	(1, 'GXFiles', '', 'd2aaa72e-0aa5-4597-9f1b-8d00700f8434-c4e3bce', 2, '{"register":"https:\\/\\/gxfpanel.com\\/api\\/v1\\/order","game_id":"https:\\/\\/gxfpanel.com\\/api\\/v1\\/games","paket":"https:\\/\\/gxfpanel.com\\/api\\/v1\\/paket","reset":"https:\\/\\/gxfpanel.com\\/api\\/v1\\/member\\/reset","edit":"https:\\/\\/gxfpanel.com\\/api\\/v1\\/member\\/edit"}', 'enabled', 'active', '2025-09-03 14:53:16', '2026-06-01 11:00:18'),
	(2, 'SENJU PREMIUM', '', 'ywmx8i-gc3fs7-3pc9qr-1vg8a8', 1, '{"register":"https:\\/\\/youngjoygame.site\\/api\\/order\\/register","reset":"https:\\/\\/youngjoygame.site\\/api\\/order\\/reset-license","game_id":"https:\\/\\/youngjoygame.site\\/api\\/get\\/games","paket":null,"edit":"https:\\/\\/youngjoygame.site\\/api\\/order\\/change","get_game":"https:\\/\\/youngjoygame.site\\/api\\/get\\/games"}', 'enabled', 'active', '2026-02-15 09:04:26', '2026-03-19 00:33:25'),
	(3, 'CHRONOS VIP', '', '39110059-eb21-4470-9a4f-8539c05802ca', 2, '{"register":"https:\\/\\/newchronos.xyz\\/api\\/v1\\/order","reset":"https:\\/\\/newchronos.xyz\\/api\\/v1\\/member\\/reset","game_id":"https:\\/\\/newchronos.xyz\\/api\\/v1\\/games","paket":"https:\\/\\/newchronos.xyz\\/api\\/v1\\/paket","edit":"https:\\/\\/newchronos.xyz\\/api\\/v1\\/member\\/edit"}', 'enabled', 'active', '2026-02-15 10:15:13', '2026-03-30 20:24:58'),
	(4, 'ATTIC PREMIUM', '', 'hj37z1-5nruno-wg3m47-biycmk', 1, '{"register":"https:\\/\\/atticprem.shop\\/api\\/order\\/register","reset":"https:\\/\\/atticprem.shop\\/api\\/order\\/reset-license","game_id":null,"paket":null,"get_game":"https:\\/\\/atticprem.shop\\/api\\/get\\/games"}', 'enabled', 'active', '2026-02-15 10:19:20', '2026-04-03 22:56:40'),
	(5, 'CHEAT VIP', '', 'XXX', 0, '{"register":null,"reset":null,"game_id":null,"paket":null}', 'disabled', 'active', '2026-02-16 09:38:57', '2026-02-16 16:18:28'),
	(6, 'META PLUS', '', 'c416d1ef-46e9-4c20-b183-ab16f43c26e4-79fb9e5', 2, '{"register":"https:\\/\\/wearemetaplus.xyz\\/api\\/v1\\/order","get_game":"https:\\/\\/wearemetaplus.xyz\\/api\\/v1\\/games","reset":"https:\\/\\/wearemetaplus.xyz\\/api\\/v1\\/member\\/reset","game_id":"https:\\/\\/wearemetaplus.xyz\\/api\\/v1\\/games","paket":"https:\\/\\/wearemetaplus.xyz\\/api\\/v1\\/paket","edit":"https:\\/\\/wearemetaplus.xyz\\/api\\/v1\\/member\\/edit"}', 'enabled', 'active', '2026-02-28 22:46:42', '2026-03-02 19:48:38');

-- membuang struktur untuk table tele_store_cheat.sessions
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Membuang data untuk tabel tele_store_cheat.sessions: ~3 rows (lebih kurang)
DELETE FROM `sessions`;
INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
	('5Vvmzr1a0cgpmqxaPotmee2fTJhBl7RRsJFRlu2f', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJKb1Ezb0V0TmJxUVBMeXVjS0pyVGtFOTRLOXprbW94eWpGVThHM2Q4IiwiYWRtaW5fY29uZmlnX2F1dGhvcml6ZWQiOnRydWUsImFkbWluX3VzZXJfaWQiOiIxIiwiYWRtaW5fbmFtZSI6IlRlc3RpbmcgQWRtaW4iLCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvbG9jYWxob3N0OjgwMDBcL2FkbWluXC9jb25maWciLCJyb3V0ZSI6ImFkbWluLmNvbmZpZy5lZGl0In0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1780215340),
	('EiYBCyHEGyCDHRSTSHdJMsc6CH32yMP7cosKLL7N', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiI3d0hUT2pUMWh0WkFTNzEwd0k0M0VMcXlwMk1YZkduN3F2V3ZlUVZnIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzc3OTQtNDUtMjUxLTQtMjI4Lm5ncm9rLWZyZWUuYXBwXC9hZG1pblwvY29uZmlnIiwicm91dGUiOiJhZG1pbi5jb25maWcuZWRpdCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImFkbWluX2NvbmZpZ19hdXRob3JpemVkIjp0cnVlLCJhZG1pbl91c2VyX2lkIjoiNTYyNTMzNTcwNyIsImFkbWluX25hbWUiOiJaWCBcdTIwMjIgQWtpcmEgIn0=', 1780215306),
	('xqYnKbjdGqhqTdvisC3F7zbr7Ujd847pvVf1ql95', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiI1V3ZrWmNTejN5OWJoVVQwTzhMaGl3dGtDTFJ6cVRRTExXWTdJUlRJIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9hZG1pblwvY29uZmlnXC9sb2dpbiIsInJvdXRlIjoiYWRtaW4uY29uZmlnLmxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1780213449);

-- membuang struktur untuk table tele_store_cheat.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `session` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('user','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`username`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Membuang data untuk tabel tele_store_cheat.users: ~3 rows (lebih kurang)
DELETE FROM `users`;
INSERT INTO `users` (`id`, `user_id`, `first_name`, `last_name`, `username`, `session`, `role`, `created_at`, `updated_at`) VALUES
	(1, '5625335707', 'ZX • Akira', NULL, 'Akiracode', NULL, 'admin', '2026-05-28 08:04:36', '2026-06-01 11:00:18'),
	(2, '8275836687', '𝐊𝐈𝐈𝐌𝐎𝐃𝐒', NULL, 'KiiMods', 'admin_create_game:code:8bp', 'admin', '2026-05-31 09:34:41', '2026-06-01 05:58:39'),
	(3, '8556619771', 'CGK', NULL, 'CenMore', NULL, 'user', '2026-05-31 14:14:14', '2026-05-31 14:14:14');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
