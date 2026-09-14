import AgoraRTC, { IAgoraRTCClient, IMicrophoneAudioTrack, UID } from 'agora-rtc-sdk-ng'

export type AgoraConnectionState = 'disconnected' | 'connecting' | 'connected' | 'reconnecting'

export type VolumeIndicator = { uid: UID; level: number }

type JoinParams = {
    appId: string
    channelName: string
    token: string
    uid: number
    /** Whether this user can publish audio (host/co-host/speaker) vs. listen only. */
    canPublish: boolean
}

/**
 * One instance per active Space session. Not a singleton — a user is only
 * ever in one Space at a time (see the "one active Space" rule), so the
 * owning component controls this client's lifecycle directly.
 */
export class SpaceAudioClient {
    private client: IAgoraRTCClient
    private micTrack: IMicrophoneAudioTrack | null = null

    constructor() {
        this.client = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' })
        // Volume indicator drives the speaking-ring UI — see useSpaceAudio.
        this.client.enableAudioVolumeIndicator()
    }

    onVolumeIndicator(callback: (levels: VolumeIndicator[]) => void) {
        this.client.on('volume-indicator', (volumes) => {
            callback(volumes.map((v) => ({ uid: v.uid, level: v.level / 100 })))
        })
    }

    onUserPublished(callback: (uid: UID) => void) {
        this.client.on('user-published', async (user, mediaType) => {
            if (mediaType !== 'audio') return
            await this.client.subscribe(user, mediaType)
            user.audioTrack?.play()
            callback(user.uid)
        })
    }

    onUserLeft(callback: (uid: UID) => void) {
        this.client.on('user-left', (user) => callback(user.uid))
    }

    onConnectionStateChange(callback: (state: AgoraConnectionState) => void) {
        this.client.on('connection-state-change', (curState) => {
            const mapped: Record<string, AgoraConnectionState> = {
                DISCONNECTED: 'disconnected',
                CONNECTING: 'connecting',
                CONNECTED: 'connected',
                RECONNECTING: 'reconnecting',
                DISCONNECTING: 'disconnected'
            }
            callback(mapped[curState] ?? 'disconnected')
        })
    }

    async join({ appId, channelName, token, uid, canPublish }: JoinParams): Promise<void> {
        await this.client.join(appId, channelName, token, uid)

        if (canPublish) {
            await this.publishMic()
        }
    }

    async publishMic(): Promise<void> {
        if (this.micTrack) return
        this.micTrack = await AgoraRTC.createMicrophoneAudioTrack()
        await this.client.publish(this.micTrack)
    }

    async unpublishMic(): Promise<void> {
        if (!this.micTrack) return
        await this.client.unpublish(this.micTrack)
        this.micTrack.close()
        this.micTrack = null
    }

    setMicEnabled(enabled: boolean): void {
        this.micTrack?.setEnabled(enabled)
    }

    isMicPublished(): boolean {
        return this.micTrack !== null
    }

    /** Swap in a fresh token without leaving/rejoining — used after a role promotion. */
    async renewToken(token: string): Promise<void> {
        await this.client.renewToken(token)
    }

    async leave(): Promise<void> {
        await this.unpublishMic()
        await this.client.leave()
        this.client.removeAllListeners()
    }
}
