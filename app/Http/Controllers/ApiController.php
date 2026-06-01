<?php
namespace App\Http\Controllers;

use App\Libraries\Apiv1;
use App\Libraries\Apiv2;
use App\Models\Config;
use App\Models\Denom;
use App\Models\Game;
use App\Models\History;
use App\Models\Provider;
use Exception;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function pakasir(Request $request)
    {
        $order_id       = $request->order_id;
        $config         = Config::first();
        $providerConfig = $config->payment['pakasir'] ?? [];
        if ($providerConfig['project'] !== $request->project) {
            return response()->json(['status' => false, 'message' => 'Data not valid!'], 403);
        }
        $history = History::where([['invoice_id', $order_id], ['payment_status', 'pending'], ['process_status', 'pending']])->first();
        if (! $history) {
            return response()->json(['status' => false, 'message' => 'Data transaksi tidak ditemukan'], 404);
        }

        $provider = Provider::find($history->product['provider']['id']);
        $denom    = Denom::where('id', $history->product['denom']['id'])->first();
        $game     = Game::where('id', $history->product['game']['id'])->first();
        if (! $denom || $game || ! $provider) {
            $history->update([
                'payment_status' => 'failed',
                'process_status' => 'failed',
                'notes'          => ['content' => 'Gagal mengambil data, silahkan hubungi admin'],
            ]);
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan']);
        }

        if ($request->status !== 'completed') {
            $history->update([
                'payment_status' => 'failed',
                'process_status' => 'failed',
                'notes'          => ['content' => 'Gagal mengambil data, silahkan hubungi admin'],
            ]);
            return response()->json(['status' => true, 'message' => 'silahkan hubungi admin']);
        }
        return response()->json(
            json_decode($this->processProvider($history, $provider, $game, $denom, $config), true)
        );
    }

    private function processProvider($history = null, $provider = null, $game = null, $denom = null, $config = null)
    {
        try {
            return match ($provider->type_api) {
                1       => $this->handleApiV1Provider($denom, $history, $provider, $config),
                2       => $this->handleApiV2Provider($denom, $history, $provider, $config),
                default => json_encode(['status' => false, 'message' => 'Provider tidak dikenali']),
            };
        } catch (Exception $e) {
            return json_encode(['status' => false, 'message' => 'Gagal mengambil data, silahkan hubungi admin']);
        }
    }
    private function handleApiV1Provider($denom, $history, $provider, $config)
    {
        $firstName  = $history->temporary_data['first_name'] ?? $history->temporary_data['fist_name'] ?? '';
        $lastName   = $history->temporary_data['last_name'] ?? '';
        $name       = trim($firstName . ' ' . $lastName) ?: 'User ' . rand(1000, 9999);

        $gameId     = $history->product['game']['id'] ?? 0;
        $customData = $provider->custom_data ?? [];
        $codeGame   = null;
        foreach ($customData as $item) {
            if (($item['game_id'] ?? null) == $gameId && ($item['key'] ?? null) === 'c_cgame') {
                $codeGame = $item['value'] ?? null;
                break;
            }
        }

        $order = (new Apiv1())->order([
            'name'      => $name,
            'nama'      => $name,
            'api_key'   => $provider->api_key,
            'durasi'    => $denom['duration'] ?? $denom->duration ?? null,
            'code_game' => $codeGame ?? '',
            'url'       => $provider->url['register'] ?? null,
        ]);
        if (empty($order['status']) || $order['status'] === false) {
            return $this->failHistory($history);
        }

        $notes = [
            'content'  => str_replace('((license))', $order['data']['license'], $config->order['custom_notes']['success']),
            'download' => $provider->download,
            'tutorial' => $provider->tutorial,
        ];

        $this->successHistory($history, $notes);
        $this->sendSuccessInvoiceToUser($history, $config, $notes);

        return json_encode(['status' => true, 'message' => 'Berhasil']);
    }

    private function handleApiV2Provider($denom, $history, $provider, $config)
    {
        $firstName  = $history->temporary_data['first_name'] ?? $history->temporary_data['fist_name'] ?? '';
        $lastName   = $history->temporary_data['last_name'] ?? '';
        $name       = trim($firstName . ' ' . $lastName) ?: 'User ' . rand(1000, 9999);

        $gameId     = $history->product['game']['id'] ?? 0;
        $customData = $provider->custom_data ?? [];
        $gameIdVal  = null;
        foreach ($customData as $item) {
            if (($item['game_id'] ?? null) == $gameId && ($item['key'] ?? null) === 'c_gameid') {
                $gameIdVal = $item['value'] ?? null;
                break;
            }
        }

        $order = (new Apiv2())->register([
            'name'    => $name,
            'nama'    => $name,
            'api_key' => $provider->api_key,
            'game_id' => $gameIdVal ?? '',
            'durasi'  => $denom->duration ?? $denom['duration'] ?? null,
            'url'     => $provider->url,
        ]);

        if (empty($order['success']) || $order['success'] === false) {
            return $this->failHistory($history);
        }

        $license = $order['data']['Member Key'];

        $notes = [
            'content'  => str_replace('((license))', $license, $config->order['custom_notes']['success']),
            'download' => $provider->download,
            'tutorial' => $provider->tutorial,
        ];

        $this->successHistory($history, $notes);
        $this->sendSuccessInvoiceToUser($history, $config, $notes);

        return json_encode(['status' => true, 'message' => 'Berhasil']);
    }

    private function sendSuccessInvoiceToUser($history, $config, array $notes): void
    {
        $template = $config->captions['invoice'] ?? null;
        if (! $template) {
            $template = "<b>✅ PEMBELIAN BERHASIL</b>\n\n🧾 <b>ID Invoice:</b> <code>{invoice_id}</code>\n🎮 <b>Game:</b> {game}\n🏢 <b>Provider:</b> {provider}\n💎 <b>Masa Aktif:</b> {denom}\n💰 <b>Harga:</b> {price}\n\n🔑 <b>Keterangan / Lisensi:</b>\n{notes}\n\n📖 <b>Tutorial:</b>\n{tutorial}";
        }
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

        $downloadButtons = [];
        if (! empty($downloadLinks)) {
            foreach ($downloadLinks as $linkObj) {
                $titleVal = $linkObj['title'] ?? 'Download';
                $linkVal  = $linkObj['link'] ?? '';
                if ($linkVal) {
                    $downloadButtons[] = ['text' => $titleVal, 'url' => $linkVal];
                }
            }
        } else {
            // Fallback to notes/provider download links if any
            $fallbackDl = $notes['download'] ?? '';
            if ($fallbackDl) {
                if (filter_var($fallbackDl, FILTER_VALIDATE_URL)) {
                    $downloadButtons[] = ['text' => '📥 Download File', 'url' => $fallbackDl];
                } else {
                    $notes['content'] = ($notes['content'] ?? '') . "\n\n📥 <b>Download:</b>\n" . $fallbackDl;
                }
            }
        }

        // Formulate tutorial text
        if (empty($tutorialText)) {
            $tutorialText = $notes['tutorial'] ?? '';
        }

        $replaces = [
            '{invoice_id}' => $history->invoice_id,
            '{game}'       => $history->product['game']['name'] ?? '-',
            '{provider}'   => $history->product['provider']['name'] ?? '-',
            '{denom}'      => $history->product['denom']['name'] ?? '-',
            '{price}'      => 'Rp ' . number_format($history->price, 0, ',', '.'),
            '{notes}'      => $notes['content'] ?? '',
            '{download}'   => '', // Handled as buttons now
            '{tutorial}'   => $tutorialText ?: '-',
        ];

        $caption = str_replace(array_keys($replaces), array_values($replaces), $template);

        // Build keyboard
        $keyboard = [];
        foreach ($downloadButtons as $btn) {
            $keyboard[] = [$btn];
        }
        $keyboard[] = [
            ['text' => '🏠 Menu Utama', 'callback_data' => 'menu_start'],
        ];

        sendMessage([
            'chat_id'  => $history->temporary_data['userId'],
            'text'     => $caption,
            'mode'     => 'HTML',
            'keyboard' => $keyboard,
        ]);
    }

    private function failHistory($history = null, string $message = 'Gagal mengambil data, silahkan hubungi admin'): string
    {
        $config = Config::first();
        $history->update([
            'payment_status' => 'failed',
            'process_status' => 'failed',
            'notes'          => ['content' => $message],
        ]);
        $keyboard = [
            [
                ['text' => '👨‍💼 Hubungi Admin', 'url' => $config->bot['contact'] ?? 'https://t.me/username_admin'],
            ],
        ];
        sendMessage(['chat_id' => $history->temporary_data['userId'], 'text' => 'Gagal mengambil data, silahkan hubungi admin', 'keyboard' => $keyboard]);
        return json_encode(['status' => false, 'message' => $message]);
    }

    private function successHistory($history, array $notes): void
    {
        $history->update([
            'payment_status' => 'paid',
            'process_status' => 'paid',
            'notes'          => $notes,
        ]);
    }

}
