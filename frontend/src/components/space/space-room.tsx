'use client'

import SpaceRequestApi, { SpaceDto, SpaceRole, SpaceSpeakRequestDto } from '@/apis/space.request'
import { SpaceActionBar } from '@/components/space/space-action-bar'
import { SpaceHeader } from '@/components/space/space-header'
import { SpaceRequestsDrawer } from '@/components/space/space-requests-drawer'
import { SpaceStage } from '@/components/space/space-stage'
import { TipDialog } from '@/components/tip/tip-dialog'
import { useSpaceAudio } from '@/hooks/use-space-audio'
import { useSpaceChannel, SpacePresenceMember } from '@/hooks/use-space-channel'
import { useEffect, useRef, useState } from 'react'
import { toast } from 'sonner'

type SpaceRoomProps = {
    spaceUuid: string
    currentUserId: number
    /** Called after leaving/being removed/the Space ending, to navigate away. */
    onExit: () => void
}

export function SpaceRoom({ spaceUuid, currentUserId, onExit }: SpaceRoomProps) {
    const [space, setSpace] = useState<SpaceDto | null>(null)
    const [myRole, setMyRole] = useState<SpaceRole>('listener')
    const [myAgoraUid, setMyAgoraUid] = useState<number | null>(null)
    const [initialToken, setInitialToken] = useState<string | null>(null)
    const [hasPendingRequest, setHasPendingRequest] = useState(false)
    const [pendingRequests, setPendingRequests] = useState<SpaceSpeakRequestDto[]>([])
    const [requestsDrawerOpen, setRequestsDrawerOpen] = useState(false)
    const [tipTarget, setTipTarget] = useState<SpacePresenceMember | null>(null)
    const hasLeftRef = useRef(false)

    // Join on mount.
    useEffect(() => {
        let cancelled = false

        SpaceRequestApi.join(spaceUuid)
            .then((res) => {
                if (cancelled) return
                setSpace(res.data.space)
                setMyRole(res.data.participant.role)
                setMyAgoraUid(res.data.participant.agora?.uid ?? null)
                setInitialToken(res.data.participant.agora?.token ?? null)
            })
            .catch(() => {
                toast.error("Couldn't join this Space")
                onExit()
            })

        return () => {
            cancelled = true
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [spaceUuid])

    const canModerate = myRole === 'host' || myRole === 'co_host'

    // Load the existing pending-requests queue once we know we can moderate —
    // the real-time event only covers requests made *after* we're connected.
    useEffect(() => {
        if (!canModerate) return
        SpaceRequestApi.listSpeakRequests(spaceUuid)
            .then((res) => setPendingRequests(res.data))
            .catch(() => {})
    }, [canModerate, spaceUuid])

    const { members, connected } = useSpaceChannel({
        spaceUuid,
        currentUserId,
        onTokenRefreshed: (agora) => {
            void applyRoleChange(agora.token, true)
            setMyRole('speaker')
        },
        onSpeakRequestCreated: (payload) => {
            if (!canModerate) return
            setPendingRequests((prev) => [
                ...prev,
                {
                    id: payload.speak_request_id,
                    status: 'pending',
                    requested_at: new Date().toISOString(),
                    user: members.find((m) => m.id === payload.requesting_user_id)
                        ? {
                              uuid: members.find((m) => m.id === payload.requesting_user_id)!.uuid,
                              username: members.find((m) => m.id === payload.requesting_user_id)!.username,
                              avatar_url: members.find((m) => m.id === payload.requesting_user_id)!.avatar_url
                          }
                        : { uuid: '', username: 'Someone', avatar_url: null }
                }
            ])
        },
        onSpeakRequestDeclined: (payload) => {
            if (payload.requesting_user_id === currentUserId) {
                setHasPendingRequest(false)
                toast('Your request to speak was declined')
            }
        },
        onParticipantRemoved: (payload) => {
            if (payload.target_user_id === currentUserId) {
                hasLeftRef.current = true
                toast(payload.banned ? 'You were removed from this Space' : 'You were removed from this Space')
                onExit()
            }
        },
        onEnded: () => {
            hasLeftRef.current = true
            toast('This Space has ended')
            onExit()
        }
    })

    const { micEnabled, toggleMic, volumeLevels, applyRoleChange } = useSpaceAudio({
        appId: '', // filled in once the join response resolves — see below
        channelName: space?.uuid ?? '',
        uid: myAgoraUid ?? 0,
        token: initialToken ?? '',
        canPublish: myRole !== 'listener'
    })

    useEffect(() => {
        return () => {
            if (!hasLeftRef.current) {
                void SpaceRequestApi.leave(spaceUuid)
            }
        }
    }, [spaceUuid])

    if (!space || myAgoraUid === null) {
        return (
            <div className='flex h-full items-center justify-center text-sm text-muted-foreground'>
                Joining Space…
            </div>
        )
    }

    const handleLeave = async () => {
        hasLeftRef.current = true
        await SpaceRequestApi.leave(spaceUuid)
        onExit()
    }

    const handleEnd = async () => {
        hasLeftRef.current = true
        await SpaceRequestApi.end(spaceUuid)
        onExit()
    }

    const handleRequestToSpeak = async () => {
        try {
            await SpaceRequestApi.requestToSpeak(spaceUuid)
            setHasPendingRequest(true)
        } catch {
            toast.error("Couldn't send your request")
        }
    }

    const handleAccept = async (requestId: number) => {
        await SpaceRequestApi.acceptSpeakRequest(spaceUuid, requestId)
        setPendingRequests((prev) => prev.filter((r) => r.id !== requestId))
    }

    const handleDecline = async (requestId: number) => {
        await SpaceRequestApi.declineSpeakRequest(spaceUuid, requestId)
        setPendingRequests((prev) => prev.filter((r) => r.id !== requestId))
    }

    return (
        <div className='flex h-full flex-col bg-background'>
            <SpaceHeader
                space={space}
                activeCount={members.length}
                isHost={myRole === 'host'}
                onLeave={handleLeave}
                onEnd={handleEnd}
            />
            <SpaceStage
                members={members}
                speakingLevels={volumeLevels}
                currentUserId={currentUserId}
                onTipSpeaker={setTipTarget}
            />
            <SpaceActionBar
                role={myRole}
                micEnabled={micEnabled}
                onToggleMic={() => void toggleMic()}
                hasPendingRequest={hasPendingRequest}
                onRequestToSpeak={() => void handleRequestToSpeak()}
                canModerate={canModerate}
                pendingRequestCount={pendingRequests.length}
                onOpenRequests={() => setRequestsDrawerOpen(true)}
            />
            <SpaceRequestsDrawer
                open={requestsDrawerOpen}
                onOpenChange={setRequestsDrawerOpen}
                requests={pendingRequests}
                onAccept={(id) => void handleAccept(id)}
                onDecline={(id) => void handleDecline(id)}
            />
            {tipTarget && (
                <TipDialog
                    open={tipTarget !== null}
                    onOpenChange={(isOpen) => !isOpen && setTipTarget(null)}
                    recipientUuid={tipTarget.uuid}
                    recipientUsername={tipTarget.username}
                    recipientAvatarUrl={tipTarget.avatar_url}
                    sourceType='space'
                    sourceUuid={space.uuid}
                />
            )}
            {!connected && (
                <div className='absolute inset-x-0 top-0 bg-secondary py-1 text-center text-xs text-secondary-foreground'>
                    Reconnecting…
                </div>
            )}
        </div>
    )
}
