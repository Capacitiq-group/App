import SpaceRequestApi, { AgoraTokenPayload } from '@/apis/space.request'
import { disconnectEcho, getEcho } from '@/lib/echo'
import { useCallback, useEffect, useRef, useState } from 'react'

export type SpacePresenceMember = {
    id: number
    uuid: string
    username: string
    avatar_url: string | null
    role: 'host' | 'co_host' | 'speaker' | 'listener'
}

type SpeakerPromotedPayload = { space_uuid: string; promoted_user_id: number; actor_id: number }
type SpeakRequestCreatedPayload = { space_uuid: string; speak_request_id: number; requesting_user_id: number }
type SpeakRequestDeclinedPayload = { space_uuid: string; speak_request_id: number; requesting_user_id: number }
type ParticipantMutedPayload = { space_uuid: string; target_user_id: number; muted: boolean; actor_id: number }
type ParticipantRemovedPayload = { space_uuid: string; target_user_id: number; banned: boolean; actor_id: number }
type EndedPayload = { space_uuid: string; reason: string }

type UseSpaceChannelOptions = {
    spaceUuid: string
    currentUserId: number
    /** Called with a fresh Agora token right after this user's role changes. */
    onTokenRefreshed?: (agora: AgoraTokenPayload) => void
    onSpeakRequestCreated?: (payload: SpeakRequestCreatedPayload) => void
    onSpeakRequestDeclined?: (payload: SpeakRequestDeclinedPayload) => void
    onParticipantMuted?: (payload: ParticipantMutedPayload) => void
    onParticipantRemoved?: (payload: ParticipantRemovedPayload) => void
    onEnded?: (payload: EndedPayload) => void
}

/**
 * Subscribes to a Space's presence channel for the lifetime of the
 * component. Handles the one non-obvious bit of wiring: when the current
 * user is promoted to speaker, the promotion broadcast deliberately carries
 * no token (see SpaceSpeakerPromotedEvent's docblock) — this hook reacts to
 * that event by calling the token-refresh endpoint itself.
 */
export function useSpaceChannel({
    spaceUuid,
    currentUserId,
    onTokenRefreshed,
    onSpeakRequestCreated,
    onSpeakRequestDeclined,
    onParticipantMuted,
    onParticipantRemoved,
    onEnded
}: UseSpaceChannelOptions) {
    const [members, setMembers] = useState<Map<number, SpacePresenceMember>>(new Map())
    const [connected, setConnected] = useState(false)
    const leaveChannelRef = useRef<() => void>(() => {})

    const refreshToken = useCallback(async () => {
        try {
            const res = await SpaceRequestApi.refreshToken(spaceUuid)
            onTokenRefreshed?.(res.data.agora)
        } catch {
            // Swallow — the room UI can offer a manual "reconnect" action if
            // this fails; a failed background refresh shouldn't crash the view.
        }
    }, [spaceUuid, onTokenRefreshed])

    useEffect(() => {
        const echo = getEcho()
        const channelName = `space.${spaceUuid}`

        const channel = echo
            .join(channelName)
            .here((initialMembers: SpacePresenceMember[]) => {
                setMembers(new Map(initialMembers.map((m) => [m.id, m])))
                setConnected(true)
            })
            .joining((member: SpacePresenceMember) => {
                setMembers((prev) => new Map(prev).set(member.id, member))
            })
            .leaving((member: SpacePresenceMember) => {
                setMembers((prev) => {
                    const next = new Map(prev)
                    next.delete(member.id)
                    return next
                })
            })
            .listen('.speaker.promoted', (payload: SpeakerPromotedPayload) => {
                setMembers((prev) => {
                    const member = prev.get(payload.promoted_user_id)
                    if (!member) return prev
                    const next = new Map(prev)
                    next.set(member.id, { ...member, role: 'speaker' })
                    return next
                })
                if (payload.promoted_user_id === currentUserId) {
                    void refreshToken()
                }
            })
            .listen('.speak-request.created', (payload: SpeakRequestCreatedPayload) => {
                onSpeakRequestCreated?.(payload)
            })
            .listen('.speak-request.declined', (payload: SpeakRequestDeclinedPayload) => {
                onSpeakRequestDeclined?.(payload)
            })
            .listen('.participant.muted', (payload: ParticipantMutedPayload) => {
                onParticipantMuted?.(payload)
            })
            .listen('.participant.removed', (payload: ParticipantRemovedPayload) => {
                setMembers((prev) => {
                    const next = new Map(prev)
                    next.delete(payload.target_user_id)
                    return next
                })
                onParticipantRemoved?.(payload)
            })
            .listen('.ended', (payload: EndedPayload) => {
                onEnded?.(payload)
            })

        leaveChannelRef.current = () => echo.leave(channelName)
        void channel

        return () => {
            leaveChannelRef.current()
            setConnected(false)
            setMembers(new Map())
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [spaceUuid, currentUserId])

    return { members: Array.from(members.values()), connected }
}

export { disconnectEcho }
