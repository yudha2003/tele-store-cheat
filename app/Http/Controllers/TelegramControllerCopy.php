<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramControllerCopy extends Controller
{
    public function index(Request $request)
    {
        $data          = $request->all();
        $dataMessage   = $data['message'] ?? [];
        $callbackQuery = $data['callback_query'] ?? [];

        // Handle callback query
        if ($callbackQuery) {
            $this->handleCallbackQuery($callbackQuery);
            return response('ok', 200);
        }

        $message  = $dataMessage['text'] ?? null;
        $typeChat = $dataMessage['chat']['type'] ?? null;
        $chatID   = $dataMessage['chat']['id'] ?? null;

        if ($typeChat !== 'private' || ! $chatID || ! $message) {
            return response('ok', 200);
        }

        if (str_starts_with($message, '/start')) {
            $this->sessionStart((int) $chatID, $message, $dataMessage);
        } else {
            $this->handleUnknownCommand((int) $chatID);
        }

        return response('ok', 200);
    }

    // -------------------------------------------------------------------------
    // Session Start
    // -------------------------------------------------------------------------

    private function sessionStart(int $chatID, string $message, array $dataMessage): void
    {
        $hour = (int) date('H');

        $greeting = match (true) {
            $hour >= 4 && $hour < 11  => 'Selamat Pagi',
            $hour >= 11 && $hour < 15 => 'Selamat Siang',
            $hour >= 15 && $hour < 19 => 'Selamat Sore',
            default                   => 'Selamat Malam',
        };

        $firstName = htmlspecialchars(
            $dataMessage['from']['first_name'] ?? 'User',
            ENT_QUOTES,
            'UTF-8'
        );

        try {
            // Kirim foto awal dengan caption loading
            $sendMessage = sendPhoto(
                $chatID,
                $config->bot['image'],
                "<b>{$greeting} {$firstName}</b>\n\nMempersiapkan sesi\n[□□□□□]"
            );

            if (! $sendMessage) {
                Log::error('Telegram sendPhoto gagal', ['chat_id' => $chatID]);
                return;
            }

            $messageID = $this->getMessageId($sendMessage);

            if (! $messageID) {
                Log::error('Telegram message_id tidak ditemukan', [
                    'response' => $sendMessage,
                ]);
                return;
            }

            // Jalankan animasi loading menggunakan helper
            showLoadingAnimation($chatID, $messageID, "{$greeting} {$firstName}");

            // Caption final setelah animasi selesai
            $finalCaption = "<b>{$greeting} {$firstName}</b>\n\nSelamat datang di bot 🚀";

            editCaption($chatID, $messageID, $finalCaption);

        } catch (\Throwable $e) {
            Log::error('Telegram /start error', [
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
                'file'  => $e->getFile(),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Callback Query Handler
    // -------------------------------------------------------------------------

    private function handleCallbackQuery(array $callbackQuery): void
    {
        $callbackQueryID = $callbackQuery['id'] ?? null;
        $chatID          = $callbackQuery['message']['chat']['id'] ?? null;
        $messageID       = $callbackQuery['message']['message_id'] ?? null;
        $data            = $callbackQuery['data'] ?? null;

        if (! $chatID || ! $messageID || ! $data) {
            return;
        }

        // Acknowledge callback agar loading spinner di Telegram hilang
        answerCallbackQuery($callbackQueryID);

        match ($data) {
            'main_menu' => $this->showMainMenu($chatID, $messageID),
            default     => sendFallbackMessage($chatID),
        };
    }

    // -------------------------------------------------------------------------
    // Menu Handlers
    // -------------------------------------------------------------------------

    private function showMainMenu(int $chatID, int $messageID): void
    {
        $title = 'Menu Utama';

        // Tampilkan animasi loading sebelum edit ke menu tujuan
        showLoadingAnimation($chatID, $messageID, $title);

        $caption = "<b>🏠 Menu Utama</b>\n\nSilakan pilih menu di bawah ini:";

        $keyboard = [
            [
                ['text' => '📦 Produk', 'callback_data' => 'menu_produk'],
                ['text' => '📋 Pesanan', 'callback_data' => 'menu_pesanan'],
            ],
            [
                ['text' => 'ℹ️ Bantuan', 'callback_data' => 'menu_bantuan'],
            ],
        ];

        editCaption($chatID, $messageID, $caption, $keyboard);
    }

    // -------------------------------------------------------------------------
    // Unknown Command
    // -------------------------------------------------------------------------

    private function handleUnknownCommand(int $chatID): void
    {
        sendFallbackMessage($chatID);
    }

    // -------------------------------------------------------------------------
    // Utility
    // -------------------------------------------------------------------------

    private function getMessageId(mixed $response): ?int
    {
        // Object dari irazasyed/telegram-bot-sdk
        if (is_object($response) && method_exists($response, 'getMessageId')) {
            return $response->getMessageId();
        }

        // Array biasa
        if (is_array($response)) {
            return $response['result']['message_id'] ?? $response['message_id'] ?? null;
        }

        return null;
    }
}
