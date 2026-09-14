import envConfig from '@/config/app.config'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

declare global {
    interface Window {
        Pusher: typeof Pusher
    }
}

let echoInstance: Echo<'reverb'> | null = null

/**
 * Get the shared Echo/Reverb client, creating it on first use.
 *
 * Auth for private/presence channels goes through /broadcasting/auth on the
 * API host. This app authenticates via an httpOnly-style `access_token`
 * cookie rather than a token the frontend holds directly (see apis/client.ts
 * — CookieToBearer on the backend converts that cookie into the JWT Bearer
 * header), so the authorizer here just needs `credentials: 'include'` for
 * the cookie to ride along — it never touches the token itself.
 */
export function getEcho(): Echo<'reverb'> {
    if (echoInstance) return echoInstance

    if (typeof window !== 'undefined') {
        window.Pusher = Pusher
    }

    const apiOrigin = new URL(envConfig.NEXT_PUBLIC_API_ENDPOINT).origin

    echoInstance = new Echo({
        broadcaster: 'reverb',
        key: envConfig.NEXT_PUBLIC_REVERB_APP_KEY,
        wsHost: envConfig.NEXT_PUBLIC_REVERB_HOST,
        wsPort: envConfig.NEXT_PUBLIC_REVERB_PORT,
        wssPort: envConfig.NEXT_PUBLIC_REVERB_PORT,
        forceTLS: envConfig.NEXT_PUBLIC_REVERB_SCHEME === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: `${apiOrigin}/broadcasting/auth`,
        authorizer: (channel: { name: string }) => ({
            authorize: (
                socketId: string,
                callback: (error: boolean, data: unknown) => void
            ) => {
                fetch(`${apiOrigin}/broadcasting/auth`, {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ socket_id: socketId, channel_name: channel.name })
                })
                    .then((res) => {
                        if (!res.ok) throw new Error(`Broadcasting auth failed: ${res.status}`)
                        return res.json()
                    })
                    .then((data) => callback(false, data))
                    .catch(() => callback(true, null))
            }
        })
    })

    return echoInstance
}

export function disconnectEcho(): void {
    echoInstance?.disconnect()
    echoInstance = null
}
