<?php

namespace App\Modules\Lottery\Services;

use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class DuelParticipantService
{
    private const SESSION_ID = 'lottery_duel.participant_id';

    private const SESSION_NICKNAME = 'lottery_duel.nickname';

    public function id(Request $request, bool $create = true): ?string
    {
        if ($request->user()) {
            return 'user:'.$request->user()->getAuthIdentifier();
        }

        $participantId = $request->session()->get(self::SESSION_ID);

        if (! $participantId && $create) {
            $participantId = 'guest:'.Str::lower((string) Str::ulid());
            $request->session()->put(self::SESSION_ID, $participantId);
        }

        return $participantId;
    }

    public function nickname(Request $request, ?string $guestNickname = null): string
    {
        if ($request->user()) {
            return $request->user()->nickname ?: '@'.$request->user()->username;
        }

        if ($guestNickname !== null && trim($guestNickname) !== '') {
            $nickname = trim($guestNickname);
            $request->session()->put(self::SESSION_NICKNAME, $nickname);

            return $nickname;
        }

        return (string) $request->session()->get(self::SESSION_NICKNAME, '訪客');
    }

    public function identity(Request $request): ?GenericUser
    {
        $participantId = $this->id($request, false);

        if (! $participantId) {
            return null;
        }

        return new GenericUser([
            'id' => $participantId,
            'name' => $this->nickname($request),
        ]);
    }
}
