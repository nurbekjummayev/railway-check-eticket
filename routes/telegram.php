<?php

/** @var SergiX44\Nutgram\Nutgram $bot */

use App\Models\TelegramUser;
/*
|--------------------------------------------------------------------------
| Nutgram Handlers
|--------------------------------------------------------------------------
|
| Here is where you can register telegram handlers for Nutgram. These
| handlers are loaded by the NutgramServiceProvider. Enjoy!
|
*/

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;

$bot->onCommand('start', function (Nutgram $bot) {
    $user = TelegramUser::updateOrCreate(
        ['chat_id' => $bot->userId()],
        [
            'username' => $bot->user()->username,
            'first_name' => $bot->user()->first_name,
            'last_name' => $bot->user()->last_name,
        ]
    );

    $keyboard = ReplyKeyboardMarkup::make(resize_keyboard: true)
        ->addRow(
            KeyboardButton::make('🔔 Bildirishnoma sozlamalari')
        );

    $bot->sendMessage(
        text: "Assalomu alaykum! 👋\n\n".
              "Men sizga temir yo'l chiptalarini kuzatishda yordam beraman.\n\n".
              'Quyidagi tugmalardan birini tanlang:',
        reply_markup: $keyboard
    );
})->description('Botni boshlash');

$bot->onText('🔔 Bildirishnoma sozlamalari', function (Nutgram $bot) {
    $user = TelegramUser::where('chat_id', $bot->userId())->first();

    if (! $user) {
        $bot->sendMessage('Avval /start buyrug\'ini bosing.');

        return;
    }

    $foundIcon = $user->notify_when_found ? '✅' : '❌';
    $notFoundIcon = $user->notify_when_not_found ? '✅' : '❌';

    $keyboard = InlineKeyboardMarkup::make()
        ->addRow(
            InlineKeyboardButton::make(
                text: "{$foundIcon} Xabar yuborish",
                callback_data: 'toggle_notify_found'
            )
        )
        ->addRow(
            InlineKeyboardButton::make(
                text: "{$notFoundIcon} Topilmaganda xabar yuborish",
                callback_data: 'toggle_notify_not_found'
            )
        );

    $bot->sendMessage(
        text: "⚙️ <b>Bildirishnoma sozlamalari</b>\n\n".
              "Qaysi xabarnomalarni olishni xohlaysiz?\n".
              "Kerakli sozlamani yoqish/o'chirish uchun tugmani bosing.",
        parse_mode: 'HTML',
        reply_markup: $keyboard
    );
});

$bot->onCallbackQueryData('toggle_notify_found', function (Nutgram $bot) {
    $user = TelegramUser::where('chat_id', $bot->userId())->first();

    if (! $user) {
        try {
            $bot->answerCallbackQuery('Xatolik yuz berdi!');
        } catch (\Exception $e) {
            // Ignore if callback query is too old
        }

        return;
    }

    $user->notify_when_found = ! $user->notify_when_found;
    $user->save();

    // Refresh user to get latest values
    $user->refresh();

    $status = $user->notify_when_found ? 'yoqildi' : 'o\'chirildi';

    try {
        $bot->answerCallbackQuery("Topilganda xabar yuborish {$status}!");
    } catch (\Exception $e) {
        // Ignore if callback query is too old
    }

    $foundIcon = $user->notify_when_found ? '✅' : '❌';
    $notFoundIcon = $user->notify_when_not_found ? '✅' : '❌';

    $keyboard = InlineKeyboardMarkup::make()
        ->addRow(
            InlineKeyboardButton::make(
                text: "{$foundIcon} Xabar yuborish",
                callback_data: 'toggle_notify_found'
            )
        )
        ->addRow(
            InlineKeyboardButton::make(
                text: "{$notFoundIcon} Topilmaganda xabar yuborish",
                callback_data: 'toggle_notify_not_found'
            )
        );

    $bot->editMessageText(
        text: "⚙️ <b>Bildirishnoma sozlamalari</b>\n\n".
              "Qaysi xabarnomalarni olishni xohlaysiz?\n".
              "Kerakli sozlamani yoqish/o'chirish uchun tugmani bosing.",
        parse_mode: 'HTML',
        reply_markup: $keyboard
    );
});

$bot->onCallbackQueryData('toggle_notify_not_found', function (Nutgram $bot) {
    $user = TelegramUser::where('chat_id', $bot->userId())->first();

    if (! $user) {
        try {
            $bot->answerCallbackQuery('Xatolik yuz berdi!');
        } catch (\Exception $e) {
            // Ignore if callback query is too old
        }

        return;
    }

    $user->notify_when_not_found = ! $user->notify_when_not_found;
    $user->save();

    // Refresh user to get latest values
    $user->refresh();

    $status = $user->notify_when_not_found ? 'yoqildi' : 'o\'chirildi';

    try {
        $bot->answerCallbackQuery("Topilmaganda xabar yuborish {$status}!");
    } catch (\Exception $e) {
        // Ignore if callback query is too old
    }

    $foundIcon = $user->notify_when_found ? '✅' : '❌';
    $notFoundIcon = $user->notify_when_not_found ? '✅' : '❌';

    $keyboard = InlineKeyboardMarkup::make()
        ->addRow(
            InlineKeyboardButton::make(
                text: "{$foundIcon} Xabar yuborish",
                callback_data: 'toggle_notify_found'
            )
        )
        ->addRow(
            InlineKeyboardButton::make(
                text: "{$notFoundIcon} Topilmaganda xabar yuborish",
                callback_data: 'toggle_notify_not_found'
            )
        );

    $bot->editMessageText(
        text: "⚙️ <b>Bildirishnoma sozlamalari</b>\n\n".
              "Qaysi xabarnomalarni olishni xohlaysiz?\n".
              "Kerakli sozlamani yoqish/o'chirish uchun tugmani bosing.",
        parse_mode: 'HTML',
        reply_markup: $keyboard
    );
});
