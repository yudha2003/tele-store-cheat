<?php
namespace App\Http\Controllers;

use App\Libraries\Apiv1;
use App\Libraries\Apiv2;
use App\Libraries\Wijayapay;
use App\Models\Denom;
use App\Models\Game;
use App\Models\History;
use App\Models\Payment;
use App\Models\Provider;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HandleCallback extends Controller
{
    public function handle(array $data, $user = null, $config = null)
    {
        try {
            if (! isset($data['data'])) {
                return;
            }

            $teleController = new TelegramController();
            $callbackData   = $data['data'];

            if ($user && str_starts_with($user->session, 'admin_')) {
                $user->session = null;
                $user->save();
            }

            return match (true) {
                $callbackData === 'menu_start'                                                      => $teleController->sessionStart($data['message']['chat']['id'], $data['message'], $config, false),
                $callbackData === 'menu_games'                                                      => $this->handleMenuOrder($data['message'] ?? [], $user, $config),
                $callbackData === 'menu_history' || str_starts_with($callbackData, 'menu_history:') => $this->handleHistory($callbackData, $data['message'] ?? [], $user, $config),
                $callbackData === 'menu_account'                                                    => $this->handleAccount($data['message'] ?? [], $user, $config),
                $callbackData === 'menu_resetlicense'                                               => $this->handleResetLicense($callbackData, $data, $user, $config),
                $callbackData === 'cancel_reset_license'                                            => $this->handleCancelResetLicense($data['message'] ?? [], $user, $config),
                str_starts_with($callbackData, 'reset_license_prov:')                               => $this->selectResetLicenseProvider($callbackData, $data, $user, $config),
                $callbackData === 'leaderboard' || str_starts_with($callbackData, 'leaderboard:')   => $this->handleLeaderboard($callbackData, $data['message'] ?? [], $config),
                $callbackData === 'pengumuman'                                                      => $this->handleAnnouncement($data['message'] ?? [], $config),
                $callbackData === 'current_page'                                                    => $this->answerCallbackQuery($data['id'] ?? null),
                str_starts_with($callbackData, 'open_invoice:')                                     => $this->handleOpenInvoice($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'check_status:')                                     => $this->handleCheckStatus($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'provider:')                                         => $this->handleProviders($callbackData, $data['message'], $user, $config),
                str_starts_with($callbackData, 'data_denoms:')                                      => $this->handleDenoms($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'data_payment:')                                     => $this->handlePayments($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'submit_order:')                                     => $this->submitOrder($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'cancel_order:')                                     => $this->confirmCancelOrder($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'do_cancel:')                                        => $this->cancelOrder($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin')                                        => (new HandleCallbackAdmin())->handle($callbackData, $data, $user, $config),
                default                                                                             => null,
            };
        } catch (\Throwable $e) {
            return json_encode([
                'status'  => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function handleMenuOrder(array $message, $user, $config = null)
    {
        $games = Game::where('status', 'active')->get();

        $buttons = $games->map(function ($row) {
            return [
                'text'          => '🎮 ' . $row->name,
                'callback_data' => 'provider:' . $row->id,
            ];
        })->toArray();

        $keyboard   = array_chunk($buttons, 2);
        $keyboard[] = [
            ['text' => '🏠 Menu Utama', 'callback_data' => 'menu_start'],
        ];

        $captionRow = collect($config->captions['orders'] ?? [])->firstWhere('key', 'menu_games');
        $caption    = is_array($captionRow)
            ? ($captionRow['content'] ?? 'Silakan pilih game')
            : 'Silakan pilih game';

        return editMessageOrCaption($message, $caption, $keyboard);
    }

    private function handleProviders(string $callbackData, array $message, $user, $config = null)
    {
        $explode = explode(':', $callbackData, 2);
        $game_id = $explode[1] ?? null;
        $game    = Game::where([['id', $game_id], ['status', 'active']])->first();

        if (! $game) {
            $this->answerCallbackQuery($message['id'] ?? null, 'Data game tidak ditemukan', true);
            return;
        }

        $game_id   = $game->id;
        $providers = Provider::whereIn('id', $game->providers ?? [])->get();

        $buttons = $providers->map(function ($row) use ($game_id) {
            return [
                'text'          => $row->name,
                'callback_data' => 'data_denoms:' . $row->id . ':' . $game_id,
            ];
        })->toArray();

        $keyboard   = array_chunk($buttons, 2);
        $keyboard[] = [
            ['text' => '🎮 Pilih Game Lain', 'callback_data' => 'menu_games'],
        ];
        $keyboard[] = [
            ['text' => '🏠 Menu Utama', 'callback_data' => 'menu_start'],
        ];

        $captionRow = collect($config->captions['orders'] ?? [])->firstWhere('key', 'menu_providers');
        $caption    = is_array($captionRow)
            ? (str_replace('{game}', $game->name, $captionRow['content']) ?? 'Silakan pilih providers')
            : 'Silakan pilih providers';

        return editMessageOrCaption($message, $caption, $keyboard);
    }

    private function handleDenoms(string $callbackData, array $data, $user, $config = null)
    {
        $explode     = explode(':', $callbackData);
        $provider_id = $explode[1] ?? null;
        $game_id     = $explode[2] ?? null;
        $denom       = Denom::where([['provider_id', $provider_id], ['game_id', $game_id]])->get();

        if ($denom->count() <= 0) {
            return $this->answerCallbackQuery($data['id'], 'Denom tidak tersedia', true);
        }

        $provider = Provider::where('id', $provider_id)->first();

        if (! $provider) {
            return $this->answerCallbackQuery($data['id'] ?? null, 'Data provider tidak ditemukan', true);
        }

        $buttons = $denom->map(function ($row) use ($provider_id, $game_id) {
            return [
                'text'          => $row->name,
                'callback_data' => 'data_payment:' . $row->id . ':' . $provider_id . ':' . $game_id,
            ];
        })->toArray();

        $keyboard   = array_chunk($buttons, 2);
        $keyboard[] = [
            ['text' => '📦 Pilih Produk Lain', 'callback_data' => 'provider:' . $game_id],
        ];
        $keyboard[] = [
            ['text' => '🎮 Pilih Game Lain', 'callback_data' => 'menu_games'],
        ];

        $captionRow = collect($config->captions['orders'] ?? [])->firstWhere('key', 'menu_denoms');
        $caption    = is_array($captionRow)
            ? (str_replace('{produk}', $provider->name, $captionRow['content']) ?? 'Silakan pilih denoms')
            : 'Silakan pilih denoms';

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handlePayments(string $callbackData, array $data, $user, $config = null)
    {
        $explode     = explode(':', $callbackData);
        $denom_id    = $explode[1] ?? null;
        $provider_id = $explode[2] ?? null;
        $game_id     = $explode[3] ?? null;
        $denom       = Denom::where('id', $denom_id)->first();

        if (! $denom) {
            return $this->answerCallbackQuery($data['id'] ?? null, 'Data denom tidak ditemukan');
        }

        $payment = Payment::where('status', 'active')->get();

        if ($payment->count() <= 0) {
            return $this->answerCallbackQuery($data['id'], 'Metode pembayaran tidak tersedia', true);
        }

        $buttons = $payment->map(function ($row) use ($denom_id, $provider_id, $game_id) {
            return [
                'text'          => '💳 ' . $row->name,
                'callback_data' => 'submit_order:' . $row->id . ':' . $denom_id . ':' . $provider_id . ':' . $game_id,
            ];
        })->toArray();

        $provider = Provider::where('id', $provider_id)->first();

        if (! $provider) {
            return $this->answerCallbackQuery($data['id'], 'Data provider dari denom tidak ditemukan');
        }

        $game = Game::where('id', $game_id)->first();

        if (! $game) {
            return $this->answerCallbackQuery($data['id'], 'Data game dari denom tidak ditemukan');
        }

        $keyboard   = array_chunk($buttons, 2);
        $keyboard[] = [
            ['text' => '💎 Pilih Denom Lain', 'callback_data' => 'data_denoms:' . $provider_id . ':' . $game_id],
        ];
        $keyboard[] = [
            ['text' => '📦 Pilih Produk Lain', 'callback_data' => 'provider:' . $game_id],
            ['text' => '🏠 Menu Utama', 'callback_data' => 'menu_start'],
        ];

        $captionRow = collect($config->captions['orders'] ?? [])->firstWhere('key', 'menu_confirm_order');
        $caption    = is_array($captionRow)
            ? (str_replace(['{produk}', '{denom}', '{price}'], [$provider->name, $denom->name, 'Rp ' . number_format($denom->price, 0, ',', '.')], $captionRow['content']) ?? 'Silakan pilih metode pembayaran')
            : 'Silakan pilih metode pembayaran';

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function submitOrder(string $callbackData, array $data, $user = null, $config = null)
    {
        $explode     = explode(':', $callbackData);
        $payment_id  = $explode[1] ?? null;
        $denom_id    = $explode[2] ?? null;
        $provider_id = $explode[3] ?? null;
        $game_id     = $explode[4] ?? null;

        $payment  = Payment::where([['id', $payment_id], ['status', 'active']])->first();
        $game     = Game::where([['id', $game_id], ['status', 'active']])->first();
        $provider = Provider::where([['id', $provider_id], ['status', 'active']])->first();

        if (! $payment) {
            return $this->answerCallbackQuery($data['id'] ?? null, 'Metode pembayaran tidak tersedia', true);
        }

        if (! $game) {
            return $this->answerCallbackQuery($data['id'] ?? null, 'Game tidak aktif / tidak tersedia', true);
        }

        if (! $provider) {
            return $this->answerCallbackQuery($data['id'] ?? null, 'Provider dari denom tidak tersedia / tidak aktif', true);
        }

        $denom = Denom::where([
            ['id', $denom_id],
            ['provider_id', $provider->id],
            ['game_id', $game_id],
            ['status', 'active'],
        ])->first();

        if (! $denom) {
            return $this->answerCallbackQuery($data['id'] ?? null, 'Denom tidak tersedia atau tidak aktif', true);
        }

        $this->answerCallbackQuery($data['id'], 'Sedang memproses...');

        try {
            DB::beginTransaction();

            $fee_fixed      = (int) ($payment->fee_fixed ?? 0);
            $fee_percentage = ((float) $denom->price * (float) ($payment->fee_percent ?? 0)) / 100;
            $total_price    = (int) ceil((float) $denom->price + $fee_fixed + $fee_percentage);

            $detail = [
                'type'             => null,
                'qr_url'           => null,
                'virtual_account'  => null,
                'nomor_pembayaran' => null,
                'url'              => null,
                'name_account'     => $payment->name_account ?? null,
                'number_account'   => $payment->number ?? null,
                'instruksi'        => $payment->instruksi ?? null,
            ];

            $providerPayment = strtolower((string) $payment->provider);

            if ($providerPayment === 'manual') {
                $detail['type'] = 'manual';
            }

            if ($providerPayment === 'private') {
                $detail['type']           = 'private';
                $detail['number_account'] = $payment->number ?? $payment->number_account ?? null;
            }

            $invoice_id = trim(
                str_replace(' ', '', $config->order['prefix_order']) .
                $this->random($config->order['length_random_order'], $config->order['string'])
            );

            if ($providerPayment === 'wijayapay' && ($config->payments['wijayapay']['status'] ?? false) == true) {
                $wijayapay = new Wijayapay();
                $create    = $wijayapay->request($invoice_id, $total_price, $payment->code);

                if (! isset($create['success']) || ! $create['success']) {
                    DB::rollBack();
                    $this->answerCallbackQuery($data['id'] ?? null, 'Gagal membuat invoice', true);
                    return json_encode([
                        'status'  => false,
                        'message' => $create['msg'] ?? 'Gagal membuat invoice',
                    ]);
                }

                $detail['type']             = 'wijayapay';
                $detail['url']              = $create['data']['checkout_url'] ?? $create['data']['pay_url'] ?? null;
                $detail['virtual_account']  = $create['data']['nomor_va'] ?? null;
                $detail['nomor_pembayaran'] = $create['data']['nomor_pembayaran'] ?? null;
                $detail['qr_url']           = $create['data']['qr_image'] ?? null;

                if (! $payment->instruksi) {
                    $detail['instruksi'] = $create['data']['tutorial_pembayaran'] ?? null;
                }
            }

            $history = History::create([
                'user_id'        => $user->id,
                'temporary_data' => [
                    'userId'     => $user->user_id,
                    'first_name' => $user->first_name,
                    'last_name'  => $user->last_name,
                ],
                'invoice_id'     => $invoice_id,
                'product'        => [
                    'game'     => ['id' => $game->id, 'name' => $game->name],
                    'provider' => ['id' => $provider->id, 'name' => $provider->name],
                    'denom'    => ['id' => $denom->id, 'name' => $denom->name],
                ],
                'payment'        => [
                    'name'        => $payment->name,
                    'fee_fixed'   => $fee_fixed,
                    'fee_percent' => $fee_percentage,
                    'detail'      => $detail,
                ],
                'price'          => $total_price,
                'payment_status' => 'pending',
                'process_status' => 'pending',
                'expire_at'      => Carbon::now()->addMinutes((int) $config->order['exp_order']),
            ]);

            DB::commit();

            $captionRow = collect($config->captions['orders'] ?? [])->firstWhere('key', 'invoice_order');
            $template   = is_array($captionRow) ? ($captionRow['content'] ?? null) : null;

            if (! $template) {
                $template = "<b>INVOICE PESANAN</b>\n\n<b>ID Invoice:</b> <code>{invoice_id}</code>\n<b>Status Pembayaran:</b> {status_pembayaran}\n<b>Status Proses:</b> {status_proses}\n<b>Berlaku Sampai:</b> {expired_at}\n\n<b>Detail Produk</b>\n🎮 Game: {game}\n🏢 Provider: {provider}\n💎 Denom: {denom}\n💰 Harga: {price}\n\n<b>Metode Pembayaran</b>\n💳 Payment: {payment}\n👤 Atas Nama: {name_account}\n🔢 Nomor Rekening: <code>{number_account}</code>\n🏦 Virtual Account: <code>{virtual_account}</code>\n💼 Nomor Pembayaran: <code>{nomor_pembayaran}</code>\n🔗 {payment_url}\n\n<b>Instruksi</b>\n{instruksi}";
            }

            $paymentUrlText = ! empty($detail['url']) ? '<a href="' . $detail['url'] . '">Klik untuk bayar</a>' : '-';

            $replaces = [
                '{invoice_id}'        => $history->invoice_id,
                '{status_pembayaran}' => ucfirst($history->payment_status),
                '{status_proses}'     => ucfirst($history->process_status),
                '{expired_at}'        => Carbon::parse($history->expire_at)->format('d M Y, H:i \G\M\T+7'),
                '{game}'              => $game->name ?? '-',
                '{provider}'          => $provider->name ?? '-',
                '{denom}'             => $denom->name ?? '-',
                '{product_price}'     => 'Rp ' . number_format($denom->price, 0, ',', '.'),
                '{fee_fixed}'         => 'Rp ' . number_format($fee_fixed, 0, ',', '.'),
                '{fee_percent}'       => 'Rp ' . number_format($fee_percentage, 0, ',', '.'),
                '{price}'             => 'Rp ' . number_format($total_price, 0, ',', '.'),
                '{payment}'           => $payment->name ?? '-',
                '{name_account}'      => $detail['name_account'] ?: '-',
                '{number_account}'    => $detail['number_account'] ?: '-',
                '{virtual_account}'   => $detail['virtual_account'] ?: '-',
                '{nomor_pembayaran}'  => $detail['nomor_pembayaran'] ?: '-',
                '{instruksi}'         => $detail['instruksi'] ?: 'Silakan selesaikan pembayaran sebelum waktu habis.',
            ];

            $caption = str_replace(array_keys($replaces), array_values($replaces), $template);

            $keyboard = [
                [
                    ['text' => '❌ Batalkan Pesanan', 'callback_data' => 'cancel_order:' . $history->invoice_id],
                    ['text' => '🔍 Cek Status', 'callback_data' => 'check_status:' . $history->invoice_id],
                ],
                [
                    ['text' => '👨‍💼 Hubungi Admin', 'url' => $config->bot['contact'] ?? 'https://t.me/username_admin'],
                ],
                [
                    ['text' => '🏠 Menu Utama', 'callback_data' => 'menu_start'],
                ],
            ];

            $teleController = new TelegramController();
            $teleController->sessionStart($data['message']['chat']['id'] ?? null, $data['message'] ?? [], $config, false);

            if (! empty($detail['qr_url'])) {
                return sendPhoto(
                    $data['message']['chat']['id'] ?? null,
                    $detail['qr_url'],
                    $caption,
                    $keyboard,
                    'HTML'
                );
            }

            return sendMessage([
                'chat_id'  => $data['message']['chat']['id'] ?? null,
                'text'     => $caption,
                'mode'     => 'HTML',
                'keyboard' => $keyboard,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return json_encode([
                'status'  => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function confirmCancelOrder(string $callbackData, array $data, $user = null, $config = null)
    {
        $explode    = explode(':', $callbackData);
        $invoice_id = $explode[1] ?? null;

        $history = History::where('invoice_id', $invoice_id)->orWhere('id', $invoice_id)->first();

        if (! $history) {
            return $this->answerCallbackQuery($data['id'] ?? null, 'Data transaksi tidak ditemukan', true);
        }

        if ($history->payment_status !== 'pending') {
            return $this->answerCallbackQuery($data['id'] ?? null, 'Pesanan ini tidak dapat dibatalkan', true);
        }

        $captionRow = collect($config->captions['orders'] ?? [])->firstWhere('key', 'confirm_cancel_order');
        $template   = is_array($captionRow)
            ? ($captionRow['content'] ?? null)
            : null;

        if (! $template) {
            $template = "⚠️ <b>Konfirmasi Pembatalan</b>\n\nKamu akan membatalkan pesanan berikut:\n\n🧾 <b>Invoice:</b> <code>{invoice_id}</code>\n🎮 <b>Game:</b> {game}\n💎 <b>Denom:</b> {denom}\n💰 <b>Total:</b> {price}\n\n<i>Pesanan yang dibatalkan tidak dapat dikembalikan.</i>\n\nApakah kamu yakin ingin membatalkan pesanan ini?";
        }

        $caption = str_replace(
            ['{invoice_id}', '{game}', '{denom}', '{price}'],
            [
                $invoice_id,
                $history->product['game']['name'] ?? '-',
                $history->product['denom']['name'] ?? '-',
                'Rp ' . number_format($history->price, 0, ',', '.'),
            ],
            $template
        );

        $keyboard = [
            [
                ['text' => '✅ Ya, Batalkan', 'callback_data' => 'do_cancel:' . $invoice_id],
                ['text' => '↩️ Tidak, Kembali', 'callback_data' => 'check_status:' . $invoice_id],
            ],
        ];

        return editMessageOrCaption(
            $data['message'] ?? [],
            $caption,
            $keyboard,
            'HTML'
        );
    }

    private function cancelOrder(string $callbackData, array $data, $user = null, $config = null)
    {
        $explode    = explode(':', $callbackData);
        $invoice_id = $explode[1] ?? null;

        $history = History::where('invoice_id', $invoice_id)->orWhere('id', $invoice_id)->first();

        if (! $history) {
            return $this->answerCallbackQuery($data['id'] ?? null, 'Data transaksi tidak ditemukan', true);
        }

        if ($history->payment_status !== 'pending') {
            return $this->answerCallbackQuery($data['id'] ?? null, 'Pesanan ini tidak dapat dibatalkan', true);
        }

        $history->payment_status = 'failed';
        $history->process_status = 'failed';
        $history->save();

        $this->answerCallbackQuery($data['id'] ?? null, '✅ Pesanan berhasil dibatalkan');

        $captionRow = collect($config->captions['orders'] ?? [])->firstWhere('key', 'cancel_order');
        $template   = is_array($captionRow)
            ? ($captionRow['content'] ?? null)
            : null;

        if (! $template) {
            $template = "❌ <b>Pesanan Dibatalkan</b>\n\n🧾 <b>Invoice:</b> <code>{invoice_id}</code>\n\nPesanan kamu telah berhasil dibatalkan.\nSilakan buat pesanan baru jika diperlukan.";
        }

        $caption = str_replace('{invoice_id}', $invoice_id, $template);

        $keyboard = [
            [
                ['text' => '🛒 Buat Pesanan Baru', 'callback_data' => 'menu_games'],
                ['text' => '🏠 Menu Utama', 'callback_data' => 'menu_start'],
            ],
        ];

        deleteMessage($data['message']['chat']['id'] ?? null, $data['message']['message_id'] ?? null);

        return sendPhoto(
            $data['message']['chat']['id'] ?? null,
            $config->bot['image'],
            $caption,
            $keyboard,
            'HTML'
        );
    }

    private function handleHistory(string $callbackData, array $message, $user, $config = null)
    {
        $explode = explode(':', $callbackData);
        $page    = isset($explode[1]) ? (int) $explode[1] : 1;
        if ($page < 1) {
            $page = 1;
        }

        $perPage = 10;
        $offset  = ($page - 1) * $perPage;

        $totalTransactions = History::where('user_id', $user->id)->count();
        $totalPages        = (int) ceil($totalTransactions / $perPage);

        if ($totalTransactions === 0) {
            $captionRow = collect($config->captions['others_button'] ?? [])->firstWhere('key', 'menu_history_empty');
            $caption    = is_array($captionRow) ? ($captionRow['content'] ?? null) : null;
            if (! $caption) {
                $caption = "<b>📜 Riwayat Transaksi</b>\n\nKamu belum memiliki riwayat transaksi.\n\nKirimkan ID Invoice Anda untuk mencari detail transaksi secara langsung.";
            }
            $keyboard = [
                [
                    ['text' => '🏠 Menu Utama', 'callback_data' => 'menu_start'],
                ],
            ];
            if (isset($message['photo'])) {
                deleteMessage($message['chat']['id'] ?? null, $message['message_id'] ?? null);
                return sendMessage([
                    'chat_id'  => $message['chat']['id'] ?? null,
                    'text'     => $caption,
                    'mode'     => 'HTML',
                    'keyboard' => $keyboard,
                ]);
            }
            return editMessageOrCaption($message, $caption, $keyboard);
        }

        $histories = History::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->skip($offset)
            ->take($perPage)
            ->get();

        $captionRow = collect($config->captions['others_button'] ?? [])->firstWhere('key', 'menu_history');
        $template   = is_array($captionRow) ? ($captionRow['content'] ?? null) : null;
        if (! $template) {
            $template = "<b>📜 Riwayat Transaksi (Halaman {page}/{total_pages})</b>\n\n{list_transactions}\n\n<i>Gunakan perintah berikut untuk melihat detail:</i>\n<code>/cek-invoice [ID_Invoice]</code>";
        }

        $listTransactions = '';
        $index            = $offset + 1;
        foreach ($histories as $history) {
            $gameName   = $history->product['game']['name'] ?? '-';
            $denomName  = $history->product['denom']['name'] ?? '-';
            $statusPay  = ucfirst($history->payment_status ?? 'pending');
            $statusProc = ucfirst($history->process_status ?? 'pending');
            $priceStr   = 'Rp ' . number_format($history->price, 0, ',', '.');
            $date       = Carbon::parse($history->created_at)->format('d M Y, H:i \G\M\T+7');

            $listTransactions .= "<b>{$index}. Invoice:</b> <code>{$history->invoice_id}</code>\n";
            $listTransactions .= "   • {$gameName} ({$denomName})\n";
            $listTransactions .= "   • Total: {$priceStr}\n";
            $listTransactions .= "   • Status Bayar: {$statusPay}\n";
            $listTransactions .= "   • Status Proses: {$statusProc}\n";
            $listTransactions .= "   • Waktu: {$date}\n\n";
            $index++;
        }

        $caption = str_replace(
            ['{page}', '{total_pages}', '{list_transactions}'],
            [$page, $totalPages, rtrim($listTransactions)],
            $template
        );

        $navigationRow = [];
        if ($page > 1) {
            $navigationRow[] = ['text' => '◀️ Prev', 'callback_data' => 'menu_history:' . ($page - 1)];
        }

        $navigationRow[]  = ['text' => "Hal {$page}/{$totalPages}", 'callback_data' => 'current_page'];

        if ($page < $totalPages) {
            $navigationRow[] = ['text' => 'Next ▶️', 'callback_data' => 'menu_history:' . ($page + 1)];
        }

        $keyboard    = [];
        $keyboard[]  = $navigationRow;
        $keyboard[]  = [
            ['text' => '🏠 Menu Utama', 'callback_data' => 'menu_start'],
        ];

        if (isset($message['photo'])) {
            deleteMessage($message['chat']['id'] ?? null, $message['message_id'] ?? null);
            return sendMessage([
                'chat_id'  => $message['chat']['id'] ?? null,
                'text'     => $caption,
                'mode'     => 'HTML',
                'keyboard' => $keyboard,
            ]);
        }

        return editMessageOrCaption($message, $caption, $keyboard);
    }

    private function handleAccount(array $message, $user = null, $config = null)
    {
        $captionRow = collect($config->captions['others_button'] ?? [])->firstWhere('key', 'menu_account');
        $template   = is_array($captionRow) ? ($captionRow['content'] ?? null) : null;
        if (! $template) {
            $template = "<b>👤 Informasi Akun</b>\n\n• <b>ID User:</b> <code>{user_id}</code>\n• <b>Nama:</b> {name}\n• <b>Username:</b> {username}\n• <b>Role:</b> {role}\n• <b>Terdaftar:</b> {registered_at}";
        }

        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        if (empty($fullName)) {
            $fullName = 'User';
        }

        $username = $user->username ? '@' . $user->username : '-';
        $role     = ucfirst($user->role ?? 'user');
        $date     = $user->created_at ? Carbon::parse($user->created_at)->format('d M Y, H:i \G\M\T+7') : '-';

        $caption = str_replace(
            ['{user_id}', '{name}', '{username}', '{role}', '{registered_at}'],
            [$user->user_id, $fullName, $username, $role, $date],
            $template
        );

        $keyboard = [
            [
                ['text' => '🏠 Menu Utama', 'callback_data' => 'menu_start'],
            ],
        ];

        return editMessageOrCaption($message, $caption, $keyboard);
    }

    private function handleResetLicense(string $callbackData, array $data, $user = null, $config = null)
    {
        $message = $data['message'] ?? [];

        $captionRow = collect($config->captions['others_button'] ?? [])->firstWhere('key', 'menu_resetlicense');
        $caption    = is_array($captionRow) ? ($captionRow['content'] ?? null) : null;
        if (! $caption) {
            $caption = "🔑 <b>Reset Kunci Lisensi</b>\n\nFitur ini untuk mereset kunci lisensi Anda agar dapat digunakan di perangkat baru.\n\nSilakan pilih produk yang sesuai:";
        }

        $providers = \App\Models\Provider::where('status', 'active')
            ->where('reset_license', 'enabled')
            ->get();

        $buttons = $providers->map(function ($row) {
            return [
                'text'          => '🎮 ' . $row->name,
                'callback_data' => 'reset_license_prov:' . $row->id,
            ];
        })->toArray();

        $keyboard   = array_chunk($buttons, 2);
        $keyboard[] = [
            ['text' => '🏠 Menu Utama', 'callback_data' => 'menu_start'],
        ];

        return editMessageOrCaption($message, $caption, $keyboard);
    }

    private function selectResetLicenseProvider(string $callbackData, array $data, $user = null, $config = null)
    {
        $message    = $data['message'] ?? [];
        $explode    = explode(':', $callbackData);
        $providerId = $explode[1] ?? null;

        $provider = \App\Models\Provider::find($providerId);
        if (! $provider) {
            return $this->answerCallbackQuery($data['id'] ?? null, 'Provider tidak ditemukan.');
        }

        if ($user) {
            $user->session = 'reset_license:' . $provider->id;
            $user->save();
        }

        $captionRow = collect($config->captions['others_button'] ?? [])->firstWhere('key', 'menu_select_resetlicense');
        $template   = is_array($captionRow) ? ($captionRow['content'] ?? null) : null;
        if (! $template) {
            $template = "Anda telah memilih <b>{provider}</b>.\n\n✍️ Silakan kirimkan Kunci Lisensi Anda di chat ini untuk melanjutkan proses reset.";
        }

        $caption = str_replace('{provider}', htmlspecialchars($provider->name, ENT_QUOTES, 'UTF-8'), $template);

        $keyboard = [
            [
                ['text' => '❌ Batalkan', 'callback_data' => 'cancel_reset_license'],
            ],
        ];

        return editMessageOrCaption($message, $caption, $keyboard);
    }

    private function handleCancelResetLicense(array $message, $user, $config = null)
    {
        if ($user) {
            $user->session = null;
            $user->save();
        }
        $teleController = new TelegramController();
        return $teleController->sessionStart($message['chat']['id'] ?? 0, $message, $config, false);
    }

    private function handleLeaderboard(string $callbackData, array $message, $config = null)
    {
        $explode = explode(':', $callbackData);
        $type    = $explode[1] ?? 'weekly';

        if ($type === 'monthly') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate   = Carbon::now()->endOfMonth();

            $captionRow = collect($config->captions['others_button'] ?? [])->firstWhere('key', 'menu_leaderboard_monthly');
            $template   = is_array($captionRow) ? ($captionRow['content'] ?? null) : null;
            if (! $template) {
                $template = "🏆 <b>LEADERBOARD BULANAN</b>\n📅 <i>Awal s/d Akhir Bulan</i>\n\n{list_rank}";
            }
        } else {
            $startDate = Carbon::now()->subDays(7)->startOfDay();
            $endDate   = Carbon::now()->endOfDay();

            $captionRow = collect($config->captions['others_button'] ?? [])->firstWhere('key', 'menu_leaderboard_weekly');
            $template   = is_array($captionRow) ? ($captionRow['content'] ?? null) : null;
            if (! $template) {
                $template = "🏆 <b>LEADERBOARD MINGGUAN</b>\n📅 <i>7 Hari Terakhir</i>\n\n{list_rank}";
            }
        }
        $ranks = DB::table('histories')
            ->join('users', 'histories.user_id', '=', 'users.id')
            ->select(
                'users.first_name',
                'users.last_name',
                'users.username',
                DB::raw('SUM(histories.price) as total_spent'),
                DB::raw('COUNT(histories.id) as total_transactions')
            )
            ->where('histories.payment_status', 'paid')
            ->where('histories.process_status', 'paid')
            ->where('histories.created_at', '>=', $startDate)
            ->where('histories.created_at', '<=', $endDate)
            ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.username')
            ->orderBy('total_spent', 'desc')
            ->limit(10)
            ->get();

        $listRank = '';
        if ($ranks->isEmpty()) {
            $listRank = "<i>Belum ada data transaksi paid pada periode ini.</i>";
        } else {
            $index = 1;
            foreach ($ranks as $row) {
                $name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
                if (empty($name)) {
                    $name = $row->username ? '@' . $row->username : 'User';
                }

                $emoji = '';
                if ($index === 1) {
                    $emoji = '🥇 ';
                } elseif ($index === 2) {
                    $emoji = '🥈 ';
                } elseif ($index === 3) {
                    $emoji = '🥉 ';
                } else {
                    $emoji = "{$index}. ";
                }

                $totalSpentFormatted = 'Rp ' . number_format($row->total_spent, 0, ',', '.');
                $listRank            .= "{$emoji}<b>{$name}</b>\n";
                $listRank            .= "   • Total Belanja: {$totalSpentFormatted} ({$row->total_transactions} Transaksi)\n\n";
                $index++;
            }
        }
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth   = Carbon::now()->endOfMonth();

        $monthlyHistories = History::where('payment_status', 'paid')
            ->where('process_status', 'paid')
            ->where('created_at', '>=', $startOfMonth)
            ->where('created_at', '<=', $endOfMonth)
            ->select('product')
            ->get();

        $providerCounts = [];
        foreach ($monthlyHistories as $hist) {
            $providerName = $hist->product['provider']['name'] ?? null;
            if ($providerName) {
                $providerCounts[$providerName] = ($providerCounts[$providerName] ?? 0) + 1;
            }
        }

        arsort($providerCounts);

        $topServices = "────────────────────\n<b>Top Layanan Bulan Ini</b>\n";
        if (empty($providerCounts)) {
            $topServices .= " • Belum ada transaksi paid bulan ini.\n";
        } else {
            foreach ($providerCounts as $name => $total) {
                $topServices .= " • " . strtoupper($name) . " : {$total}x\n";
            }
        }
        $topServices .= "────────────────────";

        if (str_contains($template, '{top_services}')) {
            $template = str_replace('{top_services}', $topServices, $template);
            $caption  = str_replace('{list_rank}', rtrim($listRank), $template);
        } else {
            $caption  = str_replace('{list_rank}', rtrim($listRank), $template);
            $caption .= "\n\n" . $topServices;
        }

        $keyboard = [];
        if ($type === 'monthly') {
            $keyboard[] = [
                ['text' => '📅 Lihat Mingguan', 'callback_data' => 'leaderboard:weekly'],
            ];
        } else {
            $keyboard[] = [
                ['text' => '📅 Lihat Bulanan', 'callback_data' => 'leaderboard:monthly'],
            ];
        }

        $keyboard[] = [
            ['text' => '🏠 Menu Utama', 'callback_data' => 'menu_start'],
        ];

        return editMessageOrCaption($message, $caption, $keyboard);
    }

    private function handleAnnouncement(array $message, $config = null)
    {
        $captionRow   = collect($config->captions['others_button'] ?? [])->firstWhere('key', 'menu_announcement');
        $announcement = is_array($captionRow) ? ($captionRow['content'] ?? null) : null;
        if (! $announcement) {
            $announcement = "Tidak ada pengumuman saat ini.";
        }

        $date    = $config->updated_at ? Carbon::parse($config->updated_at)->format('d M Y, H:i \G\M\T+7') : '-';
        $caption = "<b>📢 Pengumuman</b>\n\n" . $announcement . "\n\n<i>Diterbitkan: {$date}</i>";

        $keyboard = [
            [
                ['text' => '🏠 Menu Utama', 'callback_data' => 'menu_start'],
            ],
        ];

        return editMessageOrCaption($message, $caption, $keyboard);
    }

    private function handleOpenInvoice(string $callbackData, array $data, $user, $config = null)
    {
        $explode    = explode(':', $callbackData);
        $invoice_id = $explode[1] ?? null;

        $history = History::where('invoice_id', $invoice_id)->orWhere('id', $invoice_id)->first();

        if (! $history) {
            return $this->answerCallbackQuery($data['id'] ?? null, 'Data invoice tidak ditemukan', true);
        }

        return $this->showInvoice($history, $data['message'] ?? [], $config, $data['id'] ?? null);
    }

    private function handleCheckStatus(string $callbackData, array $data, $user, $config = null)
    {
        $explode    = explode(':', $callbackData);
        $invoice_id = $explode[1] ?? null;

        $history = History::where('invoice_id', $invoice_id)->orWhere('id', $invoice_id)->first();

        if (! $history) {
            return $this->answerCallbackQuery($data['id'] ?? null, 'Data invoice tidak ditemukan', true);
        }

        return $this->showInvoice($history, $data['message'] ?? [], $config, $data['id'] ?? null);
    }

    public function showInvoice(History $history, array $message, $config, $callbackQueryId = null)
    {
        if ($callbackQueryId) {
            $this->answerCallbackQuery($callbackQueryId, 'Membuka invoice...');
        }

        $downloadButtons = [];
        if ($history->payment_status === 'paid') {
            $template = $config->captions['invoice'] ?? null;
            if (! $template) {
                $template = "<b>✅ PEMBELIAN BERHASIL</b>\n\n🧾 <b>ID Invoice:</b> <code>{invoice_id}</code>\n🎮 <b>Game:</b> {game}\n🏢 <b>Provider:</b> {provider}\n💎 <b>Masa Aktif:</b> {denom}\n💰 <b>Harga:</b> {price}\n\n🔑 <b>Keterangan / Lisensi:</b>\n{notes}\n\n📖 <b>Tutorial:</b>\n{tutorial}";
            }

            // Fetch download data from downloads table
            $downloadLinks = [];
            $tutorialText  = '';

            $downloadObj = \App\Models\Download::where('game_id', $history->product['game']['id'] ?? 0)
                ->where('provider_id', $history->product['provider']['id'] ?? 0)
                ->where('status', 'active')
                ->first();

            if ($downloadObj) {
                $downloadLinks = $downloadObj->data ?? [];
                $tutorialText  = $downloadObj->tutorial ?? '';
            }

            $notesContent = $history->notes['content'] ?? '';
            // Formulate download buttons
            if (! empty($downloadLinks)) {
                foreach ($downloadLinks as $linkObj) {
                    $titleVal = $linkObj['title'] ?? 'Download';
                    $linkVal  = $linkObj['link'] ?? '';
                    if ($linkVal) {
                        $downloadButtons[] = ['text' => $titleVal, 'url' => $linkVal];
                    }
                }
            } else {
                $fallbackDl = $history->notes['download'] ?? '';
                if ($fallbackDl) {
                    if (filter_var($fallbackDl, FILTER_VALIDATE_URL)) {
                        $downloadButtons[] = ['text' => '📥 Download File', 'url' => $fallbackDl];
                    } else {
                        $notesContent .= "\n\n📥 <b>Download:</b>\n" . $fallbackDl;
                    }
                }
            }

            // Formulate tutorial text
            if (empty($tutorialText)) {
                $tutorialText = $history->notes['tutorial'] ?? '';
            }

            $replaces = [
                '{invoice_id}' => $history->invoice_id,
                '{game}'       => $history->product['game']['name'] ?? '-',
                '{provider}'   => $history->product['provider']['name'] ?? '-',
                '{denom}'      => $history->product['denom']['name'] ?? '-',
                '{price}'      => 'Rp ' . number_format($history->price, 0, ',', '.'),
                '{notes}'      => $notesContent,
                '{download}'   => '', // Handled as buttons now
                '{tutorial}'   => $tutorialText ?: '-',
            ];

            $caption = str_replace(array_keys($replaces), array_values($replaces), $template);
        } else {
            $captionRow = collect($config->captions['orders'] ?? [])->firstWhere('key', 'invoice_order');
            $template   = is_array($captionRow) ? ($captionRow['content'] ?? null) : null;

            if (! $template) {
                $template = "<b>INVOICE PESANAN</b>\n\n<b>ID Invoice:</b> <code>{invoice_id}</code>\n<b>Status Pembayaran:</b> {status_pembayaran}\n<b>Status Proses:</b> {status_proses}\n<b>Berlaku Sampai:</b> {expired_at}\n\n<b>Detail Produk</b>\n🎮 Game: {game}\n🏢 Provider: {provider}\n💎 Denom: {denom}\n💰 Harga: {price}\n\n<b>Metode Pembayaran</b>\n💳 Payment: {payment}\n👤 Atas Nama: {name_account}\n🔢 Nomor Rekening: <code>{number_account}</code>\n🏦 Virtual Account: <code>{virtual_account}</code>\n💼 Nomor Pembayaran: <code>{nomor_pembayaran}</code>\n🔗 {payment_url}\n\n<b>Instruksi</b>\n{instruksi}";
            }

            $detail         = $history->payment['detail'] ?? [];
            $paymentUrlText = ! empty($detail['url']) ? '<a href="' . $detail['url'] . '">Klik untuk bayar</a>' : '-';
            $paymentName    = $history->payment['name'] ?? $history->payment['payment'] ?? '-';

            $replaces = [
                '{invoice_id}'        => $history->invoice_id,
                '{status_pembayaran}' => ucfirst($history->payment_status),
                '{status_proses}'     => ucfirst($history->process_status),
                '{expired_at}'        => Carbon::parse($history->expire_at)->format('d M Y, H:i \G\M\T+7'),
                '{game}'              => $history->product['game']['name'] ?? '-',
                '{provider}'          => $history->product['provider']['name'] ?? '-',
                '{denom}'             => $history->product['denom']['name'] ?? '-',
                '{product_price}'     => 'Rp ' . number_format($history->price, 0, ',', '.'),
                '{fee_fixed}'         => 'Rp ' . number_format($history->payment['fee_fixed'] ?? 0, 0, ',', '.'),
                '{fee_percent}'       => 'Rp ' . number_format($history->payment['fee_percent'] ?? 0, 0, ',', '.'),
                '{price}'             => 'Rp ' . number_format($history->price, 0, ',', '.'),
                '{payment}'           => $paymentName,
                '{name_account}'      => $detail['name_account'] ?? '-',
                '{number_account}'    => $detail['number_account'] ?? '-',
                '{virtual_account}'   => $detail['virtual_account'] ?? '-',
                '{nomor_pembayaran}'  => $detail['nomor_pembayaran'] ?? '-',
                '{payment_url}'       => $paymentUrlText,
                '{instruksi}'         => $detail['instruksi'] ?? 'Silakan selesaikan pembayaran sebelum waktu habis.',
            ];

            $caption = str_replace(array_keys($replaces), array_values($replaces), $template);
        }

        $keyboard = [];
        if ($history->payment_status === 'paid' && ! empty($downloadButtons)) {
            foreach ($downloadButtons as $btn) {
                $keyboard[] = [$btn];
            }
        }

        if ($history->payment_status === 'pending') {
            $keyboard[] = [
                ['text' => '❌ Batalkan Pesanan', 'callback_data' => 'cancel_order:' . $history->invoice_id],
                ['text' => '🔍 Cek Status', 'callback_data' => 'check_status:' . $history->invoice_id],
            ];
        } else {
            $keyboard[] = [
                ['text' => '🔍 Cek Status', 'callback_data' => 'check_status:' . $history->invoice_id],
            ];
        }

        $keyboard[] = [
            ['text' => '👨‍💼 Hubungi Admin', 'url' => $config->bot['contact'] ?? 'https://t.me/username_admin'],
        ];

        $keyboard[] = [
            ['text' => '📜 Riwayat Transaksi', 'callback_data' => 'menu_history'],
            ['text' => '🏠 Menu Utama', 'callback_data' => 'menu_start'],
        ];

        $chat_id = $message['chat']['id'] ?? null;
        $qr_url  = $detail['qr_url'] ?? null;

        if (! empty($qr_url)) {
            if ($callbackQueryId && isset($message['message_id'])) {
                deleteMessage($chat_id, $message['message_id']);
            }
            return sendPhoto(
                $chat_id,
                $qr_url,
                $caption,
                $keyboard,
                'HTML'
            );
        }

        if ($callbackQueryId) {
            return editMessageOrCaption($message, $caption, $keyboard);
        }

        return sendMessage([
            'chat_id'  => $chat_id,
            'text'     => $caption,
            'mode'     => 'HTML',
            'keyboard' => $keyboard,
        ]);
    }

    private function answerCallbackQuery($callback_query_id = null, $text = null, $show_alert = false)
    {
        $params = [
            'callback_query_id' => $callback_query_id,
            'show_alert'        => $show_alert,
        ];

        if ($text) {
            $params['text'] = $text;
        }

        return sendRequest('answerCallbackQuery', $params);
    }

    private function random($length = 0, $string = null)
    {
        $string           = $string != null ? $string : '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $characters       = $string;
        $charactersLength = strlen($characters);
        $randomString     = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public function handleUserSession(string $message, int $chatID, array $dataMessage, $user = null, $config = null)
    {
        if ($user && str_starts_with($user->session, 'admin_')) {
            return (new HandleCallbackAdmin())->handleAdminSession($message, $chatID, $dataMessage, $user, $config);
        }

        if ($user && str_starts_with($user->session, 'reset_license:')) {
            $providerId = str_replace('reset_license:', '', $user->session);
            $provider   = Provider::where([['id', $providerId], ['reset_license', 'enabled'], ['status', 'active']])->first();
            if (! $provider) {
                $user->session = null;
                $user->save();
                return sendMessage(['chat_id' => $chatID, 'text' => 'Provider tidak aktif / fitur license tidak tersedia', 'mode' => 'html']);
            }

            if ($provider->type_api == '1') {
                $api    = new Apiv1();
                $data   = ['url' => $provider->url['reset'], 'license' => $message, 'action' => 'reset', 'api_key' => $provider->api_key];
                $result = $api->reset($data);
                if (isset($result['status'])) {
                    if ($result['status'] == false) {
                        return sendMessage(['chat_id', $chatID, 'text' => 'Gagal mereset license']);
                    }
                    return sendMessage(['chat_id', $chatID, 'text' => 'Berhasil mereset license: ' . $message]);
                } else {
                    return sendMessage(['chat_id', $chatID, 'text' => 'Gagal mereset license']);
                }
            } else if ($provider->type_api == '2') {
                $api    = new Apiv2();
                $data   = ['url' => $provider->url, 'member_key' => $message, 'api_key' => $provider->api_key];
                $result = $api->resetlicense($data);
                if (isset($result['success'])) {
                    if ($result['success'] == false) {
                        return sendMessage(['chat_id', $chatID, 'text' => 'Gagal mereset license']);
                    }
                    return sendMessage(['chat_id', $chatID, 'text' => 'Berhasil mereset license: ' . $message]);
                } else {
                    return sendMessage(['chat_id', $chatID, 'text' => 'Gagal mereset license']);
                }
            } else {
                return response()->json(['status' => false, 'message' => 'Fitur reset license tidak diaktifkan']);
            }
        }
    }
}
