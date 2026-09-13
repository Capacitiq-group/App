<?php

namespace App\Services\Space;

use App\Enums\Space\SpaceParticipantRoleEnum;
use App\Exceptions\http\BusinessException;
use Agora\RtcTokenBuilder2;

/**
 * Mints short-lived Agora RTC tokens on demand. Tokens are never persisted —
 * see the spaces/space_participants migrations for why.
 *
 * Requires the Agora community PHP token-builder package:
 *   composer require agoraio-community/tools-php
 *
 * This class deliberately does NOT implement Agora's token-signing algorithm
 * itself (AccessToken2 / HMAC-SHA256 over a specific binary layout) — getting
 * that subtly wrong produces a token that fails silently on the client with
 * no useful error, so it's built on the vendor's own library rather than a
 * hand-rolled port of it.
 */
class AgoraTokenService
{
    /**
     * Generate an RTC token for a participant joining a Space's Agora channel.
     *
     * @param  string  $channelName  The Space's agora_channel_name.
     * @param  int  $agoraUid  The participant's per-channel numeric Agora UID.
     * @param  SpaceParticipantRoleEnum  $role  Determines publisher vs subscriber privileges.
     * @return array{token: string, app_id: string, channel_name: string, uid: int, expires_at: int}
     *
     * @throws BusinessException If Agora credentials are not configured.
     */
    public function generateRtcToken(string $channelName, int $agoraUid, SpaceParticipantRoleEnum $role): array
    {
        $appId = (string) config('agora.app_id');
        $appCertificate = (string) config('agora.app_certificate');

        if ($appId === '' || $appCertificate === '') {
            throw new BusinessException('Agora is not configured (missing app_id/app_certificate).');
        }

        $expirySeconds = (int) config('agora.token_expiry_seconds', 3600);
        $expiresAt = time() + $expirySeconds;

        // PUBLISHER can send + receive audio (host/co-host/speaker);
        // SUBSCRIBER can only receive (listener). See SpaceParticipantRoleEnum::canPublishAudio().
        $agoraRole = $role->canPublishAudio()
            ? RtcTokenBuilder2::ROLE_PUBLISHER
            : RtcTokenBuilder2::ROLE_SUBSCRIBER;

        $token = RtcTokenBuilder2::buildTokenWithUid(
            $appId,
            $appCertificate,
            $channelName,
            $agoraUid,
            $agoraRole,
            $expirySeconds,
            $expirySeconds,
        );

        return [
            'token' => $token,
            'app_id' => $appId,
            'channel_name' => $channelName,
            'uid' => $agoraUid,
            'expires_at' => $expiresAt,
        ];
    }
}
