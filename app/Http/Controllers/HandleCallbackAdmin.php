<?php
namespace App\Http\Controllers;

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

            return match (true) {
                $callbackData === 'menu_admin'        => $this->handleButtonMenu($callbackData, $data, $user, $config),
                $callbackData === 'menu_admin:config' => $this->handleConfigLink($callbackData, $data, $user, $config),
                default                               => null
            };
        } catch (Exception $e) {
            return json_encode([
                'status'  => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function handleButtonMenu(string $callbackData, array $data, $user = null, $config = null)
    {
        $keyboard = [
            [
                ['text' => '⚙️ Konfigurasi Bot', 'callback_data' => 'menu_admin:config'],
            ],
            [
                ['text' => '🏠 Menu Utama', 'callback_data' => 'menu_start'],
            ],
        ];

        editCaption([
            'chat_id'    => $data['message']['chat']['id'] ?? null,
            'message_id' => $data['message']['message_id'] ?? null,
            'caption'    => 'Silahkan pilih salah satu menu admin di bawah',
            'parse_mode' => 'HTML',
            'keyboard'   => $keyboard,
        ]);
    }

    private function handleConfigLink(string $callbackData, array $data, $user = null, $config = null)
    {
        if (! $user || $user->role !== 'admin') {
            return;
        }

        // Generate a secure one-time-use token for admin web interface
        $token = \Illuminate\Support\Str::random(40);
        \Illuminate\Support\Facades\Cache::put('admin_token_' . $user->user_id, $token, now()->addMinutes(15));

        // Build base URL dynamically from request to support ngrok/domain mapping seamlessly
        $baseUrl = request()->getSchemeAndHttpHost();
        $url = $baseUrl . '/admin/config/login?user_id=' . $user->user_id . '&token=' . $token;

        $keyboard = [
            [
                ['text' => '🌐 Buka Konfigurasi Web', 'url' => $url],
            ],
            [
                ['text' => '⬅️ Kembali ke Menu Admin', 'callback_data' => 'menu_admin'],
            ],
        ];

        editCaption([
            'chat_id'    => $data['message']['chat']['id'] ?? null,
            'message_id' => $data['message']['message_id'] ?? null,
            'caption'    => "⚙️ <b>Konfigurasi Bot (Web)</b>\n\nUntuk mengedit konfigurasi bot dengan mudah dan ramah pengguna, silakan klik tombol di bawah ini.\n\n⚠️ <i>Link ini bersifat rahasia dan hanya berlaku selama 15 menit.</i>",
            'parse_mode' => 'HTML',
            'keyboard'   => $keyboard,
        ]);
    }
}

