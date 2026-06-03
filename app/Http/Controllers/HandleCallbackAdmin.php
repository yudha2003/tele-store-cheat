<?php

namespace App\Http\Controllers;

use App\Libraries\Apiv1;
use App\Libraries\Apiv2;
use App\Models\Config;
use App\Models\Denom;
use App\Models\Download;
use App\Models\Game;
use App\Models\History;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\Stock;

use App\Models\User;
use Exception;

class HandleCallbackAdmin extends Controller
{
    public function handle(string $callbackData, array $data, $user = null, $config = null)
    {
        try {
            if (! isset($data['data'])) {
                return;
            }
            $callbackData = $data['data'];

            if ($user && str_starts_with($user->session, 'admin_')) {
                if (
                    !str_starts_with($callbackData, 'menu_admin:provider_create_type') &&
                    !str_starts_with($callbackData, 'menu_admin:provider_create_reset')
                ) {
                    $user->session = null;
                    $user->save();
                }
            }

            if (! $user || $user->role !== 'admin') {
                return;
            }

            return match (true) {
                $callbackData === 'menu_admin'                                         => $this->handleButtonMenu($data, $user, $config),
                $callbackData === 'menu_admin:config'                                  => $this->handleConfigMenu($data, $user, $config),
                $callbackData === 'menu_admin:config:order'                            => $this->handleConfigOrder($data, $user, $config),
                $callbackData === 'menu_admin:config:order:custom_notes'               => $this->handleConfigOrderCustomNotes($data, $user, $config),
                $callbackData === 'menu_admin:config:bot'                              => $this->handleConfigBot($data, $user, $config),
                $callbackData === 'menu_admin:config:payments'                         => $this->handleConfigPayments($data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:config:payments:detail:')   => $this->handleConfigPaymentsDetail($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:config:payments:toggle:')   => $this->handleConfigPaymentsToggle($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:config:payments:edit:')     => $this->handleConfigPaymentsEditPrompt($callbackData, $data, $user, $config),
                $callbackData === 'menu_admin:config:captions'                         => $this->handleConfigCaptions($data, $user, $config),
                $callbackData === 'menu_admin:config:captions:orders'                  => $this->handleCaptionsList('orders', $data, $user, $config),
                $callbackData === 'menu_admin:config:captions:others'                  => $this->handleCaptionsList('others_button', $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:view_caption:')             => $this->handleViewCaption($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:edit_caption:')             => $this->handleEditCaptionPrompt($callbackData, $data, $user, $config),

                $callbackData === 'menu_admin:game'                                    => $this->handleGamesList($data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:game_detail:')              => $this->handleGameDetail($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:game_delete:')              => $this->handleGameDeleteConfirm($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:game_destroy:')             => $this->handleGameDestroy($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:game_toggle:')              => $this->handleGameToggle($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:game_providers:')           => $this->handleGameProviders($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:game_connect_provider:')    => $this->handleGameConnectProvider($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:game_disconnect_provider:') => $this->handleGameDisconnectProvider($callbackData, $data, $user, $config),
                $callbackData === 'menu_admin:game_create'                             => $this->handleGameCreatePrompt($data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:game_edit:')                => $this->handleGameEditPrompt($callbackData, $data, $user, $config),

                $callbackData === 'menu_admin:provider'                                => $this->handleProvidersList($data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:provider_detail:')          => $this->handleProviderDetail($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:provider_stock:')           => $this->handleProviderStockList($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:provider_delete:')          => $this->handleProviderDeleteConfirm($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:provider_destroy:')         => $this->handleProviderDestroy($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:provider_toggle:')          => $this->handleProviderToggle($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:provider_endpoints:')       => $this->handleProviderEndpointsMenu($callbackData, $data, $user, $config),
                $callbackData === 'menu_admin:provider_create'                         => $this->handleProviderCreatePrompt($data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:provider_create_type:')     => $this->handleProviderCreateTypeCallback($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:provider_create_reset:')    => $this->handleProviderCreateResetCallback($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:provider_edit:')            => $this->handleProviderEditPrompt($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:provider_custom_data:')     => $this->handleProviderCustomDataList($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:provider_cd_add_game:')     => $this->handleProviderCDAddGame($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:provider_cd_add_key:')      => $this->handleProviderCDAddKey($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:pr_cd_fetch:')              => $this->handleProviderCDFetchProvider($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:pr_cd_sf:')                 => $this->handleProviderCDSaveFetched($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:provider_cd_prompt_val:')   => $this->handleProviderCDPromptVal($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:provider_cd_delete:')       => $this->handleProviderCDDelete($callbackData, $data, $user, $config),

                // Denom CRUD Callbacks
                $callbackData === 'menu_admin:denom'                                   => $this->handleDenomGames($data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:denom_game:')               => $this->handleDenomProviders($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:denom_list:')               => $this->handleDenomsList($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:denom_detail:')             => $this->handleDenomDetail($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:denom_toggle:')             => $this->handleDenomToggle($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:denom_delete:')             => $this->handleDenomDeleteConfirm($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:denom_destroy:')            => $this->handleDenomDestroy($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:denom_create:')             => $this->handleDenomCreatePrompt($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:denom_edit:')               => $this->handleDenomEditPrompt($callbackData, $data, $user, $config),

                // Stock Management Callbacks
                str_starts_with($callbackData, 'menu_admin:stock:')                    => $this->handleStockCallback($callbackData, $data, $user, $config),
                // History CRUD Callbacks
                $callbackData === 'menu_admin:history'                                 => $this->handleHistoryList('menu_admin:history_list:all:1', $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:history_list:')             => $this->handleHistoryList($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:history_detail:')           => $this->handleHistoryDetail($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:history_status:')           => $this->handleHistoryStatus($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:history_delete:')           => $this->handleHistoryDeleteConfirm($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:history_destroy:')          => $this->handleHistoryDestroy($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:history_edit_notes:')       => $this->handleHistoryEditNotesPrompt($callbackData, $data, $user, $config),

                // Payment CRUD Callbacks
                $callbackData === 'menu_admin:payment'                                 => $this->handlePaymentsList('menu_admin:payment_list:all:1', $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:payment_list:')             => $this->handlePaymentsList($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:payment_detail:')           => $this->handlePaymentDetail($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:payment_toggle:')           => $this->handlePaymentToggle($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:payment_delete:')           => $this->handlePaymentDeleteConfirm($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:payment_destroy:')          => $this->handlePaymentDestroy($callbackData, $data, $user, $config),
                $callbackData === 'menu_admin:payment_create'                          => $this->handlePaymentCreatePrompt($data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:payment_create_provider:')  => $this->handlePaymentCreateProviderCallback($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:payment_edit:')             => $this->handlePaymentEditPrompt($callbackData, $data, $user, $config),

                $callbackData === 'menu_admin:user'                                    => $this->handleUserList('menu_admin:user_list:all:1', $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:user_list:')                => $this->handleUserList($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:user_detail:')              => $this->handleUserDetail($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:user_role:')                => $this->handleUserToggleRole($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:user_delete:')              => $this->handleUserDeleteConfirm($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:user_destroy:')             => $this->handleUserDestroy($callbackData, $data, $user, $config),
                $callbackData === 'menu_admin:broadcast'                               => $this->handleBroadcastPrompt($data, $user, $config),

                $callbackData === 'menu_admin:download'                                => $this->handleDownloadsList('menu_admin:download_list:all:1', $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:download_list:')            => $this->handleDownloadsList($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:download_detail:')          => $this->handleDownloadDetail($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:download_toggle:')          => $this->handleDownloadToggle($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:download_delete:')          => $this->handleDownloadDeleteConfirm($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:download_destroy:')         => $this->handleDownloadDestroy($callbackData, $data, $user, $config),
                $callbackData === 'menu_admin:download_create'                         => $this->handleDownloadCreatePrompt($data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:download_create_game:')     => $this->handleDownloadCreateGameCallback($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:download_create_provider:') => $this->handleDownloadCreateProviderCallback($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:download_edit:')            => $this->handleDownloadEditPrompt($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:download_edit_game:')       => $this->handleDownloadEditGameCallback($callbackData, $data, $user, $config),
                str_starts_with($callbackData, 'menu_admin:download_edit_provider:')   => $this->handleDownloadEditProviderCallback($callbackData, $data, $user, $config),

                str_starts_with($callbackData, 'menu_admin:edit:')                     => $this->handleEditPrompt($callbackData, $data, $user, $config),
                default                                                                => null
            };
        } catch (Exception $e) {
            return json_encode([
                'status'  => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function handleButtonMenu(array $data, $user = null, $config = null)
    {
        $keyboard = [
            [
                ['text' => '⚙️ Konfigurasi Bot', 'callback_data' => 'menu_admin:config'],
                ['text' => '💳 Kelola Payment', 'callback_data' => 'menu_admin:payment'],
            ],
            [
                ['text' => '👥 Kelola User', 'callback_data' => 'menu_admin:user'],
                ['text' => '📢 Broadcast', 'callback_data' => 'menu_admin:broadcast'],
            ],
            [
                ['text' => '🎮 Kelola Game', 'callback_data' => 'menu_admin:game'],
                ['text' => '🏢 Kelola Provider', 'callback_data' => 'menu_admin:provider'],
            ],
            [
                ['text' => '📦 Kelola Denom', 'callback_data' => 'menu_admin:denom'],
                ['text' => '📜 Kelola History', 'callback_data' => 'menu_admin:history'],
            ],
            [
                ['text' => '📥 Kelola Download', 'callback_data' => 'menu_admin:download'],
            ],
            [
                ['text' => '🏠 Menu Utama', 'callback_data' => 'menu_start'],
            ],
        ];

        return editMessageOrCaption(
            $data['message'] ?? [],
            "👨‍💼 <b>Menu Panel Admin</b>\n\nSilakan pilih kategori konfigurasi di bawah ini untuk mengelola bot langsung dari Telegram:",
            $keyboard
        );
    }

    private function handleConfigMenu(array $data, $user = null, $config = null)
    {
        $keyboard = [
            [
                ['text' => '📦 Pengaturan Transaksi', 'callback_data' => 'menu_admin:config:order'],
            ],
            [
                ['text' => '🤖 Informasi Bot', 'callback_data' => 'menu_admin:config:bot'],
            ],
            [
                ['text' => '💬 Caption & Pesan', 'callback_data' => 'menu_admin:config:captions'],
            ],
            [
                ['text' => '💳 Payment Gateway', 'callback_data' => 'menu_admin:config:payments'],
            ],
            [
                ['text' => '⬅️ Kembali ke Menu Admin', 'callback_data' => 'menu_admin'],
            ],
        ];

        return editMessageOrCaption(
            $data['message'] ?? [],
            "⚙️ <b>Pengaturan Konfigurasi Bot</b>\n\nPilih salah satu kategori konfigurasi di bawah ini untuk melihat detail dan mengedit nilainya:",
            $keyboard
        );
    }

    private function handleConfigOrder(array $data, $user = null, $config = null)
    {
        $order   = $config->order ?? [];
        $string  = $order['string'] ?? '-';
        $prefix  = $order['prefix_order'] ?? '-';
        $length  = $order['length_random_order'] ?? '-';
        $exp     = $order['exp_order'] ?? '-';
        $pending = $order['count_pending'] ?? '-';
        $delay   = $order['transaksi_delay'] ?? '-';

        $caption = "📦 <b>Pengaturan Transaksi / Pesanan</b>\n\n" .
            "Berikut adalah nilai konfigurasi pesanan saat ini:\n\n" .
            "• <b>Karakter Acak Invoice:</b> <code>{$string}</code>\n" .
            "• <b>Prefix ID Invoice:</b> <code>{$prefix}</code>\n" .
            "• <b>Panjang ID Invoice Acak:</b> <code>{$length}</code> karakter\n" .
            "• <b>Masa Berlaku Invoice:</b> <code>{$exp}</code> menit\n" .
            "• <b>Batas Transaksi Pending:</b> <code>{$pending}</code> kali\n" .
            "• <b>Jeda Antar Transaksi:</b> <code>{$delay}</code> detik\n\n" .
            "Silakan pilih tombol di bawah untuk mengedit masing-masing nilai:";

        $keyboard = [
            [
                ['text' => '🔡 Edit String', 'callback_data' => 'menu_admin:edit:order:string'],
                ['text' => '⏱️ Edit Exp Order', 'callback_data' => 'menu_admin:edit:order:exp_order'],
            ],
            [
                ['text' => '🏷️ Edit Prefix', 'callback_data' => 'menu_admin:edit:order:prefix_order'],
                ['text' => '🔢 Edit Count Pending', 'callback_data' => 'menu_admin:edit:order:count_pending'],
            ],
            [
                ['text' => '⏳ Edit Delay', 'callback_data' => 'menu_admin:edit:order:transaksi_delay'],
                ['text' => '📏 Edit Random Length', 'callback_data' => 'menu_admin:edit:order:length_random_order'],
            ],
            [
                ['text' => '📝 Kelola Custom Notes', 'callback_data' => 'menu_admin:config:order:custom_notes'],
            ],
            [
                ['text' => '⬅️ Kembali', 'callback_data' => 'menu_admin:config'],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handleConfigOrderCustomNotes(array $data, $user = null, $config = null)
    {
        $order       = $config->order ?? [];
        $customNotes = $order['custom_notes'] ?? [];
        $successNote = $customNotes['success'] ?? 'Belum diatur';

        $caption = "📝 <b>Pengaturan Custom Notes</b>\n\n" .
            "Berikut adalah custom notes yang tersedia saat ini:\n\n" .
            "• <b>Custom Notes Success:</b>\n" .
            "<pre>" . htmlspecialchars($successNote, ENT_QUOTES, 'UTF-8') . "</pre>\n\n" .
            "Pilih tombol di bawah untuk mengedit catatan tersebut:";

        $keyboard = [
            [
                ['text' => '✍️ Edit Success Note', 'callback_data' => 'menu_admin:edit:order:custom_notes:success'],
            ],
            [
                ['text' => '⬅️ Kembali ke Pengaturan Pesanan', 'callback_data' => 'menu_admin:config:order'],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handleConfigBot(array $data, $user = null, $config = null)
    {
        $bot     = $config->bot ?? [];
        $image   = $bot['image'] ?? '-';
        $contact = $bot['contact'] ?? '-';

        $caption = "🤖 <b>Informasi & Tampilan Bot</b>\n\n" .
            "Berikut adalah detail informasi bot saat ini:\n\n" .
            "• <b>Gambar Utama (Start Command):</b>\n<code>{$image}</code>\n\n" .
            "• <b>Kontak Admin:</b>\n{$contact}\n\n" .
            "Silakan pilih tombol di bawah untuk mengubah:";

        $keyboard = [
            [
                ['text' => '🖼️ Edit Gambar Utama', 'callback_data' => 'menu_admin:edit:bot:image'],
            ],
            [
                ['text' => '👤 Edit Link Kontak', 'callback_data' => 'menu_admin:edit:bot:contact'],
            ],
            [
                ['text' => '⬅️ Kembali', 'callback_data' => 'menu_admin:config'],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handleConfigPayments(array $data, $user = null, $config = null)
    {
        $payments = $config->payments ?? [];

        $caption = "💳 <b>Pengaturan Payment Gateways</b>\n\n" .
            "Berikut adalah daftar payment gateway yang terdaftar:\n\n";

        $buttons = [];
        foreach ($payments as $gateway => $settings) {
            $statusEmoji = ($settings['status'] ?? false) ? '🟢' : '🔴';
            $displayName = ucfirst($gateway);

            $caption .= "• <b>{$displayName}:</b> " . (($settings['status'] ?? false) ? 'Aktif' : 'Nonaktif') . "\n";

            $buttons[] = [
                'text' => "⚙️ {$displayName} [{$statusEmoji}]",
                'callback_data' => "menu_admin:config:payments:detail:{$gateway}",
            ];
        }

        $caption .= "\nSilakan pilih salah satu payment gateway untuk mengelola konfigurasi detailnya:";

        $keyboard   = array_chunk($buttons, 2);
        $keyboard[] = [
            ['text' => '⬅️ Kembali', 'callback_data' => 'menu_admin:config'],
        ];

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handleConfigPaymentsDetail(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts   = explode(':', $callbackData);
        $gateway = $parts[4] ?? '';

        $payments = $config->payments ?? [];
        if (! isset($payments[$gateway])) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Payment gateway tidak ditemukan.');
            }
            return;
        }

        $settings    = $payments[$gateway];
        $status      = $settings['status'] ?? false;
        $statusBadge = $status ? '🟢' : '🔴';
        $toggleText  = $status ? '🔌 Nonaktifkan' : '🔌 Aktifkan';

        $caption = "💳 <b>Detail Payment Gateway: " . ucfirst($gateway) . "</b>\n\n" .
            "• <b>Status:</b> " . ($status ? '🟢 Aktif' : '🔴 Nonaktif') . "\n";

        $editButtons = [];
        foreach ($settings as $key => $value) {
            if ($key === 'status') {
                continue;
            }
            $labelVal  = (is_bool($value) ? ($value ? 'true' : 'false') : $value);
            $caption  .= "• <b>" . str_replace('_', ' ', ucfirst($key)) . ":</b> <code>" . htmlspecialchars($labelVal ?? '-', ENT_QUOTES, 'UTF-8') . "</code>\n";

            $editButtons[] = [
                'text'          => '✍️ Edit ' . str_replace('_', ' ', ucfirst($key)),
                'callback_data' => "menu_admin:config:payments:edit:{$gateway}:{$key}",
            ];
        }

        $keyboard   = [];
        $keyboard[] = [
            ['text' => $toggleText, 'callback_data' => "menu_admin:config:payments:toggle:{$gateway}"],
        ];

        $chunkedEdit = array_chunk($editButtons, 2);
        foreach ($chunkedEdit as $row) {
            $keyboard[] = $row;
        }

        $keyboard[] = [
            ['text' => '⬅️ Kembali ke Daftar', 'callback_data' => 'menu_admin:config:payments'],
        ];

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handleConfigPaymentsToggle(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts   = explode(':', $callbackData);
        $gateway = $parts[4] ?? '';

        $payments = $config->payments ?? [];
        if (! isset($payments[$gateway])) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Payment gateway tidak ditemukan.');
            }
            return;
        }

        $payments[$gateway]['status'] = ! ($payments[$gateway]['status'] ?? false);
        $config->payments             = $payments;
        $config->save();

        if (isset($data['id'])) {
            $statusStr = $payments[$gateway]['status'] ? 'diaktifkan' : 'dinonaktifkan';
            $this->answerCallbackQuery($data['id'], "✅ " . ucfirst($gateway) . " berhasil {$statusStr}!");
        }

        return $this->handleConfigPaymentsDetail($callbackData, $data, $user, $config);
    }

    private function handleConfigPaymentsEditPrompt(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts   = explode(':', $callbackData);
        $gateway = $parts[4] ?? '';
        $key     = $parts[5] ?? '';

        $payments = $config->payments ?? [];
        if (! isset($payments[$gateway][$key])) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Key tidak ditemukan.');
            }
            return;
        }

        $user->session = "admin_edit:config:payments:{$gateway}:{$key}";
        $user->save();

        $label  = ucfirst($gateway) . ' ' . str_replace('_', ' ', ucfirst($key));
        $prompt = "✍️ Silakan kirimkan nilai baru untuk <b>{$label}</b> di chat ini.\n\n" .
            "⚠️ <i>Klik tombol Batal di bawah untuk membatalkan perubahan.</i>";

        $keyboard = [
            [
                ['text' => '❌ Batal', 'callback_data' => "menu_admin:config:payments:detail:{$gateway}"],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $prompt, $keyboard);
    }

    private function handleConfigCaptions(array $data, $user = null, $config = null)
    {
        $keyboard = [
            [
                ['text' => '🛒 Caption Terkait Pesanan', 'callback_data' => 'menu_admin:config:captions:orders'],
            ],
            [
                ['text' => '📋 Caption Terkait Menu/Tombol Lain', 'callback_data' => 'menu_admin:config:captions:others'],
            ],
            [
                ['text' => '⬅️ Kembali', 'callback_data' => 'menu_admin:config'],
            ],
        ];

        return editMessageOrCaption(
            $data['message'] ?? [],
            "💬 <b>Pengaturan Caption & Pesan</b>\n\nSilakan pilih kategori caption yang ingin Anda kelola:",
            $keyboard
        );
    }

    private function handleCaptionsList(string $type, array $data, $user = null, $config = null)
    {
        $captionMap = ($type === 'orders') ? [
            'menu_start'           => '👋 Menu Start',
            'menu_order'           => '🎮 Pilih Game',
            'menu_providers'       => '🏢 Pilih Provider',
            'menu_denoms'          => '📦 Pilih Denom',
            'menu_confirm_order'   => '🛒 Konfirmasi Order',
            'invoice_order'        => '🧾 Tampilan Invoice',
            'cancel_order'         => '❌ Pesanan Batal',
            'confirm_cancel_order' => '⚠️ Konfirmasi Batal',
        ] : [
            'menu_history'             => '📜 Ada Riwayat',
            'menu_history_empty'       => '📜 Riwayat Kosong',
            'menu_account'             => '👤 Info Akun',
            'menu_leaderboard_weekly'  => '🏆 Leaderboard Mingguan',
            'menu_leaderboard_monthly' => '🏆 Leaderboard Bulanan',
            'menu_announcement'        => '📢 Pengumuman',
            'menu_resetlicense'        => '🔑 Menu Reset Lisensi',
            'menu_select_resetlicense' => '✍️ Input Reset Lisensi',
        ];

        $buttons = [];
        foreach ($captionMap as $key => $label) {
            $buttons[] = [
                'text'          => $label,
                'callback_data' => "menu_admin:view_caption:{$type}:{$key}",
            ];
        }

        $keyboard   = array_chunk($buttons, 2);
        $keyboard[] = [
            ['text' => '⬅️ Kembali', 'callback_data' => 'menu_admin:config:captions'],
        ];

        $title   = ($type === 'orders') ? '🛒 Caption Alur Pemesanan' : '📋 Caption Menu & Tombol Lainnya';
        $caption = "<b>{$title}</b>\n\nPilih salah satu pesan di bawah ini untuk melihat detail isi atau mengeditnya:";

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handleViewCaption(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts = explode(':', $callbackData);
        $type  = $parts[2] ?? '';
        $key   = $parts[3] ?? '';

        $captions = $config->captions ?? [];
        $list     = $captions[$type] ?? [];
        $item     = collect($list)->firstWhere('key', $key);
        $content  = $item['content'] ?? 'Tidak diatur';

        $captionText = "🔍 <b>Detail Caption</b>\n\n" .
            "• <b>Key:</b> <code>{$key}</code>\n" .
            "• <b>Kategori:</b> <code>{$type}</code>\n\n" .
            "<b>Isi Caption Saat Ini:</b>\n" .
            "<pre>" . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . "</pre>\n\n" .
            "Silakan klik tombol di bawah untuk mengedit caption ini:";

        $keyboard = [
            [
                ['text' => '✍️ Edit Isi Caption', 'callback_data' => "menu_admin:edit_caption:{$type}:{$key}"],
            ],
            [
                ['text' => '⬅️ Kembali', 'callback_data' => ($type === 'orders' ? 'menu_admin:config:captions:orders' : 'menu_admin:config:captions:others')],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $captionText, $keyboard);
    }

    private function handleEditPrompt(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts    = explode(':', $callbackData);
        $category = $parts[2] ?? '';
        $field    = $parts[3] ?? '';
        $subfield = $parts[4] ?? '';

        // Set session
        $session = "admin_edit:config:{$category}:{$field}";
        if ($subfield !== '') {
            $session .= ":{$subfield}";
        }
        $user->session = $session;
        $user->save();

        $fieldNameMap  = [
            'string'              => 'Karakter Acak Invoice (String)',
            'exp_order'           => 'Masa Berlaku Invoice (menit)',
            'prefix_order'        => 'Prefix ID Invoice',
            'count_pending'       => 'Batas Transaksi Pending',
            'transaksi_delay'     => 'Jeda Antar Transaksi (detik)',
            'length_random_order' => 'Panjang ID Invoice Acak',
            'image'               => 'URL Gambar Utama',
            'contact'             => 'Link Kontak Admin',
            'api_key'             => 'API Key Wijayapay',
            'code_merchant'       => 'Code Merchant Wijayapay',
            'custom_notes'        => 'Custom Notes Success',
        ];

        $label  = $fieldNameMap[$field] ?? $field;
        $prompt = "✍️ Silakan kirimkan nilai baru untuk <b>{$label}</b> di chat ini.";
        if ($field === 'image') {
            $prompt .= "\n💡 Anda juga bisa langsung mengirimkan file gambar/foto di chat ini untuk diunggah otomatis.";
        }
        $prompt .= "\n\n⚠️ <i>Klik tombol Batal di bawah untuk membatalkan perubahan.</i>";

        $backCallback = "menu_admin:config:{$category}";
        if ($field === 'custom_notes') {
            $backCallback = "menu_admin:config:{$category}:custom_notes";
        }

        $keyboard = [
            [
                ['text' => '❌ Batal', 'callback_data' => $backCallback],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $prompt, $keyboard);
    }

    private function handleEditCaptionPrompt(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts = explode(':', $callbackData);
        $type  = $parts[2] ?? '';
        $key   = $parts[3] ?? '';

        // Set session
        $user->session = "admin_edit:caption:{$type}:{$key}";
        $user->save();

        $prompt = "✍️ Silakan kirimkan isi <b>Caption Baru</b> untuk key <code>{$key}</code> di chat ini.\n\n" .
            "Anda dapat menggunakan format HTML dan placeholder yang didukung seperti:\n" .
            "• <code>{firstname}</code>, <code>{invoice_id}</code>, <code>{price}</code>, dll (sesuai kegunaan caption).\n\n" .
            "⚠️ <i>Klik tombol Batal di bawah untuk membatalkan perubahan.</i>";

        $keyboard = [
            [
                ['text' => '❌ Batal', 'callback_data' => "menu_admin:view_caption:{$type}:{$key}"],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $prompt, $keyboard);
    }

    // ==========================================
    // GAMES CRUD FLOWS
    // ==========================================

    private function handleGamesList(array $data, $user = null, $config = null)
    {
        $games = Game::all();

        $buttons = $games->map(function ($row) {
            $statusEmoji = ($row->status === 'active') ? '🟢' : '🔴';
            return [
                'text'          => $row->name . ' [' . $statusEmoji . ']',
                'callback_data' => 'menu_admin:game_detail:' . $row->id,
            ];
        })->toArray();

        $keyboard   = array_chunk($buttons, 2);
        $keyboard[] = [
            ['text' => '➕ Buat Game Baru', 'callback_data' => 'menu_admin:game_create'],
        ];
        $keyboard[] = [
            ['text' => '⬅️ Kembali', 'callback_data' => 'menu_admin'],
        ];

        return editMessageOrCaption(
            $data['message'] ?? [],
            "🎮 <b>Kelola Game</b>\n\nSilakan pilih game di bawah ini untuk melihat detail/mengubah, atau buat game baru:",
            $keyboard
        );
    }

    private function handleGameDetail(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts  = explode(':', $callbackData);
        $gameId = $parts[2] ?? null;

        $game = Game::find($gameId);
        if (! $game) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Game tidak ditemukan.');
            }
            return;
        }

        $statusBadge = ($game->status === 'active') ? '🟢 Aktif' : '🔴 Nonaktif';

        $providerIds        = $game->providers ?? [];
        $connectedProviders = Provider::whereIn('id', $providerIds)->pluck('name')->toArray();
        $providerNamesList  = empty($connectedProviders) ? 'Tidak ada provider terhubung' : implode(', ', $connectedProviders);

        $caption = "🎮 <b>Detail Game</b>\n\n" .
            "• <b>Nama Game:</b> {$game->name}\n" .
            "• <b>Kode Game:</b> <code>{$game->code}</code>\n" .
            "• <b>Status:</b> {$statusBadge}\n" .
            "• <b>Provider Terhubung:</b> {$providerNamesList}";

        $keyboard = [
            [
                ['text' => '✍️ Edit Nama', 'callback_data' => "menu_admin:game_edit:name:{$gameId}"],
                ['text' => '✍️ Edit Kode', 'callback_data' => "menu_admin:game_edit:code:{$gameId}"],
            ],
            [
                ['text' => '🔌 Toggle Status', 'callback_data' => "menu_admin:game_toggle:{$gameId}"],
                ['text' => '🔗 Hubungkan Provider', 'callback_data' => "menu_admin:game_providers:{$gameId}"],
            ],
            [
                ['text' => '🗑️ Hapus Game', 'callback_data' => "menu_admin:game_delete:{$gameId}"],
            ],
            [
                ['text' => '⬅️ Kembali ke Daftar', 'callback_data' => 'menu_admin:game'],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handleGameToggle(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts  = explode(':', $callbackData);
        $gameId = $parts[2] ?? null;

        $game = Game::find($gameId);
        if (! $game) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Game tidak ditemukan.');
            }
            return;
        }

        $game->status = ($game->status === 'active') ? 'inactive' : 'active';
        $game->save();

        if (isset($data['id'])) {
            $statusStr = ($game->status === 'active') ? 'diaktifkan' : 'dinonaktifkan';
            $this->answerCallbackQuery($data['id'], "✅ Game berhasil {$statusStr}!");
        }

        return $this->handleGameDetail($callbackData, $data, $user, $config);
    }

    private function handleGameDeleteConfirm(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts  = explode(':', $callbackData);
        $gameId = $parts[2] ?? null;

        $game = Game::find($gameId);
        if (! $game) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Game tidak ditemukan.');
            }
            return;
        }

        $caption = "⚠️ <b>Konfirmasi Hapus Game</b>\n\n" .
            "Apakah Anda yakin ingin menghapus game <b>{$game->name}</b>?\n\n" .
            "<i>Tindakan ini tidak dapat dibatalkan dan akan menghapus semua denom yang terhubung ke game ini!</i>";

        $keyboard = [
            [
                ['text' => '🔴 Ya, Hapus', 'callback_data' => "menu_admin:game_destroy:{$gameId}"],
                ['text' => '🟢 Batal', 'callback_data' => "menu_admin:game_detail:{$gameId}"],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handleGameDestroy(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts  = explode(':', $callbackData);
        $gameId = $parts[2] ?? null;

        $game = Game::find($gameId);
        if (! $game) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Game tidak ditemukan.');
            }
            return;
        }

        Denom::where('game_id', $gameId)->delete();
        $game->delete();

        if (isset($data['id'])) {
            $this->answerCallbackQuery($data['id'], '✅ Game berhasil dihapus!');
        }

        return $this->handleGamesList($data, $user, $config);
    }

    private function handleGameProviders(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts  = explode(':', $callbackData);
        $gameId = $parts[2] ?? null;

        $game = Game::find($gameId);
        if (! $game) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Game tidak ditemukan.');
            }
            return;
        }

        $connectedIds = $game->providers ?? [];
        $allProviders = Provider::all();

        $buttons = $allProviders->map(function ($prov) use ($gameId, $connectedIds) {
            $isConnected = in_array($prov->id, $connectedIds);
            $emoji       = $isConnected ? '✅' : '❌';
            $action      = $isConnected ? 'game_disconnect_provider' : 'game_connect_provider';

            return [
                'text'          => $prov->name . ' [' . $emoji . ']',
                'callback_data' => "menu_admin:{$action}:{$gameId}:{$prov->id}",
            ];
        })->toArray();

        $keyboard   = array_chunk($buttons, 2);
        $keyboard[] = [
            ['text' => '⬅️ Kembali ke Detail', 'callback_data' => "menu_admin:game_detail:{$gameId}"],
        ];

        return editMessageOrCaption(
            $data['message'] ?? [],
            "🔗 <b>Hubungkan Provider - {$game->name}</b>\n\nSilakan klik provider di bawah untuk menghubungkan atau memutuskan koneksi:",
            $keyboard
        );
    }

    private function handleGameConnectProvider(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $gameId     = $parts[2] ?? null;
        $providerId = intval($parts[3] ?? 0);

        $game = Game::find($gameId);
        if ($game) {
            $providers = $game->providers ?? [];
            if (! in_array($providerId, $providers)) {
                $providers[]     = $providerId;
                $game->providers = array_values($providers);
                $game->save();
            }
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], '✅ Provider terhubung.');
            }
        }

        return $this->handleGameProviders("menu_admin:game_providers:{$gameId}", $data, $user, $config);
    }

    private function handleGameDisconnectProvider(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $gameId     = $parts[2] ?? null;
        $providerId = intval($parts[3] ?? 0);

        $game = Game::find($gameId);
        if ($game) {
            $providers = $game->providers ?? [];
            $providers = array_filter($providers, function ($id) use ($providerId) {
                return $id != $providerId;
            });
            $game->providers = array_values($providers);
            $game->save();

            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], '❌ Sambungan diputuskan.');
            }
        }

        return $this->handleGameProviders("menu_admin:game_providers:{$gameId}", $data, $user, $config);
    }

    private function handleGameCreatePrompt(array $data, $user = null, $config = null)
    {
        $user->session = "admin_create_game:name";
        $user->save();

        $prompt = "✍️ Silakan kirimkan <b>Nama Game Baru</b> (contoh: <code>Free Fire</code>):\n\n" .
            "⚠️ <i>Klik tombol Batal di bawah untuk membatalkan.</i>";

        $keyboard = [
            [
                ['text' => '❌ Batal', 'callback_data' => 'menu_admin:game'],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $prompt, $keyboard);
    }

    private function handleGameEditPrompt(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts  = explode(':', $callbackData);
        $field  = $parts[2] ?? '';
        $gameId = $parts[3] ?? null;

        $game = Game::find($gameId);
        if (! $game) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Game tidak ditemukan.');
            }
            return;
        }

        $user->session = "admin_edit_game:{$field}:{$gameId}";
        $user->save();

        $fieldLabel = match ($field) {
            'name'  => 'Nama Game',
            'code'  => 'Kode Game (MLBB, FF, dll)',
            default => $field,
        };

        $prompt = "✍️ Silakan kirimkan <b>{$fieldLabel}</b> baru untuk game ini:\n\n" .
            "⚠️ <i>Klik tombol Batal di bawah untuk membatalkan.</i>";

        $keyboard = [
            [
                ['text' => '❌ Batal', 'callback_data' => "menu_admin:game_detail:{$gameId}"],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $prompt, $keyboard);
    }

    // ==========================================
    // PROVIDERS CRUD FLOWS
    // ==========================================

    private function handleProvidersList(array $data, $user = null, $config = null)
    {
        $providers = Provider::all();

        $buttons = $providers->map(function ($row) {
            $statusEmoji = ($row->status === 'active') ? '🟢' : '🔴';
            return [
                'text'          => $row->name . ' [' . $statusEmoji . ']',
                'callback_data' => 'menu_admin:provider_detail:' . $row->id,
            ];
        })->toArray();

        $keyboard   = array_chunk($buttons, 2);
        $keyboard[] = [
            ['text' => '➕ Buat Provider Baru', 'callback_data' => 'menu_admin:provider_create'],
        ];
        $keyboard[] = [
            ['text' => '⬅️ Kembali', 'callback_data' => 'menu_admin'],
        ];

        return editMessageOrCaption(
            $data['message'] ?? [],
            "🏢 <b>Kelola Provider</b>\n\nSilakan pilih provider di bawah ini untuk melihat detail/mengubah, atau buat provider baru:",
            $keyboard
        );
    }

    private function handleProviderDetail(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $providerId = $parts[2] ?? null;

        $provider = Provider::find($providerId);
        if (! $provider) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Provider tidak ditemukan.');
            }
            return;
        }

        $statusBadge = ($provider->status === 'active') ? '🟢 Aktif' : '🔴 Nonaktif';
        $resetBadge  = ($provider->reset_license === 'enabled') ? '🟢 Enabled' : '🔴 Disabled';

        $urlInfo = '';
        $urls    = $provider->url ?? [];
        if ($provider->type_api == 0) {
            $urlInfo .= "• (Sistem Stok tidak membutuhkan URL Endpoints)\n";
        } elseif ($provider->type_api == 1) {
            $urlInfo .= "• Register URL: <code>" . ($urls['register'] ?? '-') . "</code>\n";
            $urlInfo .= "• Get Game URL: <code>" . ($urls['get_game'] ?? '-') . "</code>\n";
            $urlInfo .= "• Reset Key URL: <code>" . ($urls['reset'] ?? '-') . "</code>\n";
        } elseif ($provider->type_api == 3) {
            $urlInfo .= "• Register URL: <code>" . ($urls['register'] ?? '-') . "</code>\n";
            $urlInfo .= "• Reset Key URL: <code>" . ($urls['reset'] ?? '-') . "</code>\n";
        } else {
            $urlInfo .= "• Register URL: <code>" . ($urls['register'] ?? '-') . "</code>\n";
            $urlInfo .= "• Game ID URL: <code>" . ($urls['game_id'] ?? '-') . "</code>\n";
            $urlInfo .= "• Paket URL: <code>" . ($urls['paket'] ?? '-') . "</code>\n";
            $urlInfo .= "• Reset Key URL: <code>" . ($urls['reset'] ?? '-') . "</code>\n";
            $urlInfo .= "• Edit Key URL: <code>" . ($urls['edit'] ?? '-') . "</code>\n";
        }

        $caption = "🏢 <b>Detail Provider</b>\n\n" .
            "• <b>Nama Provider:</b> {$provider->name}\n" .
            "• <b>API Key:</b> <code>{$provider->api_key}</code>\n" .
            "• <b>Type API:</b> " . ($provider->type_api == 0 ? '0 (Stok)' : $provider->type_api) . "\n" .
            "• <b>Reset License:</b> {$resetBadge}\n" .
            "• <b>Status:</b> {$statusBadge}\n\n" .
            "<b>Endpoints URL:</b>\n{$urlInfo}";

        $keyboard  = [
            [
                ['text' => '✍️ Edit Nama', 'callback_data' => "menu_admin:provider_edit:name:{$providerId}"],
                ['text' => '🔑 Edit API Key', 'callback_data' => "menu_admin:provider_edit:api_key:{$providerId}"],
            ],
            [
                ['text' => '⚙️ Edit Type API', 'callback_data' => "menu_admin:provider_edit:type_api:{$providerId}"],
                ['text' => '🔄 Edit Reset License', 'callback_data' => "menu_admin:provider_edit:reset_license:{$providerId}"],
            ],
            [
                ['text' => '🔗 Edit Endpoints URL', 'callback_data' => "menu_admin:provider_endpoints:{$providerId}"],
                ['text' => '🔌 Toggle Status', 'callback_data' => "menu_admin:provider_toggle:{$providerId}"],
            ],
            [
                ['text' => '🛠️ Kelola Custom Data', 'callback_data' => "menu_admin:provider_custom_data:{$providerId}"],
                ['text' => '🗑️ Hapus Provider', 'callback_data' => "menu_admin:provider_delete:{$providerId}"],
            ],
        ];

        if ($provider->type_api == 0) {
            $keyboard[] = [
                ['text' => '📦 Kelola Stok', 'callback_data' => "menu_admin:provider_stock:{$providerId}"],
            ];
        }

        $keyboard[] = [
            ['text' => '⬅️ Kembali ke Daftar', 'callback_data' => 'menu_admin:provider'],
        ];

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handleProviderToggle(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $providerId = $parts[2] ?? null;

        $provider = Provider::find($providerId);
        if (! $provider) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Provider tidak ditemukan.');
            }
            return;
        }

        $provider->status = ($provider->status === 'active') ? 'inactive' : 'active';
        $provider->save();

        if (isset($data['id'])) {
            $statusStr = ($provider->status === 'active') ? 'diaktifkan' : 'dinonaktifkan';
            $this->answerCallbackQuery($data['id'], "✅ Provider berhasil {$statusStr}!");
        }

        return $this->handleProviderDetail($callbackData, $data, $user, $config);
    }

    private function handleProviderDeleteConfirm(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $providerId = $parts[2] ?? null;

        $provider = Provider::find($providerId);
        if (! $provider) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Provider tidak ditemukan.');
            }
            return;
        }

        $caption = "⚠️ <b>Konfirmasi Hapus Provider</b>\n\n" .
            "Apakah Anda yakin ingin menghapus provider <b>{$provider->name}</b>?\n\n" .
            "<i>Tindakan ini tidak dapat dibatalkan dan akan menghapus seluruh denom terkait provider ini!</i>";

        $keyboard = [
            [
                ['text' => '🔴 Ya, Hapus', 'callback_data' => "menu_admin:provider_destroy:{$providerId}"],
                ['text' => '🟢 Batal', 'callback_data' => "menu_admin:provider_detail:{$providerId}"],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handleProviderDestroy(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $providerId = $parts[2] ?? null;

        $provider = Provider::find($providerId);
        if (! $provider) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Provider tidak ditemukan.');
            }
            return;
        }

        // Disconnect from games first
        $games = Game::all();
        foreach ($games as $game) {
            $providers = $game->providers ?? [];
            if (in_array($providerId, $providers)) {
                $providers = array_filter($providers, function ($id) use ($providerId) {
                    return $id != $providerId;
                });
                $game->providers = array_values($providers);
                $game->save();
            }
        }

        Denom::where('provider_id', $providerId)->delete();
        $provider->delete();

        if (isset($data['id'])) {
            $this->answerCallbackQuery($data['id'], '✅ Provider berhasil dihapus!');
        }

        return $this->handleProvidersList($data, $user, $config);
    }

    private function handleProviderEndpointsMenu(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $providerId = $parts[2] ?? null;

        $provider = Provider::find($providerId);
        if (! $provider) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Provider tidak ditemukan.');
            }
            return;
        }

        $buttons = [];
        if ($provider->type_api == 0) {
            // Sistem stok tidak memerlukan URL Endpoints
        } elseif ($provider->type_api == 1) {
            $buttons[] = [
                ['text' => 'Register URL', 'callback_data' => "menu_admin:provider_edit:url_register:{$providerId}"],
                ['text' => 'Get Game URL', 'callback_data' => "menu_admin:provider_edit:url_get_game:{$providerId}"],
            ];
            $buttons[] = [
                ['text' => 'Reset Key URL', 'callback_data' => "menu_admin:provider_edit:url_reset:{$providerId}"],
            ];
        } elseif ($provider->type_api == 3) {
            $buttons[] = [
                ['text' => 'Register URL', 'callback_data' => "menu_admin:provider_edit:url_register:{$providerId}"],
                ['text' => 'Reset Key URL', 'callback_data' => "menu_admin:provider_edit:url_reset:{$providerId}"],
            ];
        } else {
            $buttons[] = [
                ['text' => 'Register URL', 'callback_data' => "menu_admin:provider_edit:url_register:{$providerId}"],
                ['text' => 'Game ID URL', 'callback_data' => "menu_admin:provider_edit:url_game_id:{$providerId}"],
            ];
            $buttons[] = [
                ['text' => 'Paket URL', 'callback_data' => "menu_admin:provider_edit:url_paket:{$providerId}"],
                ['text' => 'Reset Key URL', 'callback_data' => "menu_admin:provider_edit:url_reset:{$providerId}"],
            ];
            $buttons[] = [
                ['text' => 'Edit Key URL', 'callback_data' => "menu_admin:provider_edit:url_edit:{$providerId}"],
            ];
        }

        $buttons[] = [
            ['text' => '⬅️ Kembali ke Detail', 'callback_data' => "menu_admin:provider_detail:{$providerId}"],
        ];

        return editMessageOrCaption(
            $data['message'] ?? [],
            "🔗 <b>Edit Endpoints URL - {$provider->name}</b> (Type API: " . ($provider->type_api == 0 ? '0 (Stok)' : $provider->type_api) . ")\n\n" . ($provider->type_api == 0 ? 'Sistem stok tidak memerlukan URL Endpoints.' : 'Silakan pilih URL endpoint yang ingin diubah:'),
            $buttons
        );
    }

    private function handleProviderCreatePrompt(array $data, $user = null, $config = null)
    {
        $user->session = "admin_create_provider:name";
        $user->save();

        $prompt = "✍️ Silakan kirimkan <b>Nama Provider Baru</b> (contoh: <code>GXFiles</code>):\n\n" .
            "⚠️ <i>Klik tombol Batal di bawah untuk membatalkan.</i>";

        $keyboard = [
            [
                ['text' => '❌ Batal', 'callback_data' => 'menu_admin:provider'],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $prompt, $keyboard);
    }

    private function handleProviderCreateTypeCallback(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts = explode(':', $callbackData);
        $type  = $parts[2] ?? '1';

        $sessionParts = explode(':', $user->session);
        $name         = urldecode($sessionParts[2] ?? '');
        $apiKey       = urldecode($sessionParts[3] ?? '');

        $user->session = "admin_create_provider:reset_license:" . urlencode($name) . ":" . urlencode($apiKey) . ":{$type}";
        $user->save();

        $keyboard = [
            [
                ['text' => '🟢 Enabled', 'callback_data' => "menu_admin:provider_create_reset:enabled"],
                ['text' => '🔴 Disabled', 'callback_data' => "menu_admin:provider_create_reset:disabled"],
            ],
            [
                ['text' => '❌ Batal', 'callback_data' => 'menu_admin:provider'],
            ],
        ];

        return editMessageOrCaption(
            $data['message'] ?? [],
            "Nama Provider: <b>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</b>\nType API: <b>{$type}</b>\n\n✍️ Silakan tentukan status <b>Reset License</b>:",
            $keyboard
        );
    }

    private function handleProviderCreateResetCallback(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts = explode(':', $callbackData);
        $reset = $parts[2] ?? 'enabled';

        $sessionParts = explode(':', $user->session);
        $name         = urldecode($sessionParts[2] ?? '');
        $apiKey       = urldecode($sessionParts[3] ?? '');
        $type         = intval($sessionParts[4] ?? 1);

        $provider = Provider::create([
            'name'          => $name,
            'api_key'       => $apiKey,
            'type_api'      => $type,
            'reset_license' => $reset,
            'status'        => 'active',
            'url'           => [],
        ]);

        $user->session = null;
        $user->save();

        if (isset($data['id'])) {
            $this->answerCallbackQuery($data['id'], '✅ Provider berhasil dibuat!');
        }

        return $this->handleProviderDetail("menu_admin:provider_detail:{$provider->id}", $data, $user, $config);
    }

    private function handleProviderEditPrompt(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $field      = $parts[2] ?? '';
        $providerId = $parts[3] ?? null;

        $provider = Provider::find($providerId);
        if (! $provider) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Provider tidak ditemukan.');
            }
            return;
        }

        $user->session = "admin_edit_provider:{$field}:{$providerId}";
        $user->save();

        $fieldLabel = match ($field) {
            'name'          => 'Nama Provider',
            'api_key'       => 'API Key Provider',
            'type_api'      => 'Type API (0, 1, 2, atau 3)',
            'reset_license' => 'Reset License (enabled atau disabled)',
            'url_register'  => 'Register URL Endpoint',
            'url_get_game'  => 'Get Game URL Endpoint',
            'url_reset'     => 'Reset URL Endpoint',
            'url_game_id'   => 'Game ID URL Endpoint',
            'url_paket'     => 'Paket URL Endpoint',
            'url_edit'      => 'Edit Key URL Endpoint',
            default         => $field,
        };

        $prompt = "✍️ Silakan kirimkan <b>{$fieldLabel}</b> baru untuk provider ini:\n\n" .
            "⚠️ <i>Klik tombol Batal di bawah untuk membatalkan.</i>";

        $backCallback = str_starts_with($field, 'url_')
            ? "menu_admin:provider_endpoints:{$providerId}"
            : "menu_admin:provider_detail:{$providerId}";

        $keyboard = [
            [
                ['text' => '❌ Batal', 'callback_data' => $backCallback],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $prompt, $keyboard);
    }

    private function handleProviderCustomDataList(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $providerId = $parts[2] ?? null;

        $provider = Provider::find($providerId);
        if (! $provider) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Provider tidak ditemukan.');
            }
            return;
        }

        $customData = $provider->custom_data ?? [];

        $caption = "🛠️ <b>Custom Data - {$provider->name}</b>\n\n" .
            "Custom data digunakan untuk mapping ID Game / Kode Game ke Provider.\n\n" .
            "<b>Daftar Custom Data:</b>\n";

        if (empty($customData)) {
            $caption .= "<i>Belum ada custom data.</i>\n";
        } else {
            $index = 1;
            foreach ($customData as $item) {
                $game      = Game::find($item['game_id'] ?? 0);
                $gameName  = $game ? $game->name : "Game ID: " . ($item['game_id'] ?? '-');
                $caption  .= "{$index}. <b>{$item['key']}</b> ({$gameName}) = <code>" . htmlspecialchars($item['value'], ENT_QUOTES, 'UTF-8') . "</code>\n";
                $index++;
            }
        }

        $buttons = [];
        if (! empty($customData)) {
            $idx = 0;
            $row = [];
            foreach ($customData as $item) {
                $row[] = [
                    'text'          => '🗑️ Hapus #' . ($idx + 1),
                    'callback_data' => "menu_admin:provider_cd_delete:{$providerId}:{$idx}",
                ];
                if (count($row) === 2) {
                    $buttons[] = $row;
                    $row       = [];
                }
                $idx++;
            }
            if (! empty($row)) {
                $buttons[] = $row;
            }
        }

        $buttons[]  = [
            ['text' => '➕ Tambah Custom Data', 'callback_data' => "menu_admin:provider_cd_add_game:{$providerId}"],
        ];
        $buttons[]  = [
            ['text' => '⬅️ Kembali ke Detail', 'callback_data' => "menu_admin:provider_detail:{$providerId}"],
        ];

        return editMessageOrCaption($data['message'] ?? [], $caption, $buttons);
    }

    private function handleProviderCDAddGame(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $providerId = $parts[2] ?? null;

        $provider = Provider::find($providerId);
        if (! $provider) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Provider tidak ditemukan.');
            }
            return;
        }

        $games   = Game::all();
        $caption = "➕ <b>Tambah Custom Data - {$provider->name}</b>\n\n" .
            "<b>Langkah 1/3:</b> Silakan pilih <b>Game</b> yang ingin dipetakan:";

        $buttons = $games->map(function ($g) use ($providerId) {
            return [
                'text'          => $g->name,
                'callback_data' => "menu_admin:provider_cd_add_key:{$providerId}:{$g->id}",
            ];
        })->toArray();

        $keyboard   = array_chunk($buttons, 2);
        $keyboard[] = [
            ['text' => '❌ Batal', 'callback_data' => "menu_admin:provider_custom_data:{$providerId}"],
        ];

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handleProviderCDAddKey(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $providerId = $parts[2] ?? null;
        $gameId     = $parts[3] ?? null;

        $provider = Provider::find($providerId);
        $game     = Game::find($gameId);
        if (! $provider || ! $game) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Data tidak ditemukan.');
            }
            return;
        }

