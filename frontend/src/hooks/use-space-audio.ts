import { AgoraConnectionState, SpaceAudioClient } from '@/lib/agora-rtc-client'
import { useCallback, useEffect, useRef, useState } from 'react'

type UseSpaceAudioParams = {
    appId: string
    channelName: string
    uid: number
    token: string
    /** Whether the current role can publish audio (host/co-host/speaker). */
    canPublish: boolean
}

export function useSpaceAudio({ appId, channelName, uid, token, canPublish }: UseSpaceAudioParams) {
    const clientRef = useRef<SpaceAudioClient | null>(null)
    const [connectionState, setConnectionState] = useState<AgoraConnectionState>('connecting')
    const [micEnabled, setMicEnabled] = useState(true)
    const [volumeLevels, setVolumeLevels] = useState<Map<number, number>>(new Map())
    const [remoteUids, setRemoteUids] = useState<Set<number>>(new Set())

    useEffect(() => {
        const client = new SpaceAudioClient()
        clientRef.current = client

        client.onConnectionStateChange(setConnectionState)
        client.onVolumeIndicator((levels) => {
            setVolumeLevels(new Map(levels.map((l) => [Number(l.uid), l.level])))
        })
        client.onUserPublished((remoteUid) => {
            setRemoteUids((prev) => new Set(prev).add(Number(remoteUid)))
        })
        client.onUserLeft((remoteUid) => {
            setRemoteUids((prev) => {
                const next = new Set(prev)
                next.delete(Number(remoteUid))
                return next
            })
        })

        void client.join({ appId, channelName, token, uid, canPublish })

        return () => {
            void client.leave()
            clientRef.current = null
        }
        // Intentionally not reacting to token/canPublish changes here — a
        // role change mid-session goes through applyRoleChange() below
        // (renew token in place) rather than a full leave/rejoin.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [appId, channelName, uid])

    const toggleMic = useCallback(async () => {
        const client = clientRef.current
        if (!client) return

        if (!client.isMicPublished()) {
            await client.publishMic()
            setMicEnabled(true)
            return
        }

        const next = !micEnabled
        client.setMicEnabled(next)
        setMicEnabled(next)
    }, [micEnabled])

    /** Call after a `speaker.promoted` broadcast + the resulting token refresh. */
    const applyRoleChange = useCallback(async (freshToken: string, nowCanPublish: boolean) => {
        const client = clientRef.current
        if (!client) return

        await client.renewToken(freshToken)

        if (nowCanPublish && !client.isMicPublished()) {
            await client.publishMic()
            setMicEnabled(true)
        }
    }, [])

    return { connectionState, micEnabled, toggleMic, volumeLevels, remoteUids, applyRoleChange }
}
