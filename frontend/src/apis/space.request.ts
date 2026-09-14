import httpClient from '@/apis/client'
import { BACKEND_API_ENDPOINT } from '@/constants/api/endpoints'

export type AgoraTokenPayload = {
    token: string
    app_id: string
    channel_name: string
    uid: number
    expires_at: number
}

export type SpaceRole = 'host' | 'co_host' | 'speaker' | 'listener'

export type SpaceUserSummary = {
    uuid: string
    username: string
    avatar_url: string | null
}

export type SpaceParticipantDto = {
    user: SpaceUserSummary
    role: SpaceRole
    is_muted: boolean
    joined_at: string
    agora?: AgoraTokenPayload
}

export type SpaceDto = {
    uuid: string
    title: string
    description: string | null
    status: 'waiting' | 'live' | 'ended'
    host: SpaceUserSummary
    min_to_start: number
    min_to_continue: number
    max_participants: number
    max_speakers: number
    duration_minutes: number
    active_participant_count?: number
    active_speaker_count?: number
    ends_at: string | null
}

export type SpaceSpeakRequestDto = {
    id: number
    user: SpaceUserSummary
    status: 'pending' | 'accepted' | 'declined' | 'cancelled'
    requested_at: string
}

type ApiEnvelope<T> = { data: T; message: string }

const SpaceRequestApi = {
    list: (topicId?: number) =>
        httpClient.get<ApiEnvelope<SpaceDto[]>>(
            BACKEND_API_ENDPOINT.SPACE.LIST + (topicId ? `?topic_id=${topicId}` : '')
        ),

    show: (spaceUuid: string) => httpClient.get<ApiEnvelope<SpaceDto>>(BACKEND_API_ENDPOINT.SPACE.DETAIL(spaceUuid)),

    create: (payload: { title: string; description?: string; topic_id?: number }) =>
        httpClient.post<ApiEnvelope<SpaceDto>>(BACKEND_API_ENDPOINT.SPACE.CREATE, payload),

    join: (spaceUuid: string, invitedByUserUuid?: string) =>
        httpClient.post<ApiEnvelope<{ space: SpaceDto; participant: SpaceParticipantDto }>>(
            BACKEND_API_ENDPOINT.SPACE.JOIN(spaceUuid),
            invitedByUserUuid ? { invited_by_user_uuid: invitedByUserUuid } : {}
        ),

    leave: (spaceUuid: string) => httpClient.post<void>(BACKEND_API_ENDPOINT.SPACE.LEAVE(spaceUuid), {}),

    end: (spaceUuid: string) => httpClient.post<ApiEnvelope<SpaceDto>>(BACKEND_API_ENDPOINT.SPACE.END(spaceUuid), {}),

    refreshToken: (spaceUuid: string) =>
        httpClient.post<ApiEnvelope<{ agora: AgoraTokenPayload }>>(
            BACKEND_API_ENDPOINT.SPACE.TOKEN_REFRESH(spaceUuid),
            {}
        ),

    requestToSpeak: (spaceUuid: string) =>
        httpClient.post<ApiEnvelope<SpaceSpeakRequestDto>>(
            BACKEND_API_ENDPOINT.SPACE.SPEAK_REQUEST_CREATE(spaceUuid),
            {}
        ),

    listSpeakRequests: (spaceUuid: string) =>
        httpClient.get<ApiEnvelope<SpaceSpeakRequestDto[]>>(BACKEND_API_ENDPOINT.SPACE.SPEAK_REQUEST_LIST(spaceUuid)),

    acceptSpeakRequest: (spaceUuid: string, speakRequestId: number) =>
        httpClient.post<ApiEnvelope<SpaceSpeakRequestDto>>(
            BACKEND_API_ENDPOINT.SPACE.SPEAK_REQUEST_ACCEPT(spaceUuid, speakRequestId),
            {}
        ),

    declineSpeakRequest: (spaceUuid: string, speakRequestId: number) =>
        httpClient.post<ApiEnvelope<SpaceSpeakRequestDto>>(
            BACKEND_API_ENDPOINT.SPACE.SPEAK_REQUEST_DECLINE(spaceUuid, speakRequestId),
            {}
        ),

    muteParticipant: (spaceUuid: string, userUuid: string) =>
        httpClient.post<ApiEnvelope<SpaceParticipantDto>>(
            BACKEND_API_ENDPOINT.SPACE.PARTICIPANT_MUTE(spaceUuid, userUuid),
            {}
        ),

    unmuteParticipant: (spaceUuid: string, userUuid: string) =>
        httpClient.delete<ApiEnvelope<SpaceParticipantDto>>(
            BACKEND_API_ENDPOINT.SPACE.PARTICIPANT_MUTE(spaceUuid, userUuid)
        ),

    removeParticipant: (spaceUuid: string, userUuid: string) =>
        httpClient.delete<void>(BACKEND_API_ENDPOINT.SPACE.PARTICIPANT_REMOVE(spaceUuid, userUuid)),

    banParticipant: (spaceUuid: string, userUuid: string, reason?: string) =>
        httpClient.post<void>(BACKEND_API_ENDPOINT.SPACE.PARTICIPANT_BAN(spaceUuid, userUuid), reason ? { reason } : {})
}

export default SpaceRequestApi