        $caption = "➕ <b>Tambah Custom Data - {$provider->name}</b>\n\n" .
            "• <b>Game:</b> {$game->name}\n\n" .
            "<b>Langkah 2/3:</b> Silakan pilih tipe key atau ambil otomatis dari provider:\n\n";

        if ($provider->type_api == 1 || $provider->type_api == 3) {
            $caption       .= "Pilih <code>c_cgame</code> jika ingin memetakan Kode Game (code) dari sistem ke provider.\n";
            $selectedField  = 'c_cgame';
            $keyboard       = [
                [
                    ['text' => 'c_cgame', 'callback_data' => "menu_admin:provider_cd_prompt_val:{$providerId}:{$gameId}:c_cgame"],
                ],
            ];
            if ($provider->type_api == 1) {
                $keyboard[] = [
                    ['text' => 'Get c_cgame / c_gameid from provider', 'callback_data' => "menu_admin:pr_cd_fetch:{$providerId}:{$gameId}"],
                ];
            }
            $keyboard[] = [
                ['text' => '❌ Batal', 'callback_data' => "menu_admin:provider_custom_data:{$providerId}"],
            ];
        } else {
            $caption       .= "Pilih <code>c_gameid</code> jika ingin memetakan ID Game (game_id) dari sistem ke provider.";
            $selectedField  = 'c_gameid';
            $keyboard       = [
                [
                    ['text' => 'c_gameid', 'callback_data' => "menu_admin:provider_cd_prompt_val:{$providerId}:{$gameId}:c_gameid"],
                ],
                [
                    ['text' => 'Get c_cgame / c_gameid from provider', 'callback_data' => "menu_admin:pr_cd_fetch:{$providerId}:{$gameId}"],
                ],
                [
                    ['text' => '❌ Batal', 'callback_data' => "menu_admin:provider_custom_data:{$providerId}"],
                ],
            ];
        }

