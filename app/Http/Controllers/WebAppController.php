<?php

namespace App\Http\Controllers;

use App\Models\TelegramUser;
use App\Services\ETicketService;
use App\Services\TelegramWebAppValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class WebAppController extends Controller
{
    public function __construct(
        private ETicketService $eticketService,
        private TelegramWebAppValidator $validator
    ) {}

    /**
     * Show the main web app interface.
     */
    public function index()
    {
        return view('webapp.index');
    }

    /**
     * Authenticate the user using Telegram's initData.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $initData = $request->input('initData');

        if (! $initData || ! $this->validator->validate($initData)) {
            throw ValidationException::withMessages([
                'initData' => 'The provided initData is invalid or missing.',
            ]);
        }

        $data = $this->validator->parseInitData($initData);
        $userData = $data['user'];

        $user = TelegramUser::firstOrCreate(
            ['chat_id' => $userData['id']],
            [
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'] ?? null,
                'username' => $userData['username'] ?? null,
            ]
        );

        Auth::login($user);

        return response()->json(['status' => 'ok']);
    }

    public function deleteSearch(Request $request, int $id)
    {
        /** @var TelegramUser $user */
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $search = $user->ticketSearches()->findOrFail($id);
        $search->delete();

        return response()->json(['success' => true]);
    }
}
