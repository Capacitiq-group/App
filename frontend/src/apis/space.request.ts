import httpClient from '@/apis/client'
import { BACKEND_API_ENDPOINT } from '@/constants/api/endpoints'

export type AgoraTokenPayload = {
    token: string
    app_id: string
    channel_name: string
    uid: number
    expires_at: number
}

const SpaceRequestApi = {
    refreshToken: (spaceUuid: string) =>
        httpClient.post<{ data: { agora: AgoraTokenPayload } }>(
            BACKEND_API_ENDPOINT.SPACE.TOKEN_REFRESH(spaceUuid),
            {}
        )
}

export default SpaceRequestApi