        $user->session  = "admin_provider_custom_data_value:{$providerId}:{$gameId}:{$selectedField}";
        $user->save();

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handleProviderCDPromptVal(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $providerId = $parts[2] ?? null;
        $gameId     = $parts[3] ?? null;
        $key        = $parts[4] ?? null;

        $provider = Provider::find($providerId);
        $game     = Game::find($gameId);

        if (! $provider || ! $game) {
            return;
        }

        $user->session = "admin_provider_custom_data_value:{$providerId}:{$gameId}:{$key}";
        $user->save();

        $caption = "➕ <b>Tambah Custom Data - {$provider->name}</b>\n\n" .
            "• <b>Game:</b> {$game->name}\n" .
            "• <b>Key:</b> <code>{$key}</code>\n\n" .
            "<b>Langkah 3/3:</b> Silakan kirimkan <b>Value</b> (nilai) yang diinginkan via chat ini:";

        $keyboard = [
            [
                ['text' => '❌ Batal', 'callback_data' => "menu_admin:provider_custom_data:{$providerId}"],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handleProviderCDDelete(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $providerId = $parts[2] ?? null;
        $index      = intval($parts[3] ?? -1);

        $provider = Provider::find($providerId);
        if ($provider && $index >= 0) {
            $customData = $provider->custom_data ?? [];
            if (isset($customData[$index])) {
                array_splice($customData, $index, 1);
                $provider->custom_data = array_values($customData);
                $provider->save();

                if (isset($data['id'])) {
                    $this->answerCallbackQuery($data['id'], '✅ Custom data berhasil dihapus.');
                }
            }
        }

        return $this->handleProviderCustomDataList("menu_admin:provider_custom_data:{$providerId}", $data, $user, $config);
    }

    private function handleProviderCDFetchProvider(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $providerId = $parts[2] ?? null;
        $gameId     = $parts[3] ?? null;

        $provider = Provider::find($providerId);
        $game     = Game::find($gameId);

        if (! $provider || ! $game) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Data tidak ditemukan.');
            }
            return;
        }

        if (isset($data['id'])) {
            $this->answerCallbackQuery($data['id'], 'Mengambil data dari provider...');
        }

        $list = [];
        if ($provider->type_api == 1) {
            $api     = new Apiv1();
            $apiData = [
                'url'     => $provider->url['get_game'] ?? '',
                'api_key' => $provider->api_key,
                'action'  => 'get_game',
            ];
            $response = $api->getGame($apiData);
            if (isset($response['status']) && $response['status'] == true && ! empty($response['data'])) {
                $list = $response['data'];
            } else {
                $list = [];
            }
        } elseif ($provider->type_api == 2) {
            $api      = new Apiv2();
            $response = $api->game_id($provider->api_key, $provider->url);
            if (isset($response['success']) && $response['success'] == true && ! empty($response['data'])) {
                $list = $response['data'];
            } else {
                $list = [];
            }
        }

        if (empty($list)) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], '⚠️ Gagal mengambil data game dari provider.', true);
            }
            return;
        }

        $caption = "➕ <b>Pilih Game dari Provider - {$provider->name}</b>\n\n" .
            "Silakan pilih game dari provider di bawah ini untuk dipetakan ke game <b>{$game->name}</b>:";

        $buttons = [];
        if ($provider->type_api == 1) {
            foreach ($list as $row) {
                $code = $row['code'] ?? '';
                $name = $row['game'] ?? '';
                if ($code && $name) {
                    $buttons[] = [
                        'text' => "{$name} ({$code})",
                        'callback_data' => "menu_admin:pr_cd_sf:{$providerId}:{$gameId}:c_cgame:{$code}",
                    ];
                }
            }
        } else {
            foreach ($list as $row) {
                $id   = $row['id'] ?? '';
                $name = $row['nama'] ?? '';
                if ($id && $name) {
                    $buttons[] = [
                        'text' => "{$name} (ID: {$id})",
                        'callback_data' => "menu_admin:pr_cd_sf:{$providerId}:{$gameId}:c_gameid:{$id}",
                    ];
                }
            }
        }

        $keyboard   = array_chunk($buttons, 2);
        $keyboard[] = [
            ['text' => '❌ Batal', 'callback_data' => "menu_admin:provider_cd_add_key:{$providerId}:{$gameId}"],
        ];

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handleProviderCDSaveFetched(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $providerId = $parts[2] ?? null;
        $gameId     = $parts[3] ?? null;
        $key        = $parts[4] ?? null;
        $value      = $parts[5] ?? null;

        $provider = Provider::find($providerId);
        $game     = Game::find($gameId);

        if (! $provider || ! $game) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Data tidak ditemukan.');
            }
            return;
        }

        if ($key === 'c_gameid') {
            if (is_numeric($value)) {
                $value = intval($value);
            }
        }

        $customData = $provider->custom_data ?? [];

        $updated = false;
        foreach ($customData as &$item) {
            if (($item['game_id'] ?? null) == $gameId && ($item['key'] ?? null) === $key) {
                $item['value'] = $value;
                $updated       = true;
                break;
            }
        }
        unset($item);

        if (! $updated) {
            $customData[] = [
                'key'     => $key,
                'game_id' => intval($gameId),
                'value'   => $value,
            ];
        }

        $provider->custom_data = $customData;
        $provider->save();

        if (isset($data['id'])) {
            $this->answerCallbackQuery($data['id'], '✅ Custom data berhasil disimpan.');
        }

        return $this->handleProviderCustomDataList("menu_admin:provider_custom_data:{$providerId}", $data, $user, $config);
    }

    // ==========================================
    // DENOM CRUD FLOWS
    // ==========================================

    private function handleDenomGames(array $data, $user = null, $config = null)
    {
        $games = Game::where('status', 'active')->get();

        $buttons = $games->map(function ($row) {
            return [
                'text'          => '🎮 ' . $row->name,
                'callback_data' => 'menu_admin:denom_game:' . $row->id,
            ];
        })->toArray();

        $keyboard   = array_chunk($buttons, 2);
        $keyboard[] = [
            ['text' => '⬅️ Kembali', 'callback_data' => 'menu_admin'],
        ];

        return editMessageOrCaption(
            $data['message'] ?? [],
            "<b>Kelola Denom - Pilih Game</b>\n\nSilakan pilih game di bawah ini untuk mengelola denomnya:",
            $keyboard
        );
    }

    private function handleDenomProviders(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts  = explode(':', $callbackData);
        $gameId = $parts[2] ?? null;

        $game = Game::find($gameId);
        if (! $game) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Game tidak ditemukan.');
            }
            return;
        }

        $providers = Provider::whereIn('id', $game->providers ?? [])->get();

        $buttons = $providers->map(function ($row) use ($gameId) {
            return [
                'text'          => $row->name,
                'callback_data' => 'menu_admin:denom_list:' . $gameId . ':' . $row->id,
            ];
        })->toArray();

        $keyboard   = array_chunk($buttons, 2);
        $keyboard[] = [
            ['text' => '⬅️ Pilih Game Lain', 'callback_data' => 'menu_admin:denom'],
        ];

        return editMessageOrCaption(
            $data['message'] ?? [],
            "<b>Kelola Denom - Pilih Provider</b>\n\nGame: <b>{$game->name}</b>\n\nSilakan pilih provider di bawah ini:",
            $keyboard
        );
    }

    private function handleDenomsList(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $gameId     = $parts[2] ?? null;
        $providerId = $parts[3] ?? null;

        $game     = Game::find($gameId);
        $provider = Provider::find($providerId);

        if (! $game || ! $provider) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Game atau Provider tidak ditemukan.');
            }
            return;
        }

        $denoms = Denom::where([
            ['game_id', $gameId],
            ['provider_id', $providerId],
        ])->get();

        $buttons = $denoms->map(function ($row) {
            $statusEmoji = ($row->status === 'active') ? '🟢' : '🔴';
            return [
                'text'          => $row->name . ' - Rp ' . number_format($row->price, 0, ',', '.') . ' [' . $statusEmoji . ']',
                'callback_data' => 'menu_admin:denom_detail:' . $row->id,
            ];
        })->toArray();

        $keyboard = array_chunk($buttons, 1);

        $keyboard[] = [
            ['text' => '➕ Buat Denom Baru', 'callback_data' => "menu_admin:denom_create:{$gameId}:{$providerId}"],
        ];
        $keyboard[] = [
            ['text' => '⬅️ Pilih Provider Lain', 'callback_data' => "menu_admin:denom_game:{$gameId}"],
        ];

        return editMessageOrCaption(
            $data['message'] ?? [],
            "📦 <b>Daftar Denom</b>\n\nGame: <b>{$game->name}</b>\nProvider: <b>{$provider->name}</b>\n\nSilakan pilih denom di bawah ini untuk melihat detail/mengubah:",
            $keyboard
        );
    }

    private function handleDenomDetail(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts   = explode(':', $callbackData);
        $denomId = $parts[2] ?? null;

        $denom = Denom::find($denomId);
        if (! $denom) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Denom tidak ditemukan.');
            }
            return;
        }

        $game     = Game::find($denom->game_id);
        $provider = Provider::find($denom->provider_id);

        $gameName     = $game ? $game->name : '-';
        $providerName = $provider ? $provider->name : '-';
        $statusBadge  = ($denom->status === 'active') ? '🟢 Aktif' : '🔴 Nonaktif';

        $caption = "🔍 <b>Detail Denom</b>\n\n" .
            "• <b>Nama:</b> {$denom->name}\n" .
            "• <b>Harga:</b> Rp " . number_format($denom->price, 0, ',', '.') . "\n" .
            "• <b>Durasi:</b> {$denom->duration} Hari\n" .
            "• <b>Status:</b> {$statusBadge}\n" .
            "• <b>Game:</b> {$gameName}\n" .
            "• <b>Provider:</b> {$providerName}";

        if ($provider && $provider->type_api == 0) {
            $readyCount = Stock::where('denom_id', $denomId)->where('status', 'ready')->count();
            $caption .= "\n• <b>Stok Ready:</b> <b>{$readyCount} item</b>";
        }

        $keyboard = [
            [
                ['text' => '✍️ Edit Nama', 'callback_data' => "menu_admin:denom_edit:name:{$denomId}"],
                ['text' => '💰 Edit Harga', 'callback_data' => "menu_admin:denom_edit:price:{$denomId}"],
            ],
            [
                ['text' => '⏱️ Edit Durasi', 'callback_data' => "menu_admin:denom_edit:duration:{$denomId}"],
                ['text' => '🔌 Toggle Status', 'callback_data' => "menu_admin:denom_toggle:{$denomId}"],
            ],
        ];

        if ($provider && $provider->type_api == 0) {
            $keyboard[] = [
                ['text' => '📦 Kelola Stok', 'callback_data' => "menu_admin:stock:manage:{$denomId}"],
            ];
        }

        $keyboard[] = [
            ['text' => '🗑️ Hapus Denom', 'callback_data' => "menu_admin:denom_delete:{$denomId}"],
        ];
        $keyboard[] = [
            ['text' => '⬅️ Kembali ke Daftar', 'callback_data' => "menu_admin:denom_list:{$denom->game_id}:{$denom->provider_id}"],
        ];

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handleDenomToggle(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts   = explode(':', $callbackData);
        $denomId = $parts[2] ?? null;

        $denom = Denom::find($denomId);
        if (! $denom) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Denom tidak ditemukan.');
            }
            return;
        }

        $denom->status = ($denom->status === 'active') ? 'inactive' : 'active';
        $denom->save();

        if (isset($data['id'])) {
            $statusStr = ($denom->status === 'active') ? 'diaktifkan' : 'dinonaktifkan';
            $this->answerCallbackQuery($data['id'], "✅ Denom berhasil {$statusStr}!");
        }

        return $this->handleDenomDetail($callbackData, $data, $user, $config);
    }

    private function handleDenomDeleteConfirm(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts   = explode(':', $callbackData);
        $denomId = $parts[2] ?? null;

        $denom = Denom::find($denomId);
        if (! $denom) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Denom tidak ditemukan.');
            }
            return;
        }

        $caption = "⚠️ <b>Konfirmasi Hapus Denom</b>\n\n" .
            "Apakah Anda yakin ingin menghapus denom berikut?\n\n" .
            "• <b>Nama:</b> {$denom->name}\n" .
            "• <b>Harga:</b> Rp " . number_format($denom->price, 0, ',', '.') . "\n" .
            "• <b>Durasi:</b> {$denom->duration} Hari\n\n" .
            "<i>Tindakan ini tidak dapat dibatalkan.</i>";

        $keyboard = [
            [
                ['text' => '🔴 Ya, Hapus', 'callback_data' => "menu_admin:denom_destroy:{$denomId}"],
                ['text' => '🟢 Batal', 'callback_data' => "menu_admin:denom_detail:{$denomId}"],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handleDenomDestroy(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts   = explode(':', $callbackData);
        $denomId = $parts[2] ?? null;

        $denom = Denom::find($denomId);
        if (! $denom) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Denom tidak ditemukan.');
            }
            return;
        }

        $gameId     = $denom->game_id;
        $providerId = $denom->provider_id;

        $denom->delete();

        if (isset($data['id'])) {
            $this->answerCallbackQuery($data['id'], '✅ Denom berhasil dihapus!');
        }

        $fakeCallback = "menu_admin:denom_list:{$gameId}:{$providerId}";
        return $this->handleDenomsList($fakeCallback, $data, $user, $config);
    }

    private function handleProviderStockList(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $providerId = $parts[2] ?? null;

        $provider = Provider::find($providerId);
        if (! $provider) return;

        $denoms = Denom::where('provider_id', $providerId)->get();

        $buttons = $denoms->map(function ($row) {
            $game = Game::find($row->game_id);
            $gameName = $game ? $game->name : 'Unknown Game';
            $readyCount = Stock::where('denom_id', $row->id)->where('status', 'ready')->count();
            return [
                'text'          => "[{$gameName}] " . $row->name . " (Stok: {$readyCount})",
                'callback_data' => "menu_admin:stock:manage:{$row->id}",
            ];
        })->toArray();

        $keyboard = array_chunk($buttons, 1);
        $keyboard[] = [
            ['text' => '⬅️ Kembali ke Provider', 'callback_data' => "menu_admin:provider_detail:{$providerId}"],
        ];

        return editMessageOrCaption(
            $data['message'] ?? [],
            "📦 <b>Pilih Denom untuk Kelola Stok</b>\n\nProvider: <b>{$provider->name}</b>\n\nSilakan pilih denom di bawah untuk mengelola stoknya:",
            $keyboard
        );
    }


    private function handleStockCallback(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts  = explode(':', $callbackData);
        $action = $parts[2] ?? '';

        if ($action === 'manage') {
            $denomId = $parts[3] ?? null;
            $denom   = Denom::find($denomId);
            if (! $denom) return;

            $readyCount      = Stock::where('denom_id', $denomId)->where('status', 'ready')->count();
            $processingCount = Stock::where('denom_id', $denomId)->where('status', 'processing')->count();
            $soldCount       = Stock::where('denom_id', $denomId)->where('status', 'sold')->count();
            $inactiveCount   = Stock::where('denom_id', $denomId)->where('status', 'inactive')->count();

            $game     = Game::find($denom->game_id);
            $provider = Provider::find($denom->provider_id);

            $caption = "📦 <b>Kelola Stok - {$denom->name}</b>\n\n" .
                "🎮 <b>Game:</b> " . ($game->name ?? '-') . "\n" .
                "🏢 <b>Provider:</b> " . ($provider->name ?? '-') . "\n\n" .
                "📊 <b>Statistik Stok:</b>\n" .
                "• Ready: <b>{$readyCount}</b>\n" .
                "• Processing: <b>{$processingCount}</b>\n" .
                "• Sold: <b>{$soldCount}</b>\n" .
                "• Inactive: <b>{$inactiveCount}</b>\n\n" .
                "Silakan pilih aksi di bawah ini:";

            $keyboard = [
                [
                    ['text' => '➕ Tambah Stok', 'callback_data' => "menu_admin:stock:add_prompt:{$denomId}"],
                    ['text' => '📋 List Stok Ready', 'callback_data' => "menu_admin:stock:list:{$denomId}:1"],
                ],
                [
                    ['text' => '🗑️ Kosongkan Stok Ready', 'callback_data' => "menu_admin:stock:purge_confirm:{$denomId}"],
                ],
                [
                    ['text' => '⬅️ Kembali ke Detail Denom', 'callback_data' => "menu_admin:denom_detail:{$denomId}"],
                ],
            ];

            return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
        }

        if ($action === 'add_prompt') {
            $denomId = $parts[3] ?? null;
            $denom   = Denom::find($denomId);
            if (! $denom) return;

            $user->session = "admin_stock_add:{$denomId}";
            $user->save();

            $prompt = "✍️ <b>Tambah Stok - {$denom->name}</b>\n\n" .
                "Silakan kirimkan lisensi key baru.\n" .
                "Anda dapat mengirimkan beberapa lisensi key sekaligus dengan menuliskannya <b>satu key per baris</b>.\n\n" .
                "Contoh:\n" .
                "<code>LICENSE-KEY-1</code>\n" .
                "<code>LICENSE-KEY-2</code>\n" .
                "<code>LICENSE-KEY-3</code>\n\n" .
                "⚠️ <i>Ketik /cancel atau cancel untuk membatalkan.</i>";

            $keyboard = [
                [
                    ['text' => '❌ Batal', 'callback_data' => "menu_admin:stock:manage:{$denomId}"],
                ],
            ];

            return editMessageOrCaption($data['message'] ?? [], $prompt, $keyboard);
        }

        if ($action === 'list') {
            $denomId = $parts[3] ?? null;
            $page    = intval($parts[4] ?? 1);
            $denom   = Denom::find($denomId);
            if (! $denom) return;

            $query      = Stock::where('denom_id', $denomId)->where('status', 'ready')->orderBy('id', 'desc');
            $limit      = 10;
            $offset     = ($page - 1) * $limit;
            $total      = $query->count();
            $stocks     = $query->skip($offset)->take($limit)->get();
            $totalPages = ceil($total / $limit);
            if ($totalPages < 1) $totalPages = 1;

            $listContent = '';
            $numButtons  = [];
            $index       = 1;

            foreach ($stocks as $row) {
                $listContent .= "<b>{$index}.</b> <code>{$row->license}</code>\n";
                $numButtons[] = [
                    'text'          => " 🗑️ {$index} ",
                    'callback_data' => "menu_admin:stock:delete_confirm:{$row->id}",
                ];
                $index++;
            }

            if (empty($listContent)) {
                $listContent = "<i>Tidak ada stok ready untuk produk ini.</i>\n\n";
            }

            $keyboard = [];
            if (! empty($numButtons)) {
                $keyboard = array_chunk($numButtons, 5);
            }

            // Pagination row
            $paginationRow = [];
            if ($page > 1) {
                $paginationRow[] = [
                    'text'          => '◀️ Prev',
                    'callback_data' => "menu_admin:stock:list:{$denomId}:" . ($page - 1),
                ];
            }

            $paginationRow[] = [
                'text'          => "Hal {$page}/{$totalPages}",
                'callback_data' => 'current_page',
            ];

            if ($page < $totalPages) {
                $paginationRow[] = [
                    'text'          => 'Next ▶️',
                    'callback_data' => "menu_admin:stock:list:{$denomId}:" . ($page + 1),
                ];
            }
            $keyboard[] = $paginationRow;

            $keyboard[] = [
                ['text' => '⬅️ Kembali ke Kelola Stok', 'callback_data' => "menu_admin:stock:manage:{$denomId}"],
            ];

            $caption = "📋 <b>Daftar Stok Ready - {$denom->name}</b> (Halaman {$page}/{$totalPages})\n" .
                "• Total Stok Ready: <b>{$total}</b>\n\n" .
                $listContent . "\n" .
                "Pilih nomor bersimbol 🗑️ di bawah untuk menghapus lisensi key tersebut:";

            return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
        }

        if ($action === 'delete_confirm') {
            $stockId = $parts[3] ?? null;
            $stock   = Stock::find($stockId);
            if (! $stock) return;

            $denom = Denom::find($stock->denom_id);

            $caption = "⚠️ <b>Konfirmasi Hapus Lisensi</b>\n\n" .
                "Apakah Anda yakin ingin menghapus lisensi berikut?\n" .
                "• Produk: <b>" . ($denom->name ?? '-') . "</b>\n" .
                "• Lisensi: <code>{$stock->license}</code>\n\n" .
                "<i>Tindakan ini tidak dapat dibatalkan.</i>";

            $keyboard = [
                [
                    ['text' => '🗑️ Ya, Hapus', 'callback_data' => "menu_admin:stock:destroy:{$stockId}"],
                    ['text' => '❌ Batal', 'callback_data' => "menu_admin:stock:list:{$stock->denom_id}:1"],
                ],
            ];

            return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
        }

        if ($action === 'destroy') {
            $stockId = $parts[3] ?? null;
            $stock   = Stock::find($stockId);
            if ($stock) {
                $denomId = $stock->denom_id;
                $stock->delete();
                if (isset($data['id'])) {
                    $this->answerCallbackQuery($data['id'], '✅ Lisensi berhasil dihapus!');
                }
                return $this->handleStockCallback("menu_admin:stock:list:{$denomId}:1", $data, $user, $config);
            }
        }

        if ($action === 'purge_confirm') {
            $denomId = $parts[3] ?? null;
            $denom   = Denom::find($denomId);
            if (! $denom) return;

            $readyCount = Stock::where('denom_id', $denomId)->where('status', 'ready')->count();

            $caption = "⚠️ <b>Konfirmasi Kosongkan Stok Ready</b>\n\n" .
                "Apakah Anda yakin ingin menghapus seluruh stok yang bertatus <b>ready</b> untuk produk berikut?\n" .
                "• Produk: <b>{$denom->name}</b>\n" .
                "• Jumlah Stok Akan Dihapus: <b>{$readyCount} item</b>\n\n" .
                "<i>Tindakan ini tidak dapat dibatalkan!</i>";

            $keyboard = [
                [
                    ['text' => '🔴 Ya, Kosongkan', 'callback_data' => "menu_admin:stock:purge:{$denomId}"],
                    ['text' => '❌ Batal', 'callback_data' => "menu_admin:stock:manage:{$denomId}"],
                ],
            ];

            return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
        }

        if ($action === 'purge') {
            $denomId = $parts[3] ?? null;
            $denom   = Denom::find($denomId);
            if ($denom) {
                Stock::where('denom_id', $denomId)->where('status', 'ready')->delete();
                if (isset($data['id'])) {
                    $this->answerCallbackQuery($data['id'], '✅ Stok ready berhasil dikosongkan!');
                }
                return $this->handleStockCallback("menu_admin:stock:manage:{$denomId}", $data, $user, $config);
            }
        }
    }

    private function handleDenomCreatePrompt(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $gameId     = $parts[2] ?? null;
        $providerId = $parts[3] ?? null;

        $user->session = "admin_create_denom:name:{$gameId}:{$providerId}";
        $user->save();

        $prompt = "✍️ Silakan kirimkan <b>Nama Denom Baru</b> (contoh: <code>1 Hari</code> atau <code>30 Hari</code>):\n\n" .
            "⚠️ <i>Klik tombol Batal di bawah untuk membatalkan.</i>";

        $keyboard = [
            [
                ['text' => '❌ Batal', 'callback_data' => "menu_admin:denom_list:{$gameId}:{$providerId}"],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $prompt, $keyboard);
    }

    private function handleDenomEditPrompt(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts   = explode(':', $callbackData);
        $field   = $parts[2] ?? '';
        $denomId = $parts[3] ?? null;

        $denom = Denom::find($denomId);
        if (! $denom) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Denom tidak ditemukan.');
            }
            return;
        }

        $user->session = "admin_edit_denom:{$field}:{$denomId}";
        $user->save();

        $fieldLabel = match ($field) {
            'name'     => 'Nama Denom',
            'price'    => 'Harga Denom',
            'duration' => 'Durasi (Hari)',
            default    => $field,
        };

        $prompt = "✍️ Silakan kirimkan <b>{$fieldLabel}</b> baru untuk denom ini:\n\n" .
            "⚠️ <i>Klik tombol Batal di bawah untuk membatalkan.</i>";

        $keyboard = [
            [
                ['text' => '❌ Batal', 'callback_data' => "menu_admin:denom_detail:{$denomId}"],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $prompt, $keyboard);
    }

    // ==========================================
    // USER CRUD & BROADCAST FLOWS
    // ==========================================

    private function handleUserList(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts  = explode(':', $callbackData);
        $filter = $parts[2] ?? 'all';
        $page   = intval($parts[3] ?? 1);

        $query = User::query()->orderBy('id', 'desc');
        if ($filter === 'admin') {
            $query->where('role', 'admin');
        } elseif ($filter === 'member') {
            $query->where(function ($q) {
                $q->whereNull('role')->orWhere('role', '!=', 'admin');
            });
        }

        $limit      = 5;
        $offset     = ($page - 1) * $limit;
        $total      = $query->count();
        $users      = $query->skip($offset)->take($limit)->get();
        $totalPages = ceil($total / $limit);
        if ($totalPages < 1) {
            $totalPages = 1;
        }

        $listUsers  = '';
        $numButtons = [];
        $index      = 1;

        foreach ($users as $row) {
            $roleLabel   = ($row->role === 'admin') ? '🔴 Admin' : '🟢 Member';
            $usernameStr = $row->username ? '@' . $row->username : '-';
            $fullName    = trim($row->first_name . ' ' . $row->last_name);

            $listUsers .= "<b>{$index}. {$fullName} ({$roleLabel})</b>\n" .
                "   • Username: <code>{$usernameStr}</code>\n" .
                "   • Telegram ID: <code>{$row->user_id}</code>\n\n";

            $numButtons[] = [
                'text' => " {$index} ",
                'callback_data' => "menu_admin:user_detail:{$row->id}:{$filter}:{$page}",
            ];
            $index++;
        }

        if (empty($listUsers)) {
            $listUsers = "<i>Tidak ada user pada filter ini.</i>\n\n";
        }

        $keyboard = [];
        if (! empty($numButtons)) {
            $keyboard = array_chunk($numButtons, 5);
        }

        $filterButtons = [];
        foreach (['all' => 'Semua', 'admin' => 'Admin', 'member' => 'Member'] as $fKey => $fLabel) {
            $activeIndicator = ($filter === $fKey) ? '🔹 ' : '';
            $filterButtons[] = [
                'text'          => $activeIndicator . $fLabel,
                'callback_data' => "menu_admin:user_list:{$fKey}:1",
            ];
        }
        $keyboard[] = $filterButtons;

        $paginationRow = [];
        if ($page > 1) {
            $paginationRow[] = [
                'text'          => '◀️ Prev',
                'callback_data' => "menu_admin:user_list:{$filter}:" . ($page - 1),
            ];
        }

        $paginationRow[] = [
            'text' => "Hal {$page}/{$totalPages}",
            'callback_data' => 'current_page',
        ];

        if ($page < $totalPages) {
            $paginationRow[] = [
                'text'          => 'Next ▶️',
                'callback_data' => "menu_admin:user_list:{$filter}:" . ($page + 1),
            ];
        }
        $keyboard[] = $paginationRow;

        $keyboard[] = [
            ['text' => '⬅️ Kembali ke Admin Menu', 'callback_data' => 'menu_admin'],
        ];

        $title = "👥 <b>Kelola User</b> (Halaman {$page}/{$totalPages})\n" .
            "• Filter: <b>" . strtoupper($filter) . "</b>\n" .
            "• Total Data: <b>{$total}</b>\n\n" .
            $listUsers .
            "Pilih nomor user di bawah ini untuk melihat detail/mengubah role:";

        if (isset($data['message']['photo'])) {
            deleteMessage($data['message']['chat']['id'] ?? null, $data['message']['message_id'] ?? null);
            return sendMessage([
                'chat_id'  => $data['message']['chat']['id'] ?? null,
                'text'     => $title,
                'mode'     => 'HTML',
                'keyboard' => $keyboard,
            ]);
        }

        return editMessageOrCaption($data['message'] ?? [], $title, $keyboard);
    }

    private function handleUserDetail(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts  = explode(':', $callbackData);
        $userId = $parts[2] ?? null;
        $filter = $parts[3] ?? 'all';
        $page   = intval($parts[4] ?? 1);

        $targetUser = User::find($userId);
        if (! $targetUser) {
            return;
        }

        $roleLabel    = ($targetUser->role === 'admin') ? '🔴 Admin' : '🟢 Member';
        $usernameStr  = $targetUser->username ? '@' . $targetUser->username : '-';
        $fullName     = trim($targetUser->first_name . ' ' . $targetUser->last_name);
        $registeredAt = $targetUser->created_at ? $targetUser->created_at->format('Y-m-d H:i:s') : '-';

        $caption = "👥 <b>Detail User</b>\n\n" .
            "• <b>Nama Lengkap:</b> {$fullName}\n" .
            "• <b>Username:</b> {$usernameStr}\n" .
            "• <b>Telegram ID:</b> <code>{$targetUser->user_id}</code>\n" .
            "• <b>Role:</b> <b>{$roleLabel}</b>\n" .
            "• <b>Terdaftar Pada:</b> {$registeredAt}\n\n" .
            "Pilih tombol di bawah untuk mengubah role atau menghapus user:";

        $keyboard = [
            [
                ['text' => '🔌 Toggle Role', 'callback_data' => "menu_admin:user_role:{$targetUser->id}:{$filter}:{$page}"],
                ['text' => '🗑️ Hapus User', 'callback_data' => "menu_admin:user_delete:{$targetUser->id}:{$filter}:{$page}"],
            ],
            [
                ['text' => '⬅️ Kembali ke Daftar', 'callback_data' => "menu_admin:user_list:{$filter}:{$page}"],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handleUserToggleRole(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts  = explode(':', $callbackData);
        $userId = $parts[2] ?? null;
        $filter = $parts[3] ?? 'all';
        $page   = intval($parts[4] ?? 1);

        $targetUser = User::find($userId);
        if (! $targetUser) {
            return;
        }

        // Prevent self-demotion
        if ($targetUser->id === $user->id) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], '❌ Anda tidak dapat mengubah role Anda sendiri!', true);
            }
            return;
        }

        $targetUser->role = ($targetUser->role === 'admin') ? null : 'admin';
        $targetUser->save();

        if (isset($data['id'])) {
            $this->answerCallbackQuery($data['id'], '✅ Role user berhasil diubah!');
        }

        return $this->handleUserDetail("menu_admin:user_detail:{$targetUser->id}:{$filter}:{$page}", $data, $user, $config);
    }

    private function handleUserDeleteConfirm(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts  = explode(':', $callbackData);
        $userId = $parts[2] ?? null;
        $filter = $parts[3] ?? 'all';
        $page   = intval($parts[4] ?? 1);

        $targetUser = User::find($userId);
        if (! $targetUser) {
            return;
        }

        // Prevent self-deletion
        if ($targetUser->id === $user->id) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], '❌ Anda tidak dapat menghapus akun Anda sendiri!', true);
            }
            return;
        }

        $fullName = trim($targetUser->first_name . ' ' . $targetUser->last_name);
        $caption  = "⚠️ <b>Konfirmasi Hapus User</b>\n\n" .
            "Apakah Anda yakin ingin menghapus user <b>{$fullName}</b>?\n\n" .
            "<i>Tindakan ini tidak dapat dibatalkan!</i>";

        $keyboard = [
            [
                ['text' => '🗑️ Ya, Hapus', 'callback_data' => "menu_admin:user_destroy:{$targetUser->id}:{$filter}:{$page}"],
                ['text' => '❌ Batal', 'callback_data' => "menu_admin:user_detail:{$targetUser->id}:{$filter}:{$page}"],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handleUserDestroy(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts  = explode(':', $callbackData);
        $userId = $parts[2] ?? null;
        $filter = $parts[3] ?? 'all';
        $page   = intval($parts[4] ?? 1);

        $targetUser = User::find($userId);
        if ($targetUser && $targetUser->id !== $user->id) {
            $targetUser->delete();
        }

        if (isset($data['id'])) {
            $this->answerCallbackQuery($data['id'], '✅ User berhasil dihapus!');
        }

        return $this->handleUserList("menu_admin:user_list:{$filter}:{$page}", $data, $user, $config);
    }

    private function handleBroadcastPrompt(array $data, $user = null, $config = null)
    {
        $user->session = "admin_broadcast:message:" . ($data['message']['chat']['id'] ?? '') . ":" . ($data['message']['message_id'] ?? '');
        $user->save();

        $prompt = "📢 <b>Broadcast & Reply Broadcast</b>\n\n" .
            "Silakan kirimkan pesan yang ingin di-broadcast ke semua user.\n\n" .
            "💡 <b>Cara Penggunaan:</b>\n" .
            "1. <b>Kirim Langsung:</b> Kirim teks, foto, video, dokumen, sticker, atau emoji premium secara langsung.\n" .
            "2. <b>Reply Pesan:</b> Balas (reply) ke pesan admin sebelumnya di chat ini, lalu kirim pesan apa saja (misal: 'kirim' atau emoji) untuk membroadcast pesan yang di-reply tersebut.\n\n" .
            "• Seluruh format text, file media, dan <b>premium emoji/icon</b> akan otomatis ikut terkirim.\n\n" .
            "⚠️ <i>Ketik /cancel atau cancel untuk membatalkan.</i>";

        $keyboard = [
            [
                ['text' => '❌ Batal', 'callback_data' => 'menu_admin'],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $prompt, $keyboard);
    }

    // ==========================================
    // DOWNLOAD CRUD FLOWS
    // ==========================================

    private function handleDownloadsList(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts  = explode(':', $callbackData);
        $filter = $parts[2] ?? 'all';
        $page   = intval($parts[3] ?? 1);

        $query = Download::query()->orderBy('id', 'desc');
        if ($filter !== 'all') {
            if ($filter === 'active') {
                $query->where('status', 'active');
            } elseif ($filter === 'inactive') {
                $query->where('status', 'inactive');
            }
        }

        $limit      = 5;
        $offset     = ($page - 1) * $limit;
        $total      = $query->count();
        $downloads  = $query->skip($offset)->take($limit)->get();
        $totalPages = ceil($total / $limit);
        if ($totalPages < 1) {
            $totalPages = 1;
        }

        $listDownloads = '';
        $numButtons    = [];
        $index         = 1;

        foreach ($downloads as $row) {
            $statusEmoji  = ($row->status === 'active') ? '🟢' : '🔴';
            $game         = Game::find($row->game_id);
            $provider     = Provider::find($row->provider_id);
            $gameName     = $game ? $game->name : 'Unknown Game';
            $providerName = $provider ? $provider->name : 'Unknown Provider';

            $linksCount = is_array($row->data) ? count($row->data) : 0;

            $listDownloads .= "<b>{$index}. Game: {$gameName} [{$statusEmoji}]</b>\n" .
                "   • Provider: <code>{$providerName}</code>\n" .
                "   • Jumlah Link: <code>{$linksCount} link</code>\n\n";

            $numButtons[] = [
                'text' => " {$index} ",
                'callback_data' => "menu_admin:download_detail:{$row->id}:{$filter}:{$page}",
            ];
            $index++;
        }

        if (empty($listDownloads)) {
            $listDownloads = "<i>Tidak ada konfigurasi download pada filter ini.</i>\n\n";
        }

        $keyboard = [];
        if (! empty($numButtons)) {
            $keyboard = array_chunk($numButtons, 5);
        }

        $filterButtons = [];
        foreach (['all' => 'Semua', 'active' => 'Aktif', 'inactive' => 'Nonaktif'] as $fKey => $fLabel) {
            $activeIndicator = ($filter === $fKey) ? '🔹 ' : '';
            $filterButtons[] = [
                'text'          => $activeIndicator . $fLabel,
                'callback_data' => "menu_admin:download_list:{$fKey}:1",
            ];
        }
        $keyboard[] = $filterButtons;

        $paginationRow = [];
        if ($page > 1) {
            $paginationRow[] = [
                'text'          => '◀️ Prev',
                'callback_data' => "menu_admin:download_list:{$filter}:" . ($page - 1),
            ];
        }

        $paginationRow[] = [
            'text' => "Hal {$page}/{$totalPages}",
            'callback_data' => 'current_page',
        ];

        if ($page < $totalPages) {
            $paginationRow[] = [
                'text'          => 'Next ▶️',
                'callback_data' => "menu_admin:download_list:{$filter}:" . ($page + 1),
            ];
        }
        $keyboard[] = $paginationRow;

        $keyboard[] = [
            ['text' => '➕ Tambah Download', 'callback_data' => 'menu_admin:download_create'],
        ];
        $keyboard[] = [
            ['text' => '⬅️ Kembali ke Admin Menu', 'callback_data' => 'menu_admin'],
        ];

        $title = "📥 <b>Kelola Konfigurasi Download</b> (Halaman {$page}/{$totalPages})\n" .
            "• Filter: <b>" . strtoupper($filter) . "</b>\n" .
            "• Total Data: <b>{$total}</b>\n\n" .
            $listDownloads .
            "Pilih nomor download di bawah ini untuk melihat detail/mengubah:";

        if (isset($data['message']['photo'])) {
            deleteMessage($data['message']['chat']['id'] ?? null, $data['message']['message_id'] ?? null);
            return sendMessage([
                'chat_id'  => $data['message']['chat']['id'] ?? null,
                'text'     => $title,
                'mode'     => 'HTML',
                'keyboard' => $keyboard,
            ]);
        }

        return editMessageOrCaption($data['message'] ?? [], $title, $keyboard);
    }

    private function handleDownloadDetail(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $downloadId = $parts[2] ?? null;
        $filter     = $parts[3] ?? 'all';
        $page       = intval($parts[4] ?? 1);

        $download = Download::find($downloadId);
        if (! $download) {
            return;
        }

        $game         = Game::find($download->game_id);
        $provider     = Provider::find($download->provider_id);
        $gameName     = $game ? $game->name : 'Unknown Game';
        $providerName = $provider ? $provider->name : 'Unknown Provider';

        $statusBadge = ($download->status === 'active') ? '🟢 Aktif' : '🔴 Nonaktif';

        $linksText = '';
        $links     = $download->data ?? [];
        if (is_array($links) && count($links) > 0) {
            foreach ($links as $idx => $linkObj) {
                $num        = $idx + 1;
                $titleVal   = $linkObj['title'] ?? 'Download';
                $linkVal    = $linkObj['link'] ?? '';
                $linksText .= "{$num}. <a href=\"{$linkVal}\">{$titleVal}</a>\n   <code>{$linkVal}</code>\n";
            }
        } else {
            $linksText = "<i>Tidak ada link download</i>\n";
        }

        $caption = "📥 <b>Detail Konfigurasi Download</b>\n\n" .
            "• <b>Game:</b> {$gameName} (ID: {$download->game_id})\n" .
            "• <b>Provider:</b> {$providerName} (ID: {$download->provider_id})\n" .
            "• <b>Status:</b> {$statusBadge}\n" .
            "• <b>Tutorial:</b> " . ($download->tutorial ?: 'Tidak ada tutorial') . "\n\n" .
            "<b>Daftar Link Download:</b>\n" .
            $linksText . "\n" .
            "Pilih tombol di bawah ini untuk mengedit atau menghapus:";

        $keyboard = [
            [
                ['text' => '🎮 Edit Game', 'callback_data' => "menu_admin:download_edit:game:{$download->id}"],
                ['text' => '🏢 Edit Provider', 'callback_data' => "menu_admin:download_edit:provider:{$download->id}"],
            ],
            [
                ['text' => '🔗 Edit Link/Data', 'callback_data' => "menu_admin:download_edit:data:{$download->id}"],
                ['text' => '📖 Edit Tutorial', 'callback_data' => "menu_admin:download_edit:tutorial:{$download->id}"],
            ],
            [
                ['text' => '🔌 Toggle Status', 'callback_data' => "menu_admin:download_toggle:{$download->id}"],
                ['text' => '🗑️ Hapus Download', 'callback_data' => "menu_admin:download_delete:{$download->id}"],
            ],
            [
                ['text' => '⬅️ Kembali ke Daftar', 'callback_data' => "menu_admin:download_list:{$filter}:{$page}"],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handleDownloadToggle(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $downloadId = $parts[2] ?? null;

        $download = Download::find($downloadId);
        if (! $download) {
            return;
        }

        $download->status = ($download->status === 'active') ? 'inactive' : 'active';
        $download->save();

        if (isset($data['id'])) {
            $statusStr = ($download->status === 'active') ? 'diaktifkan' : 'dinonaktifkan';
            $this->answerCallbackQuery($data['id'], "✅ Download {$statusStr}!");
        }

        return $this->handleDownloadDetail("menu_admin:download_detail:{$download->id}:all:1", $data, $user, $config);
    }

    private function handleDownloadDeleteConfirm(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $downloadId = $parts[2] ?? null;

        $download = Download::find($downloadId);
        if (! $download) {
            return;
        }

        $caption = "⚠️ <b>Konfirmasi Hapus Download</b>\n\n" .
            "Apakah Anda yakin ingin menghapus konfigurasi download ini?\n\n" .
            "<i>Tindakan ini tidak dapat dibatalkan!</i>";

        $keyboard = [
            [
                ['text' => '🗑️ Ya, Hapus', 'callback_data' => "menu_admin:download_destroy:{$download->id}"],
                ['text' => '❌ Batal', 'callback_data' => "menu_admin:download_detail:{$download->id}:all:1"],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handleDownloadDestroy(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $downloadId = $parts[2] ?? null;

        $download = Download::find($downloadId);
        if ($download) {
            $download->delete();
        }

        if (isset($data['id'])) {
            $this->answerCallbackQuery($data['id'], '✅ Konfigurasi download berhasil dihapus.');
        }

        return $this->handleDownloadsList("menu_admin:download_list:all:1", $data, $user, $config);
    }

    private function handleDownloadCreatePrompt(array $data, $user = null, $config = null)
    {
        $games = Game::where('status', 'active')->get();
        if ($games->isEmpty()) {
            $keyboard = [[['text' => '⬅️ Kembali', 'callback_data' => 'menu_admin:download']]];
            return editMessageOrCaption($data['message'] ?? [], "❌ <b>Gagal:</b> Tidak ada game aktif untuk dikaitkan dengan download.", $keyboard);
        }

        $buttons = $games->map(function ($row) {
            return [
                'text'          => $row->name,
                'callback_data' => "menu_admin:download_create_game:{$row->id}",
            ];
        })->toArray();

        $keyboard   = array_chunk($buttons, 2);
        $keyboard[] = [
            ['text' => '❌ Batal', 'callback_data' => 'menu_admin:download'],
        ];

        return editMessageOrCaption($data['message'] ?? [], "📥 <b>Tambah Download - Langkah 1</b>\n\nSilakan pilih <b>Game</b>:", $keyboard);
    }

    private function handleDownloadCreateGameCallback(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts  = explode(':', $callbackData);
        $gameId = $parts[2] ?? null;

        $game = Game::find($gameId);
        if (! $game) {
            return;
        }

        $connectedIds = $game->providers ?? [];
        $providers    = Provider::whereIn('id', $connectedIds)->where('status', 'active')->get();
        if ($providers->isEmpty()) {
            // Fallback to all active providers if none connected
            $providers = Provider::where('status', 'active')->get();
        }

        if ($providers->isEmpty()) {
            $keyboard = [[['text' => '⬅️ Kembali', 'callback_data' => 'menu_admin:download']]];
            return editMessageOrCaption($data['message'] ?? [], "❌ <b>Gagal:</b> Tidak ada provider aktif untuk dikaitkan dengan download.", $keyboard);
        }

        $buttons = $providers->map(function ($row) use ($gameId) {
            return [
                'text'          => $row->name,
                'callback_data' => "menu_admin:download_create_provider:{$gameId}:{$row->id}",
            ];
        })->toArray();

        $keyboard   = array_chunk($buttons, 2);
        $keyboard[] = [
            ['text' => '❌ Batal', 'callback_data' => 'menu_admin:download'],
        ];

        return editMessageOrCaption($data['message'] ?? [], "📥 <b>Tambah Download - Langkah 2</b>\n\nGame: <b>{$game->name}</b>\n\nSilakan pilih <b>Provider</b>:", $keyboard);
    }

    private function handleDownloadCreateProviderCallback(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $gameId     = $parts[2] ?? null;
        $providerId = $parts[3] ?? null;

        $game     = Game::find($gameId);
        $provider = Provider::find($providerId);
        if (! $game || ! $provider) {
            return;
        }

        $user->session = "admin_create_download:data:{$gameId}:{$providerId}";
        $user->save();

        $prompt = "📥 <b>Tambah Download - Langkah 3 (Terakhir)</b>\n\n" .
            "Game: <b>{$game->name}</b>\n" .
            "Provider: <b>{$provider->name}</b>\n\n" .
            "✍️ Silakan kirimkan <b>Link & Judul Download</b>.\n\n" .
            "💡 <b>Format Pengiriman:</b>\n" .
            "1. <b>Format Teks biasa (Sangat disarankan):</b>\n" .
            "<code>https://link1.com | Judul Download 1</code>\n" .
            "<code>https://link2.com | Judul Download 2</code>\n\n" .
            "2. <b>Format JSON:</b>\n" .
            "<code>[{\"link\": \"https://gdrive...\", \"title\": \"via GDrive\"}]</code>\n\n" .
            "⚠️ <i>Ketik /cancel atau cancel untuk membatalkan.</i>";

        $keyboard = [
            [
                ['text' => '❌ Batal', 'callback_data' => 'menu_admin:download'],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $prompt, $keyboard);
    }

    private function handleDownloadEditPrompt(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $field      = $parts[2] ?? '';
        $downloadId = $parts[3] ?? null;

        $download = Download::find($downloadId);
        if (! $download) {
            return;
        }

        if ($field === 'game') {
            $games   = Game::where('status', 'active')->get();
            $buttons = $games->map(function ($row) use ($downloadId) {
                return [
                    'text'          => $row->name,
                    'callback_data' => "menu_admin:download_edit_game:{$downloadId}:{$row->id}",
                ];
            })->toArray();
            $keyboard   = array_chunk($buttons, 2);
            $keyboard[] = [['text' => '❌ Batal', 'callback_data' => "menu_admin:download_detail:{$downloadId}:all:1"]];
            return editMessageOrCaption($data['message'] ?? [], "📥 <b>Edit Game</b>\n\nSilakan pilih game baru untuk download ini:", $keyboard);
        }

        if ($field === 'provider') {
            $game         = Game::find($download->game_id);
            $connectedIds = $game->providers ?? [];
            $providers    = Provider::whereIn('id', $connectedIds)->where('status', 'active')->get();
            if ($providers->isEmpty()) {
                $providers = Provider::where('status', 'active')->get();
            }

            $buttons = $providers->map(function ($row) use ($downloadId) {
                return [
                    'text'          => $row->name,
                    'callback_data' => "menu_admin:download_edit_provider:{$downloadId}:{$row->id}",
                ];
            })->toArray();
            $keyboard   = array_chunk($buttons, 2);
            $keyboard[] = [['text' => '❌ Batal', 'callback_data' => "menu_admin:download_detail:{$downloadId}:all:1"]];
            return editMessageOrCaption($data['message'] ?? [], "📥 <b>Edit Provider</b>\n\nSilakan pilih provider baru untuk download ini:", $keyboard);
        }

        if ($field === 'data') {
            $user->session = "admin_edit_download:data:{$downloadId}";
            $user->save();

            $prompt = "✍️ Silakan kirimkan <b>Link & Judul Download Baru</b>.\n\n" .
                "💡 <b>Format Pengiriman:</b>\n" .
                "<code>https://link1.com | Judul Download 1</code>\n" .
                "<code>https://link2.com | Judul Download 2</code>\n\n" .
                "⚠️ <i>Ketik /cancel atau cancel untuk membatalkan.</i>";

            $keyboard = [
                [
                    ['text' => '❌ Batal', 'callback_data' => "menu_admin:download_detail:{$downloadId}:all:1"],
                ],
            ];

            return editMessageOrCaption($data['message'] ?? [], $prompt, $keyboard);
        }

        if ($field === 'tutorial') {
            $user->session = "admin_edit_download:tutorial:{$downloadId}";
            $user->save();

            $prompt = "✍️ Silakan kirimkan <b>Teks/Link Tutorial Baru</b>.\n\n" .
                "💡 <i>Ketik <code>-</code> untuk mengosongkan tutorial, atau ketik /cancel untuk membatalkan.</i>";

            $keyboard = [
                [
                    ['text' => '❌ Batal', 'callback_data' => "menu_admin:download_detail:{$downloadId}:all:1"],
                ],
            ];

            return editMessageOrCaption($data['message'] ?? [], $prompt, $keyboard);
        }
    }

    private function handleDownloadEditGameCallback(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $downloadId = $parts[2] ?? null;
        $gameId     = $parts[3] ?? null;

        $download = Download::find($downloadId);
        if ($download) {
            $download->game_id = $gameId;
            $download->save();
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], '✅ Game berhasil diubah.');
            }
        }
        return $this->handleDownloadDetail("menu_admin:download_detail:{$downloadId}:all:1", $data, $user, $config);
    }

    private function handleDownloadEditProviderCallback(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts      = explode(':', $callbackData);
        $downloadId = $parts[2] ?? null;
        $providerId = $parts[3] ?? null;

        $download = Download::find($downloadId);
        if ($download) {
            $download->provider_id = $providerId;
            $download->save();
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], '✅ Provider berhasil diubah.');
            }
        }
        return $this->handleDownloadDetail("menu_admin:download_detail:{$downloadId}:all:1", $data, $user, $config);
    }

    private function parseDownloadData(string $text)
    {
        $text = trim($text);
        if (str_starts_with($text, '[')) {
            $decoded = json_decode($text, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $lines = explode("\n", $text);
        $data  = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            if (str_contains($line, '|')) {
                $parts = explode('|', $line, 2);
                $link  = trim($parts[0] ?? '');
                $title = trim($parts[1] ?? '');
            } else {
                $parts = explode(' ', $line, 2);
                $link  = trim($parts[0] ?? '');
                $title = trim($parts[1] ?? 'Download Link');
            }

            if (! empty($link)) {
                $data[] = [
                    'link'  => $link,
                    'title' => $title ?: 'Download',
                ];
            }
        }
        return $data;
    }

    // ==========================================
    // PAYMENT CRUD FLOWS
    // ==========================================

    private function handlePaymentsList(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts  = explode(':', $callbackData);
        $filter = $parts[2] ?? 'all';
        $page   = intval($parts[3] ?? 1);

        $query = Payment::query()->orderBy('id', 'desc');
        if ($filter !== 'all') {
            if ($filter === 'active') {
                $query->where('status', 'active');
            } elseif ($filter === 'inactive') {
                $query->where('status', 'inactive');
            }
        }

        $limit      = 5;
        $offset     = ($page - 1) * $limit;
        $total      = $query->count();
        $payments   = $query->skip($offset)->take($limit)->get();
        $totalPages = ceil($total / $limit);
        if ($totalPages < 1) {
            $totalPages = 1;
        }

        $listPayments = '';
        $numButtons   = [];
        $index        = 1;

        foreach ($payments as $row) {
            $statusEmoji = ($row->status === 'active') ? '🟢' : '🔴';
            $feeStr      = 'Rp ' . number_format(floatval($row->fee_fixed), 0, ',', '.') . ' + ' . $row->fee_percent . '%';
            $numberStr   = $row->number ?: '-';

            $listPayments .= "<b>{$index}. {$row->name} [{$statusEmoji}]</b>\n" .
                "   • Code: <code>{$row->code}</code>\n" .
                "   • Provider: <code>{$row->provider}</code>\n" .
                "   • Fee: {$feeStr}\n" .
                "   • No Rek/Account: <code>{$numberStr}</code>\n\n";

            $numButtons[] = [
                'text' => " {$index} ",
                'callback_data' => "menu_admin:payment_detail:{$row->id}:{$filter}:{$page}",
            ];
            $index++;
        }

        if (empty($listPayments)) {
            $listPayments = "<i>Tidak ada metode pembayaran pada filter ini.</i>\n\n";
        }

        $keyboard = [];
        if (! empty($numButtons)) {
            $keyboard = array_chunk($numButtons, 5);
        }

        // Filter status row
        $filterButtons = [];
        foreach (['all' => 'Semua', 'active' => 'Aktif', 'inactive' => 'Nonaktif'] as $fKey => $fLabel) {
            $activeIndicator = ($filter === $fKey) ? '🔹 ' : '';
            $filterButtons[] = [
                'text'          => $activeIndicator . $fLabel,
                'callback_data' => "menu_admin:payment_list:{$fKey}:1",
            ];
        }
        $keyboard[] = $filterButtons;

        // Pagination row
        $paginationRow = [];
        if ($page > 1) {
            $paginationRow[] = [
                'text'          => '◀️ Prev',
                'callback_data' => "menu_admin:payment_list:{$filter}:" . ($page - 1),
            ];
        }

        $paginationRow[] = [
            'text' => "Hal {$page}/{$totalPages}",
            'callback_data' => 'current_page',
        ];

        if ($page < $totalPages) {
            $paginationRow[] = [
                'text'          => 'Next ▶️',
                'callback_data' => "menu_admin:payment_list:{$filter}:" . ($page + 1),
            ];
        }
        $keyboard[] = $paginationRow;

        $keyboard[] = [
            ['text' => '➕ Tambah Payment', 'callback_data' => 'menu_admin:payment_create'],
        ];
        $keyboard[] = [
            ['text' => '⬅️ Kembali ke Admin Menu', 'callback_data' => 'menu_admin'],
        ];

        $title = "💳 <b>Kelola Metode Pembayaran</b> (Halaman {$page}/{$totalPages})\n" .
            "• Filter: <b>" . strtoupper($filter) . "</b>\n" .
            "• Total Data: <b>{$total}</b>\n\n" .
            $listPayments .
            "Pilih nomor pembayaran di bawah ini untuk melihat detail/mengubah:";

        if (isset($data['message']['photo'])) {
            deleteMessage($data['message']['chat']['id'] ?? null, $data['message']['message_id'] ?? null);
            return sendMessage([
                'chat_id'  => $data['message']['chat']['id'] ?? null,
                'text'     => $title,
                'mode'     => 'HTML',
                'keyboard' => $keyboard,
            ]);
        }

        return editMessageOrCaption($data['message'] ?? [], $title, $keyboard);
    }

    private function handlePaymentDetail(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts     = explode(':', $callbackData);
        $paymentId = $parts[2] ?? null;
        $filter    = $parts[3] ?? 'all';
        $page      = intval($parts[4] ?? 1);

        $payment = Payment::find($paymentId);
        if (! $payment) {
            return;
        }

        $statusBadge = ($payment->status === 'active') ? '🟢 Aktif' : '🔴 Nonaktif';
        $feeFixed    = 'Rp ' . number_format(floatval($payment->fee_fixed), 0, ',', '.');
        $feePercent  = $payment->fee_percent . '%';
        $minStr      = 'Rp ' . number_format(floatval($payment->minimum), 0, ',', '.');
        $maxStr      = 'Rp ' . number_format(floatval($payment->maximum), 0, ',', '.');
        $numberVal   = $payment->number ?: '-';
        $accountVal  = $payment->name_account ?: '-';
        $imageVal    = $payment->image ?: '-';
        $instruksi   = $payment->instruksi ?: 'Tidak ada instruksi';

        $caption = "💳 <b>Detail Metode Pembayaran</b>\n\n" .
            "• <b>Nama Payment:</b> {$payment->name}\n" .
            "• <b>Kode Payment:</b> <code>{$payment->code}</code>\n" .
            "• <b>Provider:</b> <code>{$payment->provider}</code>\n" .
            "• <b>Fee Fixed:</b> {$feeFixed}\n" .
            "• <b>Fee Persen:</b> {$feePercent}\n" .
            "• <b>No Rekening/Account:</b> <code>{$numberVal}</code>\n" .
            "• <b>Nama Rekening/Account:</b> <code>{$accountVal}</code>\n" .
            "• <b>Logo Image URL:</b> <code>{$imageVal}</code>\n" .
            "• <b>Minimal Transaksi:</b> {$minStr}\n" .
            "• <b>Maksimal Transaksi:</b> {$maxStr}\n" .
            "• <b>Status:</b> {$statusBadge}\n\n" .
            "<b>Instruksi Pembayaran:</b>\n" .
            "<pre>" . htmlspecialchars($instruksi, ENT_QUOTES, 'UTF-8') . "</pre>\n\n" .
            "Pilih tombol di bawah ini untuk mengedit atau menghapus:";

        $keyboard = [
            [
                ['text' => '✏️ Edit Nama', 'callback_data' => "menu_admin:payment_edit:name:{$payment->id}"],
                ['text' => '✏️ Edit Kode', 'callback_data' => "menu_admin:payment_edit:code:{$payment->id}"],
            ],
            [
                ['text' => '✏️ Edit Provider', 'callback_data' => "menu_admin:payment_edit:provider:{$payment->id}"],
                ['text' => '✏️ Edit Fee Fixed', 'callback_data' => "menu_admin:payment_edit:fee_fixed:{$payment->id}"],
            ],
            [
                ['text' => '✏️ Edit Fee Persen', 'callback_data' => "menu_admin:payment_edit:fee_percent:{$payment->id}"],
                ['text' => '✏️ Edit No Rek', 'callback_data' => "menu_admin:payment_edit:number:{$payment->id}"],
            ],
            [
                ['text' => '✏️ Edit Nama Rek', 'callback_data' => "menu_admin:payment_edit:name_account:{$payment->id}"],
                ['text' => '✏️ Edit Logo URL', 'callback_data' => "menu_admin:payment_edit:image:{$payment->id}"],
            ],
            [
                ['text' => '✏️ Edit Min', 'callback_data' => "menu_admin:payment_edit:minimum:{$payment->id}"],
                ['text' => '✏️ Edit Max', 'callback_data' => "menu_admin:payment_edit:maximum:{$payment->id}"],
            ],
            [
                ['text' => '✏️ Edit Instruksi', 'callback_data' => "menu_admin:payment_edit:instruksi:{$payment->id}"],
                ['text' => '🔌 Toggle Status', 'callback_data' => "menu_admin:payment_toggle:{$payment->id}"],
            ],
            [
                ['text' => '🗑️ Hapus Payment', 'callback_data' => "menu_admin:payment_delete:{$payment->id}"],
            ],
            [
                ['text' => '⬅️ Kembali ke Daftar', 'callback_data' => "menu_admin:payment_list:{$filter}:{$page}"],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handlePaymentToggle(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts     = explode(':', $callbackData);
        $paymentId = $parts[2] ?? null;

        $payment = Payment::find($paymentId);
        if (! $payment) {
            return;
        }

        $payment->status = ($payment->status === 'active') ? 'inactive' : 'active';
        $payment->save();

        return $this->handlePaymentDetail("menu_admin:payment_detail:{$payment->id}:all:1", $data, $user, $config);
    }

    private function handlePaymentDeleteConfirm(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts     = explode(':', $callbackData);
        $paymentId = $parts[2] ?? null;

        $payment = Payment::find($paymentId);
        if (! $payment) {
            return;
        }

        $caption = "⚠️ <b>Konfirmasi Hapus Metode Pembayaran</b>\n\n" .
            "Apakah Anda yakin ingin menghapus metode pembayaran <b>{$payment->name}</b>?\n\n" .
            "<i>Tindakan ini tidak dapat dibatalkan!</i>";

        $keyboard = [
            [
                ['text' => '🗑️ Ya, Hapus', 'callback_data' => "menu_admin:payment_destroy:{$payment->id}"],
                ['text' => '❌ Batal', 'callback_data' => "menu_admin:payment_detail:{$payment->id}:all:1"],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handlePaymentDestroy(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts     = explode(':', $callbackData);
        $paymentId = $parts[2] ?? null;

        $payment = Payment::find($paymentId);
        if ($payment) {
            $payment->delete();
        }

        return $this->handlePaymentsList("menu_admin:payment_list:all:1", $data, $user, $config);
    }

    private function handlePaymentCreatePrompt(array $data, $user = null, $config = null)
    {
        $user->session = "admin_create_payment:name";
        $user->save();

        $prompt = "✍️ Silakan kirimkan <b>Nama Payment Baru</b> (contoh: <code>OVO</code> atau <code>BCA Transfer</code>):\n\n" .
            "⚠️ <i>Klik tombol Batal di bawah untuk membatalkan.</i>";

        $keyboard = [
            [
                ['text' => '❌ Batal', 'callback_data' => 'menu_admin:payment'],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $prompt, $keyboard);
    }

    private function handlePaymentCreateProviderCallback(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts    = explode(':', $callbackData);
        $provider = $parts[2] ?? 'manual';
        $name     = urldecode($parts[3] ?? '');
        $code     = urldecode($parts[4] ?? '');

        $user->session = "admin_create_payment:fee_fixed:" . urlencode($name) . ":" . urlencode($code) . ":{$provider}";
        $user->save();

        $keyboard = [[['text' => '❌ Batal', 'callback_data' => 'menu_admin:payment']]];

        return editMessageOrCaption(
            $data['message'] ?? [],
            "Nama: <b>{$name}</b>\nKode: <code>{$code}</code>\nProvider: <code>{$provider}</code>\n\n✍️ Silakan kirimkan <b>Fee Flat / Fixed</b> (hanya angka, contoh: <code>100</code> atau <code>0</code>):",
            $keyboard
        );
    }

    private function handlePaymentEditPrompt(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts     = explode(':', $callbackData);
        $field     = $parts[2] ?? '';
        $paymentId = $parts[3] ?? null;

        $payment = Payment::find($paymentId);
        if (! $payment) {
            return;
        }

        $user->session = "admin_edit_payment:{$field}:{$paymentId}";
        $user->save();

        $fieldLabel = match ($field) {
            'name'         => 'Nama Payment',
            'code'         => 'Kode Payment (contoh: QRIS, OVO, dll)',
            'provider'     => 'Provider Payment (manual, wijayapay, atau pakasir)',
            'fee_fixed'    => 'Fee Flat (hanya angka)',
            'fee_percent'  => 'Fee Persen (contoh: 0.7 atau 1.5)',
            'number'       => 'Nomor Rekening / Account (ketik - jika tidak ada)',
            'name_account' => 'Nama Pemilik Rekening / Account (ketik - jika tidak ada)',
            'image'        => 'Logo Image URL (ketik - jika tidak ada)',
            'minimum'      => 'Minimal Transaksi (hanya angka)',
            'maximum'      => 'Maksimal Transaksi (hanya angka)',
            'instruksi'    => 'Instruksi Pembayaran',
            default        => $field,
        };

        $prompt = "✍️ Silakan kirimkan <b>{$fieldLabel}</b> baru untuk metode pembayaran ini:\n\n" .
            "⚠️ <i>Klik tombol Batal di bawah untuk membatalkan.</i>";

        $keyboard = [
            [
                ['text' => '❌ Batal', 'callback_data' => "menu_admin:payment_detail:{$paymentId}:all:1"],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $prompt, $keyboard);
    }

    // ==========================================
    // HISTORY CRUD FLOWS
    // ==========================================

    private function handleHistoryList(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts  = explode(':', $callbackData);
        $filter = $parts[2] ?? 'all';
        $page   = intval($parts[3] ?? 1);

        $query = History::query()->orderBy('id', 'desc');
        if ($filter !== 'all') {
            if ($filter === 'pending') {
                $query->where(function ($q) {
                    $q->where('process_status', 'pending')
                        ->orWhere('payment_status', 'pending');
                });
            } elseif ($filter === 'paid') {
                $query->where('payment_status', 'paid');
            } elseif ($filter === 'failed') {
                $query->where('process_status', 'failed');
            }
        }

        $limit      = 10;
        $offset     = ($page - 1) * $limit;
        $total      = $query->count();
        $histories  = $query->skip($offset)->take($limit)->get();
        $totalPages = ceil($total / $limit);
        if ($totalPages < 1) {
            $totalPages = 1;
        }

        $listTransactions = '';
        $numButtons       = [];
        $index            = 1;

        foreach ($histories as $row) {
            $statusEmoji = '⚪';
            if (($row->process_status ?? '') === 'success') {
                $statusEmoji = '🟢';
            } elseif (in_array($row->process_status ?? '', ['paid', 'processing'])) {
                $statusEmoji = '🔵';
            } elseif (($row->process_status ?? '') === 'pending' || ($row->payment_status ?? '') === 'pending') {
                $statusEmoji = '🟡';
            } elseif (($row->process_status ?? '') === 'failed') {
                $statusEmoji = '🔴';
            }

            $product   = $row->product ?? [];
            $gameName  = $product['game']['name'] ?? 'Game';
            $denomName = $product['denom']['name'] ?? 'Denom';
            $priceStr  = 'Rp ' . number_format($row->price, 0, ',', '.');
            $buyerData = $row->temporary_data ?? [];
            $buyerName = trim(($buyerData['first_name'] ?? '') . ' ' . ($buyerData['last_name'] ?? ''));
            if (empty($buyerName)) {
                $buyerName = 'Pelanggan';
            }

            $listTransactions .= "<b>{$index}. Invoice:</b> <code>{$row->invoice_id}</code>\n" .
                "   • Pelanggan: {$buyerName}\n" .
                "   • Produk: {$gameName} ({$denomName})\n" .
                "   • Total: {$priceStr} [{$statusEmoji}]\n\n";

            $numButtons[] = [
                'text' => " {$index} ",
                'callback_data' => "menu_admin:history_detail:{$row->id}:{$filter}:{$page}",
            ];
            $index++;
        }

        if (empty($listTransactions)) {
            $listTransactions = "<i>Tidak ada riwayat transaksi pada filter ini.</i>\n\n";
        }

        $keyboard = [];
        if (! empty($numButtons)) {
            $keyboard = array_chunk($numButtons, 5);
        }

        // Filter status selector row
        $filterButtons = [];
        foreach (['all' => 'Semua', 'pending' => 'Pending', 'paid' => 'Paid', 'failed' => 'Failed'] as $fKey => $fLabel) {
            $activeIndicator = ($filter === $fKey) ? '🔹 ' : '';
            $filterButtons[] = [
                'text'          => $activeIndicator . $fLabel,
                'callback_data' => "menu_admin:history_list:{$fKey}:1",
            ];
        }
        $keyboard[] = $filterButtons;

        // Pagination buttons row
        $paginationRow = [];
        if ($page > 1) {
            $paginationRow[] = [
                'text'          => '◀️ Prev',
                'callback_data' => "menu_admin:history_list:{$filter}:" . ($page - 1),
            ];
        }

        $paginationRow[] = [
            'text' => "Hal {$page}/{$totalPages}",
            'callback_data' => 'current_page',
        ];

        if ($page < $totalPages) {
            $paginationRow[] = [
                'text'          => 'Next ▶️',
                'callback_data' => "menu_admin:history_list:{$filter}:" . ($page + 1),
            ];
        }
        $keyboard[] = $paginationRow;

        $keyboard[] = [
            ['text' => '⬅️ Kembali ke Admin Menu', 'callback_data' => 'menu_admin'],
        ];

        $title = "📜 <b>Kelola Transaksi & Riwayat</b> (Halaman {$page}/{$totalPages})\n" .
            "• Filter: <b>" . strtoupper($filter) . "</b>\n" .
            "• Total Data: <b>{$total}</b>\n\n" .
            $listTransactions .
            "Pilih nomor transaksi di bawah ini untuk melihat detail/mengubah:";

        if (isset($data['message']['photo'])) {
            deleteMessage($data['message']['chat']['id'] ?? null, $data['message']['message_id'] ?? null);
            return sendMessage([
                'chat_id'  => $data['message']['chat']['id'] ?? null,
                'text'     => $title,
                'mode'     => 'HTML',
                'keyboard' => $keyboard,
            ]);
        }

        return editMessageOrCaption($data['message'] ?? [], $title, $keyboard);
    }

    private function handleHistoryDetail(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts     = explode(':', $callbackData);
        $historyId = $parts[2] ?? null;
        $filter    = $parts[3] ?? 'all';
        $page      = intval($parts[4] ?? 1);

        $history = History::find($historyId);
        if (! $history) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Transaksi tidak ditemukan.');
            }
            return;
        }

        $buyerData = $history->temporary_data ?? [];
        $buyerName = trim(($buyerData['first_name'] ?? '') . ' ' . ($buyerData['last_name'] ?? ''));
        $buyerId   = $buyerData['userId'] ?? $history->user_id;

        $product      = $history->product ?? [];
        $gameName     = $product['game']['name'] ?? '-';
        $denomName    = $product['denom']['name'] ?? '-';
        $providerName = $product['provider']['name'] ?? '-';

        $paymentInfo = $history->payment ?? [];
        $paymentName = $paymentInfo['name'] ?? '-';

        $caption = "🧾 <b>Detail Transaksi</b>\n\n" .
            "• <b>Invoice ID:</b> <code>{$history->invoice_id}</code>\n" .
            "• <b>Pelanggan:</b> {$buyerName} (<code>{$buyerId}</code>)\n" .
            "• <b>Produk:</b> {$gameName} - {$denomName} (Provider: {$providerName})\n" .
            "• <b>Metode Pembayaran:</b> {$paymentName}\n" .
            "• <b>Total Bayar:</b> Rp " . number_format($history->price, 0, ',', '.') . "\n" .
            "• <b>Status Pembayaran:</b> <code>" . strtoupper($history->payment_status ?? 'unpaid') . "</code>\n" .
            "• <b>Status Proses:</b> <code>" . strtoupper($history->process_status ?? 'pending') . "</code>\n" .
            "• <b>Catatan / Lisensi:</b>\n<pre>" . htmlspecialchars($history->notes ?? 'Tidak ada catatan', ENT_QUOTES, 'UTF-8') . "</pre>\n" .
            "• <b>Waktu Dibuat:</b> {$history->created_at}\n\n" .
            "Silakan gunakan menu di bawah untuk mengelola status transaksi:";

        $keyboard = [
            [
                ['text' => '💸 Set PAID & SUCCESS', 'callback_data' => "menu_admin:history_status:{$historyId}:success"],
                ['text' => '❌ Set FAILED', 'callback_data' => "menu_admin:history_status:{$historyId}:failed"],
            ],
            [
                ['text' => '⏳ Set PENDING', 'callback_data' => "menu_admin:history_status:{$historyId}:pending"],
                ['text' => '✍️ Edit Notes / Key', 'callback_data' => "menu_admin:history_edit_notes:{$historyId}"],
            ],
            [
                ['text' => '🗑️ Hapus Transaksi', 'callback_data' => "menu_admin:history_delete:{$historyId}"],
            ],
            [
                ['text' => '⬅️ Kembali ke Daftar', 'callback_data' => "menu_admin:history_list:{$filter}:{$page}"],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handleHistoryStatus(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts     = explode(':', $callbackData);
        $historyId = $parts[2] ?? null;
        $status    = $parts[3] ?? '';

        $history = History::find($historyId);
        if (! $history) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Transaksi tidak ditemukan.');
            }
            return;
        }

        if ($status === 'success') {
            $history->payment_status = 'paid';
            $history->process_status = 'success';
            $history->paid_at        = now();
        } elseif ($status === 'failed') {
            $history->process_status = 'failed';
        } elseif ($status === 'pending') {
            $history->payment_status = 'pending';
            $history->process_status = 'pending';
        }

        $history->save();

        if (isset($data['id'])) {
            $this->answerCallbackQuery($data['id'], "✅ Status berhasil diubah!");
        }

        // Return back to detail page, using fake detail callback
        $fakeCallback = "menu_admin:history_detail:{$historyId}:all:1";
        return $this->handleHistoryDetail($fakeCallback, $data, $user, $config);
    }

    private function handleHistoryDeleteConfirm(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts     = explode(':', $callbackData);
        $historyId = $parts[2] ?? null;

        $history = History::find($historyId);
        if (! $history) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Transaksi tidak ditemukan.');
            }
            return;
        }

        $caption = "⚠️ <b>Konfirmasi Hapus Transaksi</b>\n\n" .
            "Apakah Anda yakin ingin menghapus transaksi <b>{$history->invoice_id}</b>?\n\n" .
            "<i>Tindakan ini tidak dapat dibatalkan.</i>";

        $keyboard = [
            [
                ['text' => '🔴 Ya, Hapus', 'callback_data' => "menu_admin:history_destroy:{$historyId}"],
                ['text' => '🟢 Batal', 'callback_data' => "menu_admin:history_detail:{$historyId}:all:1"],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $caption, $keyboard);
    }

    private function handleHistoryDestroy(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts     = explode(':', $callbackData);
        $historyId = $parts[2] ?? null;

        $history = History::find($historyId);
        if (! $history) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Transaksi tidak ditemukan.');
            }
            return;
        }

        $history->delete();

        if (isset($data['id'])) {
            $this->answerCallbackQuery($data['id'], '✅ Transaksi berhasil dihapus!');
        }

        return $this->handleHistoryList("menu_admin:history_list:all:1", $data, $user, $config);
    }

    private function handleHistoryEditNotesPrompt(string $callbackData, array $data, $user = null, $config = null)
    {
        $parts     = explode(':', $callbackData);
        $historyId = $parts[2] ?? null;

        $history = History::find($historyId);
        if (! $history) {
            if (isset($data['id'])) {
                $this->answerCallbackQuery($data['id'], 'Transaksi tidak ditemukan.');
            }
            return;
        }

        $user->session = "admin_edit_history:notes:{$historyId}";
        $user->save();

        $prompt = "✍️ Silakan kirimkan <b>Catatan / Lisensi Key</b> baru untuk transaksi <code>{$history->invoice_id}</code>:\n\n" .
            "⚠️ <i>Catatan ini akan dikirim/dapat dilihat oleh pembeli. Klik Batal untuk membatalkan.</i>";

        $keyboard = [
            [
                ['text' => '❌ Batal', 'callback_data' => "menu_admin:history_detail:{$historyId}:all:1"],
            ],
        ];

        return editMessageOrCaption($data['message'] ?? [], $prompt, $keyboard);
    }

    private function answerCallbackQuery($callbackQueryId, $text = null)
    {
        $params = [
            'callback_query_id' => $callbackQueryId,
        ];
        if ($text) {
            $params['text'] = $text;
        }
        return sendRequest('answerCallbackQuery', $params);
    }

    /**
     * Handle admin text input when in an active edit/create session.
     */
    public function handleAdminSession(string $message, int $chatID, array $dataMessage, $user = null, $config = null)
    {
        if (isset($dataMessage['message_id'])) {
            deleteMessage($chatID, $dataMessage['message_id']);
        }

        if (! $user || $user->role !== 'admin') {
            return;
        }

        if (strtolower(trim($message)) === '/cancel' || strtolower(trim($message)) === 'cancel') {
            $user->session = null;
            $user->save();

            $keyboard = [
                [
                    ['text' => '⬅️ Menu Admin', 'callback_data' => 'menu_admin'],
                ],
            ];

            return sendMessage([
                'chat_id'  => $chatID,
                'text'     => "❌ <b>Perubahan dibatalkan.</b>",
                'mode'     => 'HTML',
                'keyboard' => $keyboard,
            ]);
        }

        // ----------------------------------------------------
        // Add Stock Session
        // ----------------------------------------------------
        if (str_starts_with($user->session, 'admin_stock_add:')) {
            $parts   = explode(':', $user->session);
            $denomId = $parts[1] ?? null;
            $denom   = Denom::find($denomId);

            if (! $denom) {
                $user->session = null;
                $user->save();
                return sendMessage([
                    'chat_id' => $chatID,
                    'text'    => "❌ Denom tidak ditemukan.",
                ]);
            }

            $lines = explode("\n", $message);
            $inserted = 0;
            foreach ($lines as $line) {
                $license = trim($line);
                if ($license !== '') {
                    Stock::create([
                        'denom_id' => $denomId,
                        'license'  => $license,
                        'status'   => 'ready',
                    ]);
                    $inserted++;
                }
            }

            $user->session = null;
            $user->save();

            $keyboard = [
                [
                    ['text' => '⬅️ Kelola Stok', 'callback_data' => "menu_admin:stock:manage:{$denomId}"],
                ],
            ];

            return sendMessage([
                'chat_id' => $chatID,
                'text'    => "✅ <b>Berhasil Menambahkan Stok!</b>\n\n• Produk: <b>{$denom->name}</b>\n• Jumlah Lisensi Ditambahkan: <b>{$inserted} item</b>",
                'mode'    => 'HTML',
                'keyboard' => $keyboard,
            ]);
        }

        // ----------------------------------------------------
        // Add Provider Custom Data Session
        // ----------------------------------------------------
        if (str_starts_with($user->session, 'admin_provider_custom_data_value:')) {
            $parts      = explode(':', $user->session);
            $providerId = $parts[1] ?? null;
            $gameId     = $parts[2] ?? null;
            $key        = $parts[3] ?? null;

            $provider = Provider::find($providerId);
            $game     = Game::find($gameId);

            if (! $provider || ! $game) {
                $user->session = null;
                $user->save();
                return sendMessage([
                    'chat_id' => $chatID,
                    'text'    => "❌ Provider atau Game tidak ditemukan.",
                ]);
            }

            if (empty(trim($message))) {
                return sendMessage([
                    'chat_id' => $chatID,
                    'text'    => "❌ <b>Validasi Gagal:</b>\nValue tidak boleh kosong.\n\nSilakan kirimkan kembali value yang valid, atau ketik /cancel untuk batal.",
                    'mode'    => 'HTML',
                ]);
            }

            $value = trim($message);
            if ($key === 'c_gameid') {
                if (is_numeric($value)) {
                    $value = intval($value);
                }
            }

            $customData = $provider->custom_data ?? [];

            $updated = false;
            foreach ($customData as &$item) {
                if (($item['game_id'] ?? null) == $gameId && ($item['key'] ?? null) === $key) {
                    $item['value'] = $value;
                    $updated       = true;
                    break;
                }
            }
            unset($item);

            if (! $updated) {
                $customData[] = [
                    'key'     => $key,
                    'game_id' => intval($gameId),
                    'value'   => $value,
                ];
            }

            $provider->custom_data = $customData;
            $provider->save();

            $user->session = null;
            $user->save();

            $keyboard = [
                [
                    ['text' => '⬅️ Kelola Custom Data', 'callback_data' => "menu_admin:provider_custom_data:{$providerId}"],
                ],
            ];

            return sendMessage([
                'chat_id' => $chatID,
                'text'    => "✅ <b>Custom Data Berhasil Ditambahkan/Diperbarui!</b>\n\n• <b>Provider:</b> {$provider->name}\n• <b>Game:</b> {$game->name}\n• <b>Key:</b> <code>{$key}</code>\n• <b>Value:</b> <code>" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . "</code>",
                'mode' => 'HTML',
                'keyboard' => $keyboard,
            ]);
        }

        // ----------------------------------------------------
        // Create Download Wizard Sessions
        // ----------------------------------------------------
        if (str_starts_with($user->session, 'admin_create_download:')) {
            $parts      = explode(':', $user->session);
            $step       = $parts[1] ?? '';
            $gameId     = $parts[2] ?? null;
            $providerId = $parts[3] ?? null;

            if ($step === 'data') {
                $parsedData = $this->parseDownloadData($message);

                if (empty($parsedData)) {
                    return sendMessage([
                        'chat_id' => $chatID,
                        'text'    => "❌ <b>Validasi Gagal:</b>\nFormat link tidak valid.\n\nSilakan kirimkan kembali link dengan format yang benar, atau ketik /cancel untuk batal.",
                        'mode'    => 'HTML',
                    ]);
                }

                $download = Download::create([
                    'game_id'     => $gameId,
                    'provider_id' => $providerId,
                    'data'        => $parsedData,
                    'status'      => 'active',
                ]);

                $user->session = null;
                $user->save();

                $keyboard = [
                    [
                        ['text' => '⬅️ Kembali ke Daftar', 'callback_data' => 'menu_admin:download'],
                        ['text' => '🔍 Detail Download', 'callback_data' => "menu_admin:download_detail:{$download->id}"],
                    ],
                ];

                return sendMessage([
                    'chat_id' => $chatID,
                    'text'    => "✅ <b>Konfigurasi Download Berhasil Dibuat!</b>\n\n• ID: <code>{$download->id}</code>\n• Game ID: <code>{$download->game_id}</code>\n• Provider ID: <code>{$download->provider_id}</code>\n• Jumlah Link: <code>" . count($parsedData) . " link</code>",
                    'mode' => 'HTML',
                    'keyboard' => $keyboard,
                ]);
            }
        }

        // ----------------------------------------------------
        // Edit Download Sessions
        // ----------------------------------------------------
        if (str_starts_with($user->session, 'admin_edit_download:')) {
            $parts      = explode(':', $user->session);
            $field      = $parts[1] ?? '';
            $downloadId = $parts[2] ?? null;

            $download = Download::find($downloadId);
            if (! $download) {
                $user->session = null;
                $user->save();
                return sendMessage([
                    'chat_id' => $chatID,
                    'text'    => "❌ <b>Gagal:</b> Konfigurasi download tidak ditemukan.",
                    'mode'    => 'HTML',
                ]);
            }

            if ($field === 'data') {
                $parsedData = $this->parseDownloadData($message);

                if (empty($parsedData)) {
                    return sendMessage([
                        'chat_id' => $chatID,
                        'text'    => "❌ <b>Validasi Gagal:</b>\nFormat link tidak valid.\n\nSilakan kirimkan kembali link dengan format yang benar, atau ketik /cancel untuk batal.",
                        'mode'    => 'HTML',
                    ]);
                }

                $download->data = $parsedData;
                $download->save();

                $user->session = null;
                $user->save();

                $keyboard = [
                    [
                        ['text' => '⬅️ Kembali ke Detail', 'callback_data' => "menu_admin:download_detail:{$download->id}"],
                    ],
                ];

                return sendMessage([
                    'chat_id'  => $chatID,
                    'text'     => "✅ <b>Link Download Berhasil Diubah!</b>\n\n• Jumlah Link Sekarang: <code>" . count($parsedData) . " link</code>",
                    'mode'     => 'HTML',
                    'keyboard' => $keyboard,
                ]);
            }

            if ($field === 'tutorial') {
                $tutVal = trim($message);
                if ($tutVal === '-') {
                    $download->tutorial = null;
                } else {
                    $download->tutorial = $tutVal;
                }
                $download->save();

                $user->session = null;
                $user->save();

                $keyboard = [
                    [
                        ['text' => '⬅️ Kembali ke Detail', 'callback_data' => "menu_admin:download_detail:{$download->id}"],
                    ],
                ];

                return sendMessage([
                    'chat_id'  => $chatID,
                    'text'     => "✅ <b>Tutorial Berhasil Diubah!</b>",
                    'mode'     => 'HTML',
                    'keyboard' => $keyboard,
                ]);
            }
        }

        // ----------------------------------------------------
        // Broadcast Message Session
        // ----------------------------------------------------
        if (str_starts_with($user->session, 'admin_broadcast:message')) {
            $user->session = null;
            $user->save();

            // Check if this is a reply to another message
            $replyToMessage = $dataMessage['reply_to_message'] ?? null;
            if ($replyToMessage) {
                $fromChatId      = $replyToMessage['chat']['id'] ?? $chatID;
                $messageIdToCopy = $replyToMessage['message_id'];
                $previewText     = $replyToMessage['text'] ?? $replyToMessage['caption'] ?? '[Pesan Reply / Media]';
            } else {
                $fromChatId      = $chatID;
                $messageIdToCopy = $dataMessage['message_id'] ?? null;
                $previewText     = $message ?: ($dataMessage['caption'] ?? '[Pesan Media]');
            }

            if (! $messageIdToCopy) {
                $keyboard = [
                    [
                        ['text' => '⬅️ Menu Admin', 'callback_data' => 'menu_admin'],
                    ],
                ];
                return sendMessage([
                    'chat_id'  => $chatID,
                    'text'     => "❌ <b>Gagal:</b> Pesan tidak valid untuk di-broadcast.",
                    'mode'     => 'HTML',
                    'keyboard' => $keyboard,
                ]);
            }

            $allUsers     = User::where('id', '!=', $user->id)->get();
            $successCount = 0;
            $failCount    = 0;

            foreach ($allUsers as $u) {
                if (! $u->user_id) {
                    continue;
                }

                $res = copyMessage($u->user_id, $fromChatId, $messageIdToCopy);

                if ($res) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            }

            $keyboard = [
                [
                    ['text' => '⬅️ Menu Admin', 'callback_data' => 'menu_admin'],
                ],
            ];

            return sendMessage([
                'chat_id' => $chatID,
                'text'    => "📢 <b>Hasil Broadcast Pesan:</b>\n\n" .
                    "📝 <b>Preview:</b>\n<i>" . htmlspecialchars($previewText, ENT_QUOTES, 'UTF-8') . "</i>\n\n" .
                    "————————————————————————\n\n" .
                    "• Berhasil dikirim: <b>{$successCount} user</b>\n" .
                    "• Gagal dikirim: <b>{$failCount} user</b>\n\n" .
                    "<i>Proses broadcast telah selesai.</i>",
                'mode'     => 'HTML',
                'keyboard' => $keyboard,
            ]);
        }

        // ----------------------------------------------------
        // Create Payment Wizard Sessions
        // ----------------------------------------------------
        if (str_starts_with($user->session, 'admin_create_payment:')) {
            $parts = explode(':', $user->session);
            $step  = $parts[1] ?? '';

            if ($step === 'name') {
                if (empty(trim($message))) {
                    return sendMessage([
                        'chat_id' => $chatID,
                        'text'    => "❌ <b>Validasi Gagal:</b>\nNama tidak boleh kosong.\n\nSilakan kirimkan kembali nama yang valid, atau ketik /cancel untuk batal.",
                        'mode'    => 'HTML',
                    ]);
                }

                $user->session = "admin_create_payment:code:" . urlencode($message);
                $user->save();

                $keyboard = [[['text' => '❌ Batal', 'callback_data' => 'menu_admin:payment']]];

                return sendMessage([
                    'chat_id'  => $chatID,
                    'text'     => "Nama Payment: <b>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</b>\n\n✍️ Silakan kirimkan <b>Kode Payment Baru</b> (contoh: <code>QRIS</code>, <code>OVO</code>, <code>BCA</code>):",
                    'mode'     => 'HTML',
                    'keyboard' => $keyboard,
                ]);
            } elseif ($step === 'code') {
                $name = urldecode($parts[2] ?? '');

                if (empty(trim($message))) {
                    return sendMessage([
                        'chat_id' => $chatID,
                        'text'    => "❌ <b>Validasi Gagal:</b>\nKode tidak boleh kosong.\n\nSilakan kirimkan kembali kode yang valid, atau ketik /cancel untuk batal.",
                        'mode'    => 'HTML',
                    ]);
                }

                $code = strtoupper(trim($message));

                // Advance to provider step, showing options manual, wijayapay, and pakasir
                $user->session = "admin_create_payment:provider:" . urlencode($name) . ":" . urlencode($code);
                $user->save();

                $keyboard = [
                    [
                        ['text' => 'manual', 'callback_data' => "menu_admin:payment_create_provider:manual:" . urlencode($name) . ":" . urlencode($code)],
                        ['text' => 'wijayapay', 'callback_data' => "menu_admin:payment_create_provider:wijayapay:" . urlencode($name) . ":" . urlencode($code)],
                        ['text' => 'pakasir', 'callback_data' => "menu_admin:payment_create_provider:pakasir:" . urlencode($name) . ":" . urlencode($code)],
                    ],
                    [['text' => '❌ Batal', 'callback_data' => 'menu_admin:payment']],
                ];

                return sendMessage([
                    'chat_id' => $chatID,
                    'text'    => "Nama: <b>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</b>\nKode: <code>{$code}</code>\n\n✍️ Silakan pilih <b>Provider Payment</b> (manual, wijayapay, atau pakasir) di bawah atau ketik nilainya langsung:",
                    'mode'     => 'HTML',
                    'keyboard' => $keyboard,
                ]);
            } elseif ($step === 'provider') {
                $name = urldecode($parts[2] ?? '');
                $code = urldecode($parts[3] ?? '');

                $provider = strtolower(trim($message));
                if ($provider !== 'manual' && $provider !== 'wijayapay' && $provider !== 'pakasir') {
                    return sendMessage([
                        'chat_id' => $chatID,
                        'text'    => "❌ <b>Validasi Gagal:</b>\nProvider harus berupa 'manual', 'wijayapay', atau 'pakasir'.\n\nSilakan kirimkan kembali provider yang valid, atau ketik /cancel untuk batal.",
                        'mode'    => 'HTML',
                    ]);
                }

                // Advance to fee_fixed step
                $user->session = "admin_create_payment:fee_fixed:" . urlencode($name) . ":" . urlencode($code) . ":{$provider}";
                $user->save();

                $keyboard = [[['text' => '❌ Batal', 'callback_data' => 'menu_admin:payment']]];

                return sendMessage([
                    'chat_id' => $chatID,
                    'text'    => "Nama: <b>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</b>\nKode: <code>{$code}</code>\nProvider: <code>{$provider}</code>\n\n✍️ Silakan kirimkan <b>Fee Flat / Fixed</b> (hanya angka, contoh: <code>100</code> atau <code>0</code>):",
                    'mode' => 'HTML',
                    'keyboard' => $keyboard,
                ]);
            } elseif ($step === 'fee_fixed') {
                $name     = urldecode($parts[2] ?? '');
                $code     = urldecode($parts[3] ?? '');
                $provider = urldecode($parts[4] ?? '');

                if (! is_numeric($message) || floatval($message) < 0) {
                    return sendMessage([
                        'chat_id' => $chatID,
                        'text'    => "❌ <b>Validasi Gagal:</b>\nFee flat harus berupa angka minimal 0.\n\nSilakan kirimkan kembali nilai yang valid, atau ketik /cancel untuk batal.",
                        'mode'    => 'HTML',
                    ]);
                }

                $feeFixed = floatval($message);

                // Advance to fee_percent step
                $user->session = "admin_create_payment:fee_percent:" . urlencode($name) . ":" . urlencode($code) . ":{$provider}:{$feeFixed}";
                $user->save();

                $keyboard = [[['text' => '❌ Batal', 'callback_data' => 'menu_admin:payment']]];

                return sendMessage([
                    'chat_id'  => $chatID,
                    'text'     => "Nama: <b>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</b>\nFee Flat: <b>Rp " . number_format($feeFixed, 0, ',', '.') . "</b>\n\n✍️ Silakan kirimkan <b>Fee Persen</b> (contoh: <code>0.7</code> atau <code>1.5</code> atau <code>0</code>):",
                    'mode'     => 'HTML',
                    'keyboard' => $keyboard,
                ]);
            } elseif ($step === 'fee_percent') {
                $name     = urldecode($parts[2] ?? '');
                $code     = urldecode($parts[3] ?? '');
                $provider = urldecode($parts[4] ?? '');
                $feeFixed = floatval($parts[5] ?? 0);

                if (! is_numeric($message) || floatval($message) < 0) {
                    return sendMessage([
                        'chat_id' => $chatID,
                        'text'    => "❌ <b>Validasi Gagal:</b>\nFee persen harus berupa angka minimal 0.\n\nSilakan kirimkan kembali nilai yang valid, atau ketik /cancel untuk batal.",
                        'mode'    => 'HTML',
                    ]);
                }

                $feePercent = floatval($message);

                // Advance to number step
                $user->session = "admin_create_payment:number:" . urlencode($name) . ":" . urlencode($code) . ":{$provider}:{$feeFixed}:{$feePercent}";
                $user->save();

                $keyboard = [[['text' => '❌ Batal', 'callback_data' => 'menu_admin:payment']]];

                return sendMessage([
                    'chat_id' => $chatID,
                    'text'    => "Nama: <b>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</b>\nFee Persen: <b>{$feePercent}%</b>\n\n✍️ Silakan kirimkan <b>Nomor Rekening / Account</b> (atau ketik <code>-</code> jika tidak ada/menggunakan default):",
                    'mode'     => 'HTML',
                    'keyboard' => $keyboard,
                ]);
            } elseif ($step === 'number') {
                $name       = urldecode($parts[2] ?? '');
                $code       = urldecode($parts[3] ?? '');
                $provider   = urldecode($parts[4] ?? '');
                $feeFixed   = floatval($parts[5] ?? 0);
                $feePercent = floatval($parts[6] ?? 0);

                $numberVal = (trim($message) === '-') ? '' : trim($message);

                // Advance to name_account step
                $user->session = "admin_create_payment:name_account:" . urlencode($name) . ":" . urlencode($code) . ":{$provider}:{$feeFixed}:{$feePercent}:" . urlencode($numberVal);
                $user->save();

                $keyboard = [[['text' => '❌ Batal', 'callback_data' => 'menu_admin:payment']]];

                return sendMessage([
                    'chat_id'  => $chatID,
                    'text'     => "Nama: <b>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</b>\nNo Rekening: <code>" . ($numberVal ?: '-') . "</code>\n\n✍️ Silakan kirimkan <b>Nama Pemilik Rekening / Account</b> (atau ketik <code>-</code> jika tidak ada/menggunakan default):",
                    'mode'     => 'HTML',
                    'keyboard' => $keyboard,
                ]);
            } elseif ($step === 'name_account') {
                $name       = urldecode($parts[2] ?? '');
                $code       = urldecode($parts[3] ?? '');
                $provider   = urldecode($parts[4] ?? '');
                $feeFixed   = floatval($parts[5] ?? 0);
                $feePercent = floatval($parts[6] ?? 0);
                $numberVal  = urldecode($parts[7] ?? '');

                $accountVal = (trim($message) === '-') ? '' : trim($message);

                // Advance to image step
                $user->session = "admin_create_payment:image:" . urlencode($name) . ":" . urlencode($code) . ":{$provider}:{$feeFixed}:{$feePercent}:" . urlencode($numberVal) . ":" . urlencode($accountVal);
                $user->save();

                $keyboard = [[['text' => '❌ Batal', 'callback_data' => 'menu_admin:payment']]];

                return sendMessage([
                    'chat_id'  => $chatID,
                    'text'     => "Nama: <b>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</b>\nNama Pemilik: <code>" . ($accountVal ?: '-') . "</code>\n\n✍️ Silakan kirimkan <b>Logo Image URL</b> (atau ketik <code>-</code> jika tidak ada):",
                    'mode'     => 'HTML',
                    'keyboard' => $keyboard,
                ]);
            } elseif ($step === 'image') {
                $name       = urldecode($parts[2] ?? '');
                $code       = urldecode($parts[3] ?? '');
                $provider   = urldecode($parts[4] ?? '');
                $feeFixed   = floatval($parts[5] ?? 0);
                $feePercent = floatval($parts[6] ?? 0);
                $numberVal  = urldecode($parts[7] ?? '');
                $accountVal = urldecode($parts[8] ?? '');

                $imageVal = trim($message);
                if ($imageVal !== '-') {
                    if (! filter_var($imageVal, FILTER_VALIDATE_URL)) {
                        return sendMessage([
                            'chat_id' => $chatID,
                            'text'    => "❌ <b>Validasi Gagal:</b>\nFormat URL gambar tidak valid. Harus dimulai dengan http:// atau https://\n\nSilakan kirimkan kembali URL logo yang valid, atau ketik /cancel untuk batal.",
                            'mode'    => 'HTML',
                        ]);
                    }
                } else {
                    $imageVal = '';
                }

                // Advance to instruksi step
                $user->session = "admin_create_payment:instruksi:" . urlencode($name) . ":" . urlencode($code) . ":{$provider}:{$feeFixed}:{$feePercent}:" . urlencode($numberVal) . ":" . urlencode($accountVal) . ":" . urlencode($imageVal);
                $user->save();

                $keyboard = [[['text' => '❌ Batal', 'callback_data' => 'menu_admin:payment']]];

                return sendMessage([
                    'chat_id'  => $chatID,
                    'text'     => "Nama: <b>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</b>\nLogo: <code>" . ($imageVal ?: '-') . "</code>\n\n✍️ Silakan kirimkan <b>Instruksi Pembayaran</b> (atau ketik <code>-</code> jika tidak ada):",
                    'mode'     => 'HTML',
                    'keyboard' => $keyboard,
                ]);
            } elseif ($step === 'instruksi') {
                $name       = urldecode($parts[2] ?? '');
                $code       = urldecode($parts[3] ?? '');
                $provider   = urldecode($parts[4] ?? '');
                $feeFixed   = floatval($parts[5] ?? 0);
                $feePercent = floatval($parts[6] ?? 0);
                $numberVal  = urldecode($parts[7] ?? '');
                $accountVal = urldecode($parts[8] ?? '');
                $imageVal   = urldecode($parts[9] ?? '');

                $instruksiVal = (trim($message) === '-') ? '' : trim($message);

                // Create the Payment Method
                $payment = Payment::create([
                    'name'         => $name,
                    'code'         => $code,
                    'provider'     => $provider,
                    'fee_fixed'    => $feeFixed,
                    'fee_percent'  => $feePercent,
                    'number'       => $numberVal,
                    'name_account' => $accountVal,
                    'image'        => $imageVal,
                    'instruksi'    => $instruksiVal,
                    'minimum'      => 1,
                    'maximum'      => 9999999,
                    'deposit'      => "1",
                    'pin'          => "1",
                    'category_id'  => 2,
                    'status'       => 'active',
                ]);

                // Reset session
                $user->session = null;
                $user->save();

                $keyboard = [
                    [
                        ['text' => '⬅️ Kembali ke Daftar', 'callback_data' => 'menu_admin:payment'],
                        ['text' => '🔍 Detail Payment Baru', 'callback_data' => "menu_admin:payment_detail:{$payment->id}:all:1"],
                    ],
                ];

                return sendMessage([
                    'chat_id' => $chatID,
                    'text'    => "✅ <b>Metode Pembayaran Baru Berhasil Dibuat!</b>\n\n• <b>Nama:</b> {$payment->name}\n• <b>Kode:</b> {$payment->code}\n• <b>Provider:</b> {$payment->provider}\n• <b>Fee:</b> Rp " . number_format($payment->fee_fixed, 0, ',', '.') . " + {$payment->fee_percent}%\n• <b>Status:</b> 🟢 Aktif",
                    'mode' => 'HTML',
                    'keyboard' => $keyboard,
                ]);
            }
        }

        // ----------------------------------------------------
        // Edit Payment Sessions
        // ----------------------------------------------------
        if (str_starts_with($user->session, 'admin_edit_payment:')) {
            $parts     = explode(':', $user->session);
            $field     = $parts[1] ?? '';
            $paymentId = $parts[2] ?? null;

            $payment = Payment::find($paymentId);
            if (! $payment) {
                $user->session = null;
                $user->save();
                return sendMessage([
                    'chat_id' => $chatID,
                    'text'    => "❌ Metode pembayaran tidak ditemukan.",
                ]);
            }

            $error = null;
            $val   = $message;

            if ($field === 'name' || $field === 'code' || $field === 'provider') {
                if (empty(trim($message))) {
                    $error = 'Nilai tidak boleh kosong.';
                }
                if ($field === 'provider') {
                    $cleanProv = strtolower(trim($message));
                    if ($cleanProv !== 'manual' && $cleanProv !== 'wijayapay' && $cleanProv !== 'pakasir') {
                        $error = 'Provider harus berupa "manual", "wijayapay", atau "pakasir".';
                    } else {
                        $val = $cleanProv;
                    }
                }
            } elseif ($field === 'fee_fixed') {
                if (! is_numeric($message) || floatval($message) < 0) {
                    $error = 'Fee flat harus berupa angka minimal 0.';
                } else {
                    $val = floatval($message);
                }
            } elseif ($field === 'fee_percent') {
                if (! is_numeric($message) || floatval($message) < 0) {
                    $error = 'Fee persen harus berupa angka minimal 0.';
                } else {
                    $val = floatval($message);
                }
            } elseif ($field === 'minimum' || $field === 'maximum') {
                if (! is_numeric($message) || floatval($message) < 0) {
                    $error = 'Nilai transaksi harus berupa angka minimal 0.';
                } else {
                    $val = floatval($message);
                }
            } elseif ($field === 'image') {
                if (trim($message) !== '-') {
                    if (! filter_var($message, FILTER_VALIDATE_URL)) {
                        $error = 'Format URL gambar tidak valid. Harus dimulai dengan http:// atau https://';
                    }
                } else {
                    $val = '';
                }
            } elseif ($field === 'number' || $field === 'name_account' || $field === 'instruksi') {
                if (trim($message) === '-') {
                    $val = '';
                }
            }

            if ($error) {
                return sendMessage([
                    'chat_id' => $chatID,
                    'text'    => "❌ <b>Validasi Gagal:</b>\n{$error}\n\nSilakan kirim kembali nilai yang valid, atau ketik <code>/cancel</code> untuk batal.",
                    'mode' => 'HTML',
                ]);
            }

            // Save details
            $payment->{$field} = $val;
            $payment->save();

            $user->session = null;
            $user->save();

            $keyboard = [
                [
                    ['text' => '⬅️ Kembali ke Detail', 'callback_data' => "menu_admin:payment_detail:{$paymentId}:all:1"],
                    ['text' => '💳 Daftar Payment', 'callback_data' => 'menu_admin:payment'],
                ],
            ];

            return sendMessage([
                'chat_id'  => $chatID,
                'text'     => "✅ <b>Metode Pembayaran Berhasil Diperbarui!</b>\n\nField <b>" . strtoupper($field) . "</b> telah diubah menjadi:\n<code>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</code>",
                'mode'     => 'HTML',
                'keyboard' => $keyboard,
            ]);
        }

        // ----------------------------------------------------
        // Edit History Sessions
        // ----------------------------------------------------
        if (str_starts_with($user->session, 'admin_edit_history:')) {
            $parts     = explode(':', $user->session);
            $field     = $parts[1] ?? '';
            $historyId = $parts[2] ?? null;

            $history = History::find($historyId);
            if (! $history) {
                $user->session = null;
                $user->save();
                return sendMessage([
                    'chat_id' => $chatID,
                    'text'    => "❌ Transaksi tidak ditemukan.",
                ]);
            }

            if ($field === 'notes') {
                $history->notes = $message;
                $history->save();
            }

            $user->session = null;
            $user->save();

            $keyboard = [
                [
                    ['text' => '⬅️ Kembali ke Detail', 'callback_data' => "menu_admin:history_detail:{$historyId}:all:1"],
                    ['text' => '📋 Daftar History', 'callback_data' => 'menu_admin:history'],
                ],
            ];

            return sendMessage([
                'chat_id' => $chatID,
                'text'    => "✅ <b>Catatan Transaksi Berhasil Diperbarui!</b>\n\nCatatan untuk invoice <code>{$history->invoice_id}</code> sekarang adalah:\n<code>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</code>",
                'mode'     => 'HTML',
                'keyboard' => $keyboard,
            ]);
        }

        // ----------------------------------------------------
        // Create Game Wizard Sessions
        // ----------------------------------------------------
        if (str_starts_with($user->session, 'admin_create_game:')) {
            $parts = explode(':', $user->session);
            $step  = $parts[1] ?? '';

            if ($step === 'name') {
                if (empty(trim($message))) {
                    return sendMessage([
                        'chat_id' => $chatID,
                        'text'    => "❌ <b>Validasi Gagal:</b>\nNama tidak boleh kosong.\n\nSilakan kirimkan kembali nama yang valid, atau ketik /cancel untuk batal.",
                        'mode'    => 'HTML',
                    ]);
                }

                $user->session = "admin_create_game:code:" . urlencode($message);
                $user->save();

                $keyboard = [[['text' => '❌ Batal', 'callback_data' => 'menu_admin:game']]];

                return sendMessage([
                    'chat_id'  => $chatID,
                    'text'     => "Nama Game: <b>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</b>\n\n✍️ Silakan kirimkan <b>Kode Game Baru</b> (contoh: <code>MLBB</code> atau <code>FF</code>):",
                    'mode'     => 'HTML',
                    'keyboard' => $keyboard,
                ]);
            } elseif ($step === 'code') {
                $name = urldecode($parts[2] ?? '');

                if (empty(trim($message))) {
                    return sendMessage([
                        'chat_id' => $chatID,
                        'text'    => "❌ <b>Validasi Gagal:</b>\nKode game tidak boleh kosong.\n\nSilakan kirimkan kembali kode yang valid, atau ketik /cancel untuk batal.",
                        'mode'    => 'HTML',
                    ]);
                }

                $game = Game::create([
                    'name'      => $name,
                    'code'      => strtoupper($message),
                    'status'    => 'active',
                    'providers' => [],
                ]);

                $user->session = null;
                $user->save();

                $keyboard = [
                    [
                        ['text' => '⬅️ Kembali ke Daftar', 'callback_data' => 'menu_admin:game'],
                        ['text' => '🔍 Detail Game', 'callback_data' => "menu_admin:game_detail:{$game->id}"],
                    ],
                ];

                return sendMessage([
                    'chat_id' => $chatID,
                    'text'    => "✅ <b>Game Baru Berhasil Dibuat!</b>\n\n• <b>Nama Game:</b> {$game->name}\n• <b>Kode Game:</b> {$game->code}\n• <b>Status:</b> 🟢 Aktif",
                    'mode'     => 'HTML',
                    'keyboard' => $keyboard,
                ]);
            }
        }

        // ----------------------------------------------------
        // Edit Game Sessions
        // ----------------------------------------------------
        if (str_starts_with($user->session, 'admin_edit_game:')) {
            $parts  = explode(':', $user->session);
            $field  = $parts[1] ?? '';
            $gameId = $parts[2] ?? null;

            $game = Game::find($gameId);
            if (! $game) {
                $user->session = null;
                $user->save();
                return sendMessage([
                    'chat_id' => $chatID,
                    'text'    => "❌ Game tidak ditemukan.",
                ]);
            }

            if (empty(trim($message))) {
                return sendMessage([
                    'chat_id' => $chatID,
                    'text'    => "❌ <b>Validasi Gagal:</b>\nNilai tidak boleh kosong.\n\nSilakan kirimkan kembali nilai yang valid, atau ketik /cancel untuk batal.",
                    'mode'    => 'HTML',
                ]);
            }

            $game->{$field} = ($field === 'code') ? strtoupper($message) : $message;
            $game->save();

            $user->session = null;
            $user->save();

            $keyboard = [
                [
                    ['text' => '⬅️ Kembali ke Detail', 'callback_data' => "menu_admin:game_detail:{$gameId}"],
                    ['text' => '🎮 Daftar Game', 'callback_data' => 'menu_admin:game'],
                ],
            ];

            return sendMessage([
                'chat_id'  => $chatID,
                'text'     => "✅ <b>Game Berhasil Diperbarui!</b>\n\nField <b>" . strtoupper($field) . "</b> telah diubah menjadi:\n<code>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</code>",
                'mode'     => 'HTML',
                'keyboard' => $keyboard,
            ]);
        }
        if (str_starts_with($user->session, 'admin_create_provider:')) {
            $parts = explode(':', $user->session);
            $step  = $parts[1] ?? '';

            if ($step === 'name') {
                if (empty(trim($message))) {
                    return sendMessage([
                        'chat_id' => $chatID,
                        'text'    => "❌ <b>Validasi Gagal:</b>\nNama tidak boleh kosong.\n\nSilakan kirimkan kembali nama yang valid, atau ketik /cancel untuk batal.",
                        'mode'    => 'HTML',
                    ]);
                }

                $user->session = "admin_create_provider:api_key:" . urlencode($message);
                $user->save();

                $keyboard = [[['text' => '❌ Batal', 'callback_data' => 'menu_admin:provider']]];

                return sendMessage([
                    'chat_id'  => $chatID,
                    'text'     => "Nama Provider: <b>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</b>\n\n✍️ Silakan kirimkan <b>API Key Provider</b>:",
                    'mode'     => 'HTML',
                    'keyboard' => $keyboard,
                ]);
            } elseif ($step === 'api_key') {
                $name = urldecode($parts[2] ?? '');

                if (empty(trim($message))) {
                    return sendMessage([
                        'chat_id' => $chatID,
                        'text'    => "❌ <b>Validasi Gagal:</b>\nAPI Key tidak boleh kosong.\n\nSilakan kirimkan kembali API Key yang valid, atau ketik /cancel untuk batal.",
                        'mode'    => 'HTML',
                    ]);
                }

                $apiKey = $message;

                $user->session = "admin_create_provider:type_api:" . urlencode($name) . ":" . urlencode($apiKey);
                $user->save();

                $keyboard = [
                    [
                        ['text' => '0 (Stok)', 'callback_data' => "menu_admin:provider_create_type:0"],
                        ['text' => '1', 'callback_data' => "menu_admin:provider_create_type:1"],
                        ['text' => '2', 'callback_data' => "menu_admin:provider_create_type:2"],
                        ['text' => '3', 'callback_data' => "menu_admin:provider_create_type:3"],
                    ],
                    [['text' => '❌ Batal', 'callback_data' => 'menu_admin:provider']],
                ];

                return sendMessage([
                    'chat_id'  => $chatID,
                    'text'     => "Nama Provider: <b>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</b>\nAPI Key: <code>" . htmlspecialchars($apiKey, ENT_QUOTES, 'UTF-8') . "</code>\n\n✍️ Silakan pilih <b>Type API</b> (0, 1, 2, atau 3) melalui tombol di bawah, atau ketik nilainya langsung:",
                    'mode'     => 'HTML',
                    'keyboard' => $keyboard,
                ]);
            } elseif ($step === 'type_api') {
                $name   = urldecode($parts[2] ?? '');
                $apiKey = urldecode($parts[3] ?? '');

                if ($message !== '0' && $message !== '1' && $message !== '2' && $message !== '3') {
                    return sendMessage([
                        'chat_id' => $chatID,
                        'text'    => "❌ <b>Validasi Gagal:</b>\nType API harus berupa angka 0, 1, 2, atau 3.\n\nSilakan kirim kembali nilai yang valid, atau ketik /cancel untuk batal.",
                        'mode'    => 'HTML',
                    ]);
                }

                $type = intval($message);

                $user->session = "admin_create_provider:reset_license:" . urlencode($name) . ":" . urlencode($apiKey) . ":{$type}";
                $user->save();

                $keyboard = [
                    [
                        ['text' => '🟢 Enabled', 'callback_data' => "menu_admin:provider_create_reset:enabled"],
                        ['text' => '🔴 Disabled', 'callback_data' => "menu_admin:provider_create_reset:disabled"],
                    ],
                    [['text' => '❌ Batal', 'callback_data' => 'menu_admin:provider']],
                ];

                return sendMessage([
                    'chat_id' => $chatID,
                    'text'    => "Nama: <b>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</b>\nType API: <b>{$type}</b>\n\n✍️ Silakan pilih status <b>Reset License</b>:",
                    'mode'     => 'HTML',
                    'keyboard' => $keyboard,
                ]);
            }
        }

        // ----------------------------------------------------
        // Edit Provider Sessions
        // ----------------------------------------------------
        if (str_starts_with($user->session, 'admin_edit_provider:')) {
            $parts      = explode(':', $user->session);
            $field      = $parts[1] ?? '';
            $providerId = $parts[2] ?? null;

            $provider = Provider::find($providerId);
            if (! $provider) {
                $user->session = null;
                $user->save();
                return sendMessage([
                    'chat_id' => $chatID,
                    'text'    => "❌ Provider tidak ditemukan.",
                ]);
            }

            $error = null;
            $val   = $message;

            if ($field === 'name' || $field === 'api_key') {
                if (empty(trim($message))) {
                    $error = 'Nilai tidak boleh kosong.';
                }
            } elseif ($field === 'type_api') {
                if ($message !== '0' && $message !== '1' && $message !== '2' && $message !== '3') {
                    $error = 'Type API harus berupa angka 0, 1, 2, atau 3.';
                } else {
                    $val = intval($message);
                }
            } elseif ($field === 'reset_license') {
                $cleanMsg = strtolower(trim($message));
                if ($cleanMsg !== 'enabled' && $cleanMsg !== 'disabled') {
                    $error = 'Reset license harus berupa "enabled" atau "disabled".';
                } else {
                    $val = $cleanMsg;
                }
            } elseif (str_starts_with($field, 'url_')) {
                if (empty(trim($message))) {
                    $error = 'URL endpoint tidak boleh kosong.';
                } elseif (! filter_var($message, FILTER_VALIDATE_URL)) {
                    $error = 'Format URL tidak valid. Harus dimulai dengan http:// atau https://';
                }
            }

            if ($error) {
                return sendMessage([
                    'chat_id' => $chatID,
                    'text'    => "❌ <b>Validasi Gagal:</b>\n{$error}\n\nSilakan kirim kembali nilai yang valid, atau ketik <code>/cancel</code> untuk batal.",
                    'mode' => 'HTML',
                ]);
            }

            // Save details
            if (str_starts_with($field, 'url_')) {
                $urls          = $provider->url ?? [];
                $urlKey        = str_replace('url_', '', $field);
                $urls[$urlKey] = $val;
                $provider->url = $urls;
            } else {
                $provider->{$field} = $val;
            }
            $provider->save();

            $user->session = null;
            $user->save();

            $backCallback = str_starts_with($field, 'url_')
                ? "menu_admin:provider_endpoints:{$providerId}"
                : "menu_admin:provider_detail:{$providerId}";

            $keyboard = [
                [
                    ['text' => '⬅️ Kembali', 'callback_data' => $backCallback],
                    ['text' => '🏢 Daftar Provider', 'callback_data' => 'menu_admin:provider'],
                ],
            ];

            return sendMessage([
                'chat_id'  => $chatID,
                'text'     => "✅ <b>Provider Berhasil Diperbarui!</b>\n\nField <b>" . strtoupper($field) . "</b> telah diubah menjadi:\n<code>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</code>",
                'mode'     => 'HTML',
                'keyboard' => $keyboard,
            ]);
        }

        // ----------------------------------------------------
        // Create Denom Wizard Sessions
        // ----------------------------------------------------
        if (str_starts_with($user->session, 'admin_create_denom:')) {
            $parts = explode(':', $user->session);
            $step  = $parts[1] ?? '';

            if ($step === 'name') {
                $gameId     = $parts[2] ?? '';
                $providerId = $parts[3] ?? '';

                if (empty(trim($message))) {
                    return sendMessage([
                        'chat_id' => $chatID,
                        'text'    => "❌ <b>Validasi Gagal:</b>\nNama tidak boleh kosong.\n\nSilakan kirimkan kembali nama yang valid, atau klik Batal di atas.",
                        'mode'    => 'HTML',
                    ]);
                }

                // Advance to price step
                $user->session = "admin_create_denom:price:{$gameId}:{$providerId}:" . urlencode($message);
                $user->save();

                $keyboard = [
                    [['text' => '❌ Batal', 'callback_data' => "menu_admin:denom_list:{$gameId}:{$providerId}"]],
                ];

                return sendMessage([
                    'chat_id'  => $chatID,
                    'text'     => "Nama Denom: <b>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</b>\n\n✍️ Silakan kirimkan <b>Harga Denom</b> (hanya angka, contoh: <code>500</code> atau <code>15000</code>):",
                    'mode'     => 'HTML',
                    'keyboard' => $keyboard,
                ]);
            } elseif ($step === 'price') {
                $gameId     = $parts[2] ?? '';
                $providerId = $parts[3] ?? '';
                $name       = urldecode($parts[4] ?? '');

                if (! is_numeric($message) || intval($message) < 0) {
                    return sendMessage([
                        'chat_id' => $chatID,
                        'text'    => "❌ <b>Validasi Gagal:</b>\nHarga harus berupa angka minimal 0.\n\nSilakan kirimkan kembali harga yang valid, atau klik Batal di atas.",
                        'mode'    => 'HTML',
                    ]);
                }

                $price = intval($message);

                // Advance to duration step
                $user->session = "admin_create_denom:duration:{$gameId}:{$providerId}:" . urlencode($name) . ":{$price}";
                $user->save();

                $keyboard = [
                    [['text' => '❌ Batal', 'callback_data' => "menu_admin:denom_list:{$gameId}:{$providerId}"]],
                ];

                return sendMessage([
                    'chat_id'  => $chatID,
                    'text'     => "Nama Denom: <b>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</b>\nHarga: <b>Rp " . number_format($price, 0, ',', '.') . "</b>\n\n✍️ Silakan kirimkan <b>Durasi (dalam hari)</b> (hanya angka, contoh: <code>1</code> atau <code>30</code>):",
                    'mode'     => 'HTML',
                    'keyboard' => $keyboard,
                ]);
            } elseif ($step === 'duration') {
                $gameId     = $parts[2] ?? '';
                $providerId = $parts[3] ?? '';
                $name       = urldecode($parts[4] ?? '');
                $price      = intval($parts[5] ?? 0);

                if (! is_numeric($message)) {
                    return sendMessage([
                        'chat_id' => $chatID,
                        'text'    => "❌ <b>Validasi Gagal:</b>\nDurasi harus berupa angka minimal 1.\n\nSilakan kirimkan kembali durasi yang valid, atau klik Batal di atas.",
                        'mode'    => 'HTML',
                    ]);
                }

                $duration = intval($message);

                // Create Denom
                $denom = Denom::create([
                    'game_id'     => $gameId,
                    'provider_id' => $providerId,
                    'name'        => $name,
                    'price'       => $price,
                    'duration'    => $duration,
                    'status'      => 'active',
                ]);

                // Reset session
                $user->session = null;
                $user->save();

                $keyboard = [
                    [
                        ['text' => '⬅️ Kembali ke Daftar', 'callback_data' => "menu_admin:denom_list:{$gameId}:{$providerId}"],
                        ['text' => '🔍 Detail Denom Baru', 'callback_data' => "menu_admin:denom_detail:{$denom->id}"],
                    ],
                ];

                return sendMessage([
                    'chat_id' => $chatID,
                    'text'    => "✅ <b>Denom Baru Berhasil Dibuat!</b>\n\n• <b>Nama:</b> {$denom->name}\n• <b>Harga:</b> Rp " . number_format($denom->price, 0, ',', '.') . "\n• <b>Durasi:</b> {$denom->duration} Hari\n• <b>Status:</b> 🟢 Aktif",
                    'mode' => 'HTML',
                    'keyboard' => $keyboard,
                ]);
            }
        }

        // ----------------------------------------------------
        // Edit Denom Sessions
        // ----------------------------------------------------
        if (str_starts_with($user->session, 'admin_edit_denom:')) {
            $parts   = explode(':', $user->session);
            $field   = $parts[1] ?? '';
            $denomId = $parts[2] ?? null;

            $denom = Denom::find($denomId);
            if (! $denom) {
                $user->session = null;
                $user->save();
                return sendMessage([
                    'chat_id' => $chatID,
                    'text'    => "❌ Denom tidak ditemukan di database.",
                ]);
            }

            $error = null;
            $val   = $message;

            if ($field === 'name') {
                if (empty(trim($message))) {
                    $error = 'Nama tidak boleh kosong.';
                }
            } elseif ($field === 'price') {
                if (! is_numeric($message) || intval($message) < 0) {
                    $error = 'Harga harus berupa angka minimal 0.';
                } else {
                    $val = intval($message);
                }
            } elseif ($field === 'duration') {
                if (! is_numeric($message) || intval($message) < 1) {
                    $error = 'Durasi harus berupa angka minimal 1.';
                } else {
                    $val = intval($message);
                }
            }

            if ($error) {
                return sendMessage([
                    'chat_id' => $chatID,
                    'text'    => "❌ <b>Validasi Gagal:</b>\n{$error}\n\nSilakan kirim kembali nilai yang valid, atau ketik <code>/cancel</code> untuk batal.",
                    'mode' => 'HTML',
                ]);
            }

            // Save details
            $denom->{$field} = $val;
            $denom->save();

            $user->session = null;
            $user->save();

            $keyboard = [
                [
                    ['text' => '⬅️ Kembali ke Detail', 'callback_data' => "menu_admin:denom_detail:{$denomId}"],
                    ['text' => '📦 Daftar Denom', 'callback_data' => "menu_admin:denom_list:{$denom->game_id}:{$denom->provider_id}"],
                ],
            ];

            return sendMessage([
                'chat_id'  => $chatID,
                'text'     => "✅ <b>Denom Berhasil Diperbarui!</b>\n\nField <b>" . strtoupper($field) . "</b> telah diubah menjadi:\n<code>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</code>",
                'mode'     => 'HTML',
                'keyboard' => $keyboard,
            ]);
        }

        // ----------------------------------------------------
        // Config Edit & Caption Edit Sessions
        // ----------------------------------------------------
        if (! $config) {
            $config = Config::first();
        }

        if (str_starts_with($user->session, 'admin_edit:config:')) {
            $parts    = explode(':', $user->session);
            $category = $parts[2] ?? '';
            $field    = $parts[3] ?? '';
            $subfield = $parts[4] ?? '';

            $currentData = $config->{$category} ?? [];
            $error       = null;
            $val         = $message;

            if ($category === 'order') {
                if ($field === 'custom_notes') {
                    $customNotes = $currentData['custom_notes'] ?? [];
                    if (empty(trim($message))) {
                        $error = 'Catatan tidak boleh kosong.';
                    } else {
                        $customNotes[$subfield] = $message;
                        $val                    = $customNotes;
                    }
                } elseif ($field === 'exp_order') {
                    if (! is_numeric($message) || intval($message) < 1) {
                        $error = 'Masa berlaku harus berupa angka minimal 1.';
                    } else {
                        $val = intval($message);
                    }
                } elseif ($field === 'count_pending') {
                    if (! is_numeric($message) || intval($message) < 1) {
                        $error = 'Batas transaksi pending harus berupa angka minimal 1.';
                    } else {
                        $val = intval($message);
                    }
                } elseif ($field === 'transaksi_delay') {
                    if (! is_numeric($message) || intval($message) < 0) {
                        $error = 'Jeda transaksi harus berupa angka minimal 0.';
                    } else {
                        $val = intval($message);
                    }
                } elseif ($field === 'length_random_order') {
                    if (! is_numeric($message) || intval($message) < 4 || intval($message) > 30) {
                        $error = 'Panjang ID invoice harus berupa angka antara 4 s/d 30.';
                    } else {
                        $val = intval($message);
                    }
                } elseif ($field === 'string') {
                    if (empty(trim($message))) {
                        $error = 'Karakter acak tidak boleh kosong.';
                    }
                }
            } elseif ($category === 'bot') {
                if ($field === 'image') {
                    $fileId = null;
                    $photo  = $dataMessage['photo'] ?? null;
                    if (! empty($photo)) {
                        $photoItem = end($photo);
                        $fileId    = $photoItem['file_id'] ?? null;
                    }
                    $document = $dataMessage['document'] ?? null;
                    if (! $fileId && $document && str_starts_with($document['mime_type'] ?? '', 'image/')) {
                        $fileId = $document['file_id'] ?? null;
                    }

                    if ($fileId) {
                        try {
                            $file     = \Telegram\Bot\Laravel\Facades\Telegram::getFile(['file_id' => $fileId]);
                            $filePath = null;
                            if (is_object($file)) {
                                if (method_exists($file, 'getFilePath')) {
                                    $filePath = $file->getFilePath();
                                }
                                if (! $filePath) {
                                    $filePath = $file->file_path ?? $file->get('file_path') ?? null;
                                }
                            } elseif (is_array($file)) {
                                $filePath = $file['file_path'] ?? null;
                            }

                            if ($filePath) {
                                $token    = config('telegram.bots.mybot.token') ?: env('TELEGRAM_BOT_TOKEN');
                                $fileUrl  = "https://api.telegram.org/file/bot{$token}/{$filePath}";
                                $response = \Illuminate\Support\Facades\Http::get($fileUrl);
                                if ($response->successful()) {
                                    $fileContents = $response->body();
                                    $extension    = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'jpg';
                                    $fileName     = 'bot_image_' . time() . '.' . $extension;

                                    \Illuminate\Support\Facades\Storage::disk('public')->put('bot/' . $fileName, $fileContents);

                                    $val     = asset('storage/bot/' . $fileName);
                                    $message = $val;
                                } else {
                                    $error = 'Gagal mengunduh gambar dari Telegram.';
                                }
                            } else {
                                $error = 'Gagal mendapatkan path file gambar dari Telegram.';
                            }
                        } catch (\Throwable $e) {
                            $error = 'Terjadi kesalahan saat mengunggah gambar: ' . $e->getMessage();
                        }
                    } else {
                        if (! filter_var($message, FILTER_VALIDATE_URL)) {
                            $error = 'Format URL gambar tidak valid. Harus dimulai dengan http:// atau https/ atau silakan kirim langsung file gambarnya ke chat ini.';
                        }
                    }
                } elseif ($field === 'contact') {
                    if (empty(trim($message))) {
                        $error = 'Kontak admin tidak boleh kosong.';
                    }
                }
            } elseif ($category === 'payments') {
                $gatewaySettings = $currentData[$field] ?? [];
                if (empty(trim($message))) {
                    $error = 'Nilai tidak boleh kosong.';
                } else {
                    $gatewaySettings[$subfield] = $message;
                    $currentData[$field]        = $gatewaySettings;
                }
            }

            if ($error) {
                return sendMessage([
                    'chat_id' => $chatID,
                    'text'    => "❌ <b>Validasi Gagal:</b>\n{$error}\n\nSilakan kirim kembali nilai yang valid, atau ketik <code>/cancel</code> untuk batal.",
                    'mode' => 'HTML',
                ]);
            }

            // Save the value
            if ($category !== 'payments') {
                $currentData[$field] = $val;
            }

            $config->{$category} = $currentData;
            $config->save();

            $user->session = null;
            $user->save();

            $backCallback = "menu_admin:config:{$category}";
            if ($field === 'custom_notes') {
                $backCallback = "menu_admin:config:{$category}:custom_notes";
            } elseif ($category === 'payments') {
                $backCallback = "menu_admin:config:payments:detail:{$field}";
            }

            $keyboard = [
                [
                    ['text' => '⬅️ Kembali ke Detail Gateway', 'callback_data' => $backCallback],
                    ['text' => '⚙️ Menu Config', 'callback_data' => 'menu_admin:config'],
                ],
            ];

            return sendMessage([
                'chat_id'  => $chatID,
                'text'     => "✅ <b>Berhasil Diperbarui!</b>\n\nPengaturan untuk <b>" . strtoupper($field) . ($subfield ? ' (' . strtoupper($subfield) . ')' : '') . "</b> telah diperbarui menjadi:\n<code>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</code>",
                'mode'     => 'HTML',
                'keyboard' => $keyboard,
            ]);
        } elseif (str_starts_with($user->session, 'admin_edit:caption:')) {
            $parts = explode(':', $user->session);
            $type  = $parts[2] ?? '';
            $key   = $parts[3] ?? '';

            $captions = $config->captions ?? [];
            $list     = $captions[$type] ?? [];

            $found = false;
            foreach ($list as $index => $item) {
                if (($item['key'] ?? '') === $key) {
                    $list[$index]['content'] = $message;
                    $found                   = true;
                    break;
                }
            }

            if (! $found) {
                $list[] = [
                    'key'     => $key,
                    'content' => $message,
                ];
            }

            $captions[$type]  = $list;
            $config->captions = $captions;
            $config->save();

            $user->session = null;
            $user->save();

            $keyboard = [
                [
                    ['text' => '🔍 Lihat Detail Caption', 'callback_data' => "menu_admin:view_caption:{$type}:{$key}"],
                    ['text' => '⬅️ Kembali ke List', 'callback_data' => "menu_admin:config:captions:{$type}"],
                ],
            ];

            return sendMessage([
                'chat_id' => $chatID,
                'text'    => "✅ <b>Caption Berhasil Diperbarui!</b>\n\nIsi caption untuk key <code>{$key}</code> telah disimpan.",
                'mode'     => 'HTML',
                'keyboard' => $keyboard,
            ]);
        }
    }
}
