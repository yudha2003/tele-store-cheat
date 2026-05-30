<?php
namespace App\Http\Controllers;

use App\Models\Config;
use App\Models\User;
use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramController extends Controller
{
    public $backToStart = false;

    public function index(Request $request)
    {
        $config        = Config::first();
        $data          = $request->all();
        $dataMessage   = $data['message'] ?? [];
        $message       = $dataMessage['text'] ?? null;
        $typeChat      = $dataMessage['chat']['type'] ?? null;
        $chatID        = $dataMessage['chat']['id'] ?? null;
        $callbackQuery = $data['callback_query'] ?? [];

        if ($callbackQuery) {
            $user = User::where('user_id', $callbackQuery['from']['id'] ?? 0)->first();
            if ($user) {
                $this->syncUser($user, $callbackQuery['from']);
                return (new HandleCallback())->handle($callbackQuery, $user, $config);
            }
            return response('ok', 200);
        }

        if ($typeChat !== 'private' || ! $chatID || ! $message) {
            return response('ok', 200);
        }

        $user = User::where('user_id', $dataMessage['from']['id'] ?? 0)->first();
        if ($user) {
            $this->syncUser($user, $dataMessage['from']);
        }

        if (str_starts_with($message, '/start') || $this->backToStart) {
            if (! $user) {
                User::create([
                    'user_id'    => $dataMessage['from']['id'],
                    'first_name' => $dataMessage['from']['first_name'],
                    'last_name'  => $dataMessage['from']['last_name'] ?? null,
                    'username'   => $dataMessage['from']['username'] ?? null,
                ]);
            }
            $this->sessionStart((int) $chatID, $dataMessage, $config);
            return response('ok', 200);
        }

        if (str_starts_with($message, '/cek-invoice')) {
            $this->handleCekInvoice($message, $chatID, $dataMessage, $config);
            return response('ok', 200);
        }

        return response('ok', 200);
    }

    private function syncUser($user = null, array $from): void
    {
        $changed =
        $user->first_name !== ($from['first_name'] ?? null) ||
        $user->last_name !== ($from['last_name'] ?? null) ||
        $user->username !== ($from['username'] ?? null);

        if ($changed) {
            $user->first_name = $from['first_name'] ?? null;
            $user->last_name  = $from['last_name'] ?? null;
            $user->username   = $from['username'] ?? null;
            $user->save();
        }
    }

    private function handleCekInvoice(string $message, int $chatID, array $dataMessage, $config = null): void
    {
        $parts     = explode(' ', trim($message), 2);
        $invoiceId = trim($parts[1] ?? '');

        $backKeyboard = [[['text' => '🏠 Menu Utama', 'callback_data' => 'menu_start']]];

        if (empty($invoiceId)) {
            sendMessage([
                'chat_id'  => $chatID,
                'text'     => "⚠️ <b>Format Salah</b>\n\nSilakan gunakan format berikut:\n<code>/cek-invoice [ID_INVOICE]</code>\n\nContoh: <code>/cek-invoice KM-FZEER75RXH7Y</code>",
                'mode'     => 'HTML',
                'keyboard' => $backKeyboard,
            ]);
            return;
        }

        $history = \App\Models\History::where('invoice_id', $invoiceId)->first();

        if ($history) {
            (new HandleCallback())->showInvoice($history, $dataMessage, $config);
            return;
        }

        sendMessage([
            'chat_id'  => $chatID,
            'text'     => "❌ <b>Invoice Tidak Ditemukan</b>\n\nMaaf, transaksi dengan ID Invoice <code>" . htmlspecialchars($invoiceId, ENT_QUOTES, 'UTF-8') . "</code> tidak ditemukan.\n\nSilakan periksa kembali ID Invoice Anda.",
            'mode'     => 'HTML',
            'keyboard' => $backKeyboard,
        ]);
    }
    public function sessionStart(int $chatID, array $dataMessage, $config = null, $animation = true): void
    {
        $hour = (int) date('H');

        $greeting = match (true) {
            $hour >= 4 && $hour < 11  => 'Selamat Pagi',
            $hour >= 11 && $hour < 15 => 'Selamat Siang',
            $hour >= 15 && $hour < 19 => 'Selamat Sore',
            default                   => 'Selamat Malam',
        };

        $firstName = htmlspecialchars(
            $dataMessage['chat']['first_name'] ?? 'User',
            ENT_QUOTES,
            'UTF-8'
        );

        try {
            if ($animation) {
                $sendMessage = Telegram::sendPhoto([
                    'chat_id'    => $chatID,
                    'photo'      => config('website.image_cmd_start'),
                    'caption'    => 'Menyiapkan menu...',
                    'parse_mode' => 'HTML',
                ]);

                $messageID = $sendMessage->getMessageId();
                $frames    = [
                    '[■□□□□]',
                    '[■■□□□]',
                    '[■■■■□]',
                    '[■■■■■]',
                ];

                foreach ($frames as $frame) {
                    $caption = "<b>{$greeting} {$firstName}</b>\n\n<b>Menyiapkan menu</b> {$frame}";

                    editCaption([
                        'chat_id'    => $chatID,
                        'message_id' => $messageID,
                        'caption'    => $caption,
                        'parse_mode' => 'HTML',
                    ]);

                    usleep(800000);
                }
            }

            $captionRow   = collect($config->captions['orders'] ?? [])->firstWhere('key', 'menu_start');
            $template     = is_array($captionRow) ? ($captionRow['content'] ?? null) : null;
            $finalCaption = str_replace(
                ['{greeting}', '{firstname}'],
                [$greeting, $firstName],
                $template ?? "<b>{$greeting} {$firstName}</b>\n\nSelamat datang di bot 🚀"
            );

            $keyboard = [
                [
                    ['text' => '🛒 Mulai Pesan', 'callback_data' => 'menu_games'],
                ],
                [
                    ['text' => '👤 Akun Saya', 'callback_data' => 'menu_account'],
                    ['text' => '♻️ Reset Lisensi', 'callback_data' => 'menu_resetlicense'],
                ],
                [
                    ['text' => '🏆 Leaderboard', 'callback_data' => 'leaderboard'],
                    ['text' => '📢 Pengumuman', 'callback_data' => 'pengumuman'],
                ],
                [
                    ['text' => '📜 Riwayat Transaksi', 'callback_data' => 'menu_history'],
                ],
            ];

            if (isset($messageID)) {
                editCaption([
                    'chat_id'    => $chatID,
                    'message_id' => $messageID,
                    'caption'    => $finalCaption,
                    'parse_mode' => 'HTML',
                    'keyboard'   => $keyboard,
                ]);
            } else {
                if (isset($dataMessage['photo'])) {
                    editMessage(
                        $chatID,
                        $dataMessage['message_id'],
                        config('website.image_cmd_start'),
                        $finalCaption,
                        $keyboard
                    );
                } else {
                    if (isset($dataMessage['message_id'])) {
                        deleteMessage($chatID, $dataMessage['message_id']);
                    }
                    sendPhoto(
                        $chatID,
                        config('website.image_cmd_start'),
                        $finalCaption,
                        $keyboard
                    );
                }
            }
        } catch (\Throwable $e) {
            return;
        }
    }
}
