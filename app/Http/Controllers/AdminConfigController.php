<?php

namespace App\Http\Controllers;

use App\Models\Config;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class AdminConfigController extends Controller
{
    /**
     * Authenticate and authorize session using the secure token generated from Telegram.
     */
    public function login(Request $request)
    {
        $userId = $request->query('user_id');
        $token = $request->query('token');

        if (!$userId || !$token) {
            abort(403, 'Akses ditolak: Parameter tidak lengkap.');
        }

        $cachedToken = Cache::get('admin_token_' . $userId);

        if ($cachedToken && $cachedToken === $token) {
            // Find the user to ensure they are an admin
            $user = User::where('user_id', $userId)->first();
            if (!$user || $user->role !== 'admin') {
                abort(403, 'Akses ditolak: Anda bukan administrator.');
            }

            // Store authorization details in session
            session([
                'admin_config_authorized' => true,
                'admin_user_id' => $user->user_id,
                'admin_name' => $user->first_name . ' ' . $user->last_name,
            ]);

            // Clear token from cache for security (one-time use link)
            Cache::forget('admin_token_' . $userId);

            return redirect()->route('admin.config.edit');
        }

        return view('admin.login_error', [
            'message' => 'Token keamanan tidak valid atau sudah kedaluwarsa. Silakan minta tautan masuk baru melalui bot Telegram.'
        ]);
    }

    /**
     * Show the configuration editor dashboard.
     */
    public function edit()
    {
        if (session('admin_config_authorized') !== true) {
            return redirect()->route('admin.config.login_error');
        }

        $config = Config::first();
        if (!$config) {
            // Create a default config if it doesn't exist
            $config = Config::create([
                'order' => [
                    'string' => '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                    'exp_order' => 60,
                    'prefix_order' => 'KM-',
                    'count_pending' => 4,
                    'transaksi_delay' => 2,
                    'length_random_order' => 12
                ],
                'bot' => [
                    'image' => 'https://dgstoreid.xyz/assets/images/logo/1757737759.png',
                    'contact' => 'https://t.me/akiracode'
                ],
                'payments' => [
                    'wijayapay' => [
                        'status' => true,
                        'api_key' => '7c61a92d37bf1ae98d6453520a',
                        'code_merchant' => 'WP6931ab3de7ad9'
                    ]
                ],
                'captions' => [
                    'orders' => [
                        [
                            'key' => 'menu_start',
                            'content' => "<b>{greeting} {firstname}</b>\n\nSelamat datang di bot 🚀"
                        ],
                        [
                            'key' => 'menu_order',
                            'content' => "🎮 Daftar Game - CHEAT GAME\n\nPilih game di bawah ini untuk melihat produk yang tersedia."
                        ],
                        [
                            'key' => 'menu_providers',
                            'content' => "Produk untuk {game}\n\n🔥 : Unggulan | 🎟️ : Bisa Pakai Bonus\n\nPilih produk di bawah ini untuk melihat detail harga."
                        ],
                        [
                            'key' => 'menu_denoms',
                            'content' => "📦 Produk: {produk}\n\nSilakan pilih masa aktif yang diinginkan:"
                        ],
                        [
                            'key' => 'menu_confirm_order',
                            'content' => "🛒 Konfirmasi Pembelian\n\nProduk: {produk} ({denom})\nHarga: {price}\n────────────────────\n💰Silakan pilih metode pembayaran:"
                        ],
                        [
                            'key' => 'invoice_order',
                            'content' => "<b>INVOICE PESANAN</b>\n\n<b>ID Invoice:</b> <code>{invoice_id}</code>\n<b>Status Pembayaran:</b> {status_pembayaran}\n<b>Status Proses:</b> {status_proses}\n<b>Berlaku Sampai:</b> {expired_at}\n\n<b>Detail Produk</b>\n🎮 Game: {game}\n🏢 Provider: {provider}\n💎 Denom: {denom}\n💰 Harga: {price}\n\n<b>Metode Pembayaran</b>\n💳 Payment: {payment}\n👤 Atas Nama: {name_account}\n🔢 Nomor Rekening: <code>{number_account}</code>\n🏦 Virtual Account: <code>{virtual_account}</code>\n💼 Nomor Pembayaran: <code>{nomor_pembayaran}</code>\n\n<b>Instruksi</b>\n{instruksi}"
                        ],
                        [
                            'key' => 'cancel_order',
                            'content' => "Berhasil membatalkan transaksi dengan invoice id : {invoice_id} "
                        ],
                        [
                            'key' => 'confirm_cancel_order',
                            'content' => "⚠️ <b>Konfirmasi Pembatalan</b>\n\nKamu akan membatalkan pesanan berikut:\n\n🧾 <b>Invoice:</b> <code>{invoice_id}</code>\n🎮 <b>Game:</b> {game}\n💎 <b>Denom:</b> {denom}\n💰 <b>Total:</b> {price}\n\n<i>Pesanan yang dibatalkan tidak dapat dikembalikan.</i>\n\nApakah kamu yakin ingin membatalkan pesanan ini?"
                        ]
                    ],
                    'others_button' => [
                        [
                            'key' => 'menu_history',
                            'content' => "<b>Riwayat Transaksi (Halaman {page}/{total_pages})</b>\n\n{list_transactions}\n\n<i>Gunakan perintah berikut untuk melihat detail:</i>\n<code>/cek_invoice INVOICE_ID</code>"
                        ],
                        [
                            'key' => 'menu_history_empty',
                            'content' => "<b>Riwayat Transaksi</b>\n\nKamu belum memiliki riwayat transaksi.\n\nKirimkan ID Invoice Anda untuk mencari detail transaksi secara langsung."
                        ],
                        [
                            'key' => 'menu_account',
                            'content' => "<b>Informasi Akun</b>\n\n<b>ID User:</b> <code>{user_id}</code>\n<b>Nama:</b> {name}\n<b>Username:</b> {username}\n<b>Role:</b>{role}\n<b>Terdaftar:</b> {registered_at}"
                        ],
                        [
                            'key' => 'menu_leaderboard_weekly',
                            'content' => "<b>LEADERBOARD MINGGUAN</b>\n <i>7 Hari Terakhir</i>\n\n{list_rank}"
                        ],
                        [
                            'key' => 'menu_leaderboard_monthly',
                            'content' => "<b>LEADERBOARD BULANAN</b>\n<i>Awal s/d Akhir Bulan</i>\n\n{list_rank}"
                        ],
                        [
                            'key' => 'menu_announcement',
                            'content' => "🎁 BONUS DEPOSIT 8%\nIsi saldo Anda sekarang dan raih bonus 8% , langsung buka menu akun saya untuk deposit saldo"
                        ],
                        [
                            'key' => 'menu_resetlicense',
                            'content' => "🔑 <b>Reset Kunci Lisensi</b>\n\nFitur ini untuk mereset kunci lisensi Anda agar dapat digunakan di perangkat baru.\n\nSilakan pilih produk yang sesuai:"
                        ],
                        [
                            'key' => 'menu_select_resetlicense',
                            'content' => "Anda telah memilih <b>{provider}</b>.\n\n✍️ Silakan kirimkan Kunci Lisensi Anda di chat ini untuk melanjutkan proses reset."
                        ]
                    ]
                ]
            ]);
        }

        return Inertia::render('Admin/Config', [
            'config' => $config,
            'adminName' => session('admin_name', 'Administrator'),
        ]);
    }

    /**
     * Update the configuration details.
     */
    public function update(Request $request)
    {
        if (session('admin_config_authorized') !== true) {
            return response()->json(['success' => false, 'message' => 'Unauthorized session.'], 403);
        }

        // Cast numeric fields that Vue may send as strings
        $order = $request->input('order', []);
        $order['exp_order'] = (int) ($order['exp_order'] ?? 60);
        $order['count_pending'] = (int) ($order['count_pending'] ?? 4);
        $order['transaksi_delay'] = (int) ($order['transaksi_delay'] ?? 2);
        $order['length_random_order'] = (int) ($order['length_random_order'] ?? 12);

        $payments = $request->input('payments', []);
        $payments['wijayapay']['status'] = filter_var($payments['wijayapay']['status'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $request->merge(compact('order', 'payments'));

        $validator = Validator::make($request->all(), [
            // Order validation
            'order.string' => 'required|string',
            'order.exp_order' => 'required|integer|min:1',
            'order.prefix_order' => 'nullable|string',
            'order.count_pending' => 'required|integer|min:1',
            'order.transaksi_delay' => 'required|integer|min:0',
            'order.length_random_order' => 'required|integer|min:4|max:30',

            // Bot validation
            'bot.image' => 'required|url',
            'bot.contact' => 'required|url',

            // Payments validation
            'payments.wijayapay.status' => 'required|boolean',
            'payments.wijayapay.api_key' => 'required_if:payments.wijayapay.status,true|nullable|string',
            'payments.wijayapay.code_merchant' => 'required_if:payments.wijayapay.status,true|nullable|string',

            // Captions
            'captions.orders' => 'required|array',
            'captions.others_button' => 'required|array',
        ], [
            'required' => 'Kolom :attribute wajib diisi.',
            'integer' => 'Kolom :attribute harus berupa angka.',
            'url' => 'Format link :attribute tidak valid.',
            'min' => 'Nilai minimal untuk :attribute adalah :min.',
            'required_if' => 'Kolom :attribute wajib diisi jika status aktif.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal. Silakan periksa kembali inputan Anda.',
                'errors' => $validator->errors()
            ], 422);
        }

        $config = Config::first();
        if (!$config) {
            $config = new Config();
        }

        // Merge inputs to prevent stripping fields
        $config->order = $request->input('order');
        $config->bot = $request->input('bot');
        $config->payments = $request->input('payments');
        $config->captions = $request->input('captions');

        $config->save();

        return response()->json([
            'success' => true,
            'message' => 'Konfigurasi berhasil disimpan dan diperbarui!'
        ]);
    }

    /**
     * Clear authorization session.
     */
    public function logout()
    {
        session()->forget(['admin_config_authorized', 'admin_user_id', 'admin_name']);
        return redirect()->route('admin.config.login_error', ['logged_out' => 1]);
    }

    /**
     * Show access/login error screen.
     */
    public function loginError(Request $request)
    {
        $message = $request->has('logged_out') 
            ? 'Anda telah berhasil keluar dari sesi konfigurasi.' 
            : 'Sesi Anda tidak valid, sudah kedaluwarsa, atau Anda belum masuk. Silakan minta tautan konfigurasi baru melalui bot Telegram.';

        return view('admin.login_error', compact('message'));
    }
}
