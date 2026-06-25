<?php

namespace App\Http\Controllers;

use App\Models\Config;
use App\Models\History;
use App\Models\User;
use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramController extends Controller
{
    public $backToStart = false;

    public function index(Request $request)
    {
        // file_put_contents(
        //     'webhook.txt',
        //     json_encode($request->all(), JSON_PRETTY_PRINT) . PHP_EOL,
        //     FILE_APPEND
        // );
        $config        = Config::first();
        $data          = $request->all();
        $dataMessage   = $data['message'] ?? [];
        $message       = $dataMessage['text'] ?? null;
        $typeChat      = $dataMessage['chat']['type'] ?? null;
        $chatID        = isset($dataMessage['chat']['id']) ? (string) $dataMessage['chat']['id'] : null;
        $callbackQuery = $data['callback_query'] ?? [];

        if ($callbackQuery) {
            $callbackFromId = isset($callbackQuery['from']['id']) ? (string) $callbackQuery['from']['id'] : null;
            $user = User::where('user_id', $callbackFromId)->first();

            if ($user) {
                $this->syncUser($user, $callbackQuery['from']);
                return (new HandleCallback())->handle($callbackQuery, $user, $config);
            }

            return response('ok', 200);
        }

        if ($typeChat !== 'private' || ! $chatID) {
            return response('ok', 200);
        }

        $fromId = isset($dataMessage['from']['id']) ? (string) $dataMessage['from']['id'] : null;

        $user = User::where('user_id', $fromId)->orWhere('user_id', $dataMessage['from']['id'])->first();

        if ($user) {
            $this->syncUser($user, $dataMessage['from']);
            $user->refresh();
        }

        $hasSession = $user && ! empty($user->session);

        if (! $message && ! $hasSession) {
            return response('ok', 200);
        }

        if ($message && (str_starts_with($message, '/start') || $this->backToStart)) {
            if ($user && ! empty($user->session)) {
                $user->session = null;
                $user->save();
            }

            if (! $user) {
                $user = User::create([
                    'user_id'    => $fromId,
                    'first_name' => $dataMessage['from']['first_name'] ?? null,
                    'last_name'  => $dataMessage['from']['last_name'] ?? null,
                    'username'   => $dataMessage['from']['username'] ?? null,
                ]);
            }

            $this->sessionStart($chatID, $dataMessage, $config);
            return response('ok', 200);
        }

        if ($message && str_starts_with($message, '/cek_invoice')) {
            $this->handleCekInvoice($message, (int) $chatID, $dataMessage, $config, $user);
            return response('ok', 200);
        }

        if ($user && ! empty($user->session)) {
            (new HandleCallback())->handleUserSession($message ?? '', $chatID, $dataMessage, $user, $config);
            return response('ok', 200);
        }

        return response('ok', 200);
    }

    private function syncUser($user = null, array $from): void
    {
        $newId = isset($from['id']) ? (string) $from['id'] : null;

        $changed =
            $user->user_id !== $newId ||
            $user->first_name !== ($from['first_name'] ?? null) ||
            $user->last_name !== ($from['last_name'] ?? null) ||
            $user->username !== ($from['username'] ?? null);

        if ($changed) {
            $user->user_id = $newId;
            $user->first_name = $from['first_name'] ?? null;
            $user->last_name = $from['last_name'] ?? null;
            $user->username = $from['username'] ?? null;
            $user->save();
        }
    }

    public function sessionStart($chatID, array $dataMessage, $config = null, $animation = true): void
    {
        $chatIdString = (string) $chatID;
        $user = User::where('user_id', $chatIdString)->first();

        if ($user && ! empty($user->session)) {
            $user->session = null;
            $user->save();
        }

        $hour = (int) date('H');

        $greeting = match (true) {
            $hour >= 4 && $hour < 11  => 'Selamat Pagi',
            $hour >= 11 && $hour < 15 => 'Selamat Siang',
            $hour >= 15 && $hour < 19 => 'Selamat Sore',
            default                   => 'Selamat Malam',
        };

        $firstName = htmlspecialchars($dataMessage['chat']['first_name'] ?? 'User', ENT_QUOTES, 'UTF-8');

        $captionRow   = collect($config->captions['orders'] ?? [])->firstWhere('key', 'menu_start');
        $template     = is_array($captionRow) ? ($captionRow['content'] ?? null) : null;
        $finalCaption = str_replace(
            ['{greeting}', '{firstname}'],
            [$greeting, $firstName],
            $template ?? "<b>{$greeting} {$firstName}</b>\n\nSelamat datang di bot 🚀"
        );

        $keyboard = [
            [['text' => '🛒 Mulai Pesan', 'callback_data' => 'menu_games']],
            [
                ['text' => '👤 Akun Saya', 'callback_data' => 'menu_account'],
                ['text' => '♻️ Reset Lisensi', 'callback_data' => 'menu_resetlicense'],
            ],
            [
                ['text' => '🏆 Leaderboard', 'callback_data' => 'leaderboard'],
                ['text' => '📢 Pengumuman', 'callback_data' => 'pengumuman'],
            ],
            [['text' => '📜 Riwayat Transaksi', 'callback_data' => 'menu_history']],
        ];

        if ($user && $user->role === 'admin') {
            $keyboard[] = [['text' => '👨‍💼 Menu Admin', 'callback_data' => 'menu_admin']];
        }

        try {
            $messageID = null;

            if ($animation) {
                $sendMessage = Telegram::sendPhoto([
                    'chat_id' => $chatIdString,
                    'photo' => $config->bot['image'],
                    'caption' => 'Menyiapkan menu...',
                    'parse_mode' => 'HTML',
                ]);

                $messageID = $sendMessage->getMessageId();

                foreach (['[■□□□□]', '[■■□□□]', '[■■■■□]', '[■■■■■]'] as $frame) {
                    Telegram::editMessageCaption([
                        'chat_id' => $chatIdString,
                        'message_id' => $messageID,
                        'caption' => "<b>{$greeting} {$firstName}</b>\n\n<b>Menyiapkan menu</b> {$frame}",
                        'parse_mode' => 'HTML',
                    ]);

                    usleep(800000);
                }
            }

            if ($messageID) {
                Telegram::editMessageCaption([
                    'chat_id' => $chatIdString,
                    'message_id' => $messageID,
                    'caption' => $finalCaption,
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => $keyboard,
                    ]),
                ]);
            } else {
                Telegram::sendPhoto([
                    'chat_id' => $chatIdString,
                    'photo' => $config->bot['image'],
                    'caption' => $finalCaption,
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => $keyboard,
                    ]),
                ]);
            }
        } catch (\Throwable $e) {
            logger()->error('sessionStart failed', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }
    }

    private function handleCekInvoice(string $message, int $chatID, array $dataMessage, $config = null, $user = null): void
    {
        $parts     = explode(' ', trim($message), 2);
        $invoiceId = trim($parts[1] ?? '');

        $backKeyboard = [[['text' => '🏠 Menu Utama', 'callback_data' => 'menu_start']]];

        if (empty($invoiceId)) {
            sendMessage([
                'chat_id'  => $chatID,
                'text'     => "⚠️ <b>Format Salah</b>\n\nSilakan gunakan format berikut:\n<code>/cek_invoice INVOICE_ID</code>\n\nContoh: <code>/cek_invoice KM-FZEER75RXH7Y</code>",
                'mode'     => 'HTML',
                'keyboard' => $backKeyboard,
            ]);
            return;
        }
        if ($user->role == 'user') {
            $history = History::where([['user_id', $user->id], ['invoice_id', $invoiceId]])->first();
        } else {
            $history = History::where('invoice_id', $invoiceId)->first();
        }

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
}
