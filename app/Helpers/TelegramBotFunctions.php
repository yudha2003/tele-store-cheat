<?php

use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Laravel\Facades\Telegram;

function sendRequest(string $event, array $data)
{
    $logMsg = "[" . date('Y-m-d H:i:s') . "] Calling " . $event . " with data: " . json_encode($data) . PHP_EOL;
    try {
        file_put_contents(public_path('tele_debug.txt'), $logMsg, FILE_APPEND);
    } catch (\Throwable $e) {}

    try {
        $result = Telegram::{$event}($data);
        try {
            file_put_contents(public_path('tele_debug.txt'), "[" . date('Y-m-d H:i:s') . "] Success calling " . $event . PHP_EOL, FILE_APPEND);
        } catch (\Throwable $e) {}
        return $result;
    } catch (\Throwable $e) {
        $errorMsg = "[" . date('Y-m-d H:i:s') . "] Error calling " . $event . ": " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
        try {
            file_put_contents(public_path('tele_debug.txt'), $errorMsg, FILE_APPEND);
        } catch (\Throwable $ex) {}

        logger()->error('Telegram request error', [
            'event' => $event,
            'data'  => $data,
            'error' => $e->getMessage(),
        ]);

        if (isset($data['chat_id'])) {
            try {
                // Send raw error back to the user/admin chat
                Telegram::sendMessage([
                    'chat_id' => $data['chat_id'],
                    'text'    => "⚠️ Telegram API Error in " . $event . ":\n" . $e->getMessage() . "\n\nPayload:\n" . substr(json_encode($data, JSON_PRETTY_PRINT), 0, 3000),
                ]);
            } catch (\Throwable $ex) {
                try {
                    file_put_contents(public_path('tele_debug.txt'), "[" . date('Y-m-d H:i:s') . "] Error sending fallback: " . $ex->getMessage() . PHP_EOL, FILE_APPEND);
                } catch (\Throwable $ey) {}
            }
        }

        return null;
    }
}

function telegramKeyboard(?array $keyboard = null): ?string
{
    if (! $keyboard) {
        return null;
    }

    return json_encode([
        'inline_keyboard' => $keyboard,
    ]);
}

function telegramPhoto($photo)
{
    if (is_string($photo) && filter_var($photo, FILTER_VALIDATE_URL)) {
        return $photo;
    }

    return InputFile::create($photo);
}

function forwardMessage($chat_id, $from_chat_id, $message_id)
{
    return sendRequest('forwardMessage', [
        'chat_id'      => $chat_id,
        'from_chat_id' => $from_chat_id,
        'message_id'   => $message_id,
    ]);
}

function copyMessage($chat_id, $from_chat_id, $message_id, $options = [])
{
    return sendRequest('copyMessage', array_merge([
        'chat_id'      => $chat_id,
        'from_chat_id' => $from_chat_id,
        'message_id'   => $message_id,
    ], $options));
}

function sendMessage(array $data)
{
    $params = [
        'chat_id'    => $data['chat_id'] ?? null,
        'text'       => $data['text'] ?? '',
        'parse_mode' => $data['mode'] ?? 'HTML',
    ];

    if (! empty($data['keyboard'])) {
        $params['reply_markup'] = telegramKeyboard($data['keyboard']);
    }
    return sendRequest('sendMessage', $params);
}

function sendPhoto($chat_id, $photo, $caption, $keyboard = null, $mode = 'HTML')
{
    $params = [
        'chat_id'    => $chat_id,
        'photo'      => telegramPhoto($photo),
        'caption'    => $caption,
        'parse_mode' => $mode,
    ];

    if ($keyboard) {
        $params['reply_markup'] = telegramKeyboard($keyboard);
    }

    return sendRequest('sendPhoto', $params);
}

function editMessage($chat_id, $message_id, $photo, $caption, $keyboard = null, $mode = 'HTML')
{
    $media = [
        'type'       => 'photo',
        'media'      => $photo,
        'caption'    => $caption,
        'parse_mode' => $mode,
    ];

    $params = [
        'chat_id'    => $chat_id,
        'message_id' => $message_id,
        'media'      => json_encode($media),
    ];

    if ($keyboard) {
        $params['reply_markup'] = telegramKeyboard($keyboard);
    }

    return sendRequest('editMessageMedia', $params);
}

function editCaption(array $data)
{
    $params = [
        'chat_id'    => $data['chat_id'],
        'message_id' => $data['message_id'],
        'caption'    => $data['caption'],
        'parse_mode' => $data['parse_mode'],
    ];
    if (isset($data['keyboard'])) {
        $params['reply_markup'] = telegramKeyboard($data['keyboard']);
    }

    return sendRequest('editMessageCaption', $params);
}

function editText($chat_id, $message_id, $text, $keyboard = null, $mode = 'HTML')
{
    $params = [
        'chat_id'    => $chat_id,
        'message_id' => $message_id,
        'text'       => $text,
        'parse_mode' => $mode,
    ];

    if ($keyboard) {
        $params['reply_markup'] = telegramKeyboard($keyboard);
    }

    return sendRequest('editMessageText', $params);
}

function deleteMessage($chat_id = null, $message_id = null)
{
    return sendRequest('deleteMessage', [
        'chat_id'    => $chat_id,
        'message_id' => $message_id,
    ]);
}

function editKeyboard($chat_id, $message_id, $keyboard)
{
    return sendRequest('editMessageReplyMarkup', [
        'chat_id'      => $chat_id,
        'message_id'   => $message_id,
        'reply_markup' => telegramKeyboard($keyboard),
    ]);
}

function answerCallbackQuery($callback_query_id, $text = null)
{
    $params = [
        'callback_query_id' => $callback_query_id,
    ];

    if ($text) {
        $params['text'] = $text;
    }

    return sendRequest('answerCallbackQuery', $params);
}

function editMessageOrCaption(array $message, string $text, ?array $keyboard = null, string $mode = 'HTML')
{
    $chat_id    = $message['chat']['id'] ?? null;
    $message_id = $message['message_id'] ?? null;

    if (isset($message['photo'])) {
        return editCaption([
            'chat_id'    => $chat_id,
            'message_id' => $message_id,
            'caption'    => $text,
            'parse_mode' => $mode,
            'keyboard'   => $keyboard,
        ]);
    } else {
        return editText(
            $chat_id,
            $message_id,
            $text,
            $keyboard,
            $mode
        );
    }
}
function sendFallbackMessage($chat_id)
{
    $message = "Maaf, perintah tersebut tidak tersedia.\n\nSilakan gunakan tombol di bawah ini untuk kembali ke menu utama.";

    $keyboard = [
        [
            [
                'text'          => '⬅️ Kembali ke Menu Utama',
                'callback_data' => 'main_menu',
            ],
        ],
    ];

    return sendMessage([
        'chat_id'  => $chat_id,
        'text'     => $message,
        'mode'     => 'HTML',
        'keyboard' => $keyboard,
    ]);
}
