import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Hand, Mic, MicOff } from 'lucide-react'

type SpaceActionBarProps = {
    role: 'host' | 'co_host' | 'speaker' | 'listener'
    micEnabled: boolean
    onToggleMic: () => void
    hasPendingRequest: boolean
    onRequestToSpeak: () => void
    canModerate: boolean
    pendingRequestCount: number
    onOpenRequests: () => void
}

export function SpaceActionBar({
    role,
    micEnabled,
    onToggleMic,
    hasPendingRequest,
    onRequestToSpeak,
    canModerate,
    pendingRequestCount,
    onOpenRequests
}: SpaceActionBarProps) {
    const canSpeak = role !== 'listener'

    return (
        <div className='flex items-center justify-center gap-3 border-t border-border px-4 py-3'>
            {canSpeak ? (
                <Button variant={micEnabled ? 'brand' : 'outline'} size='lg' onClick={onToggleMic}>
                    {micEnabled ? <Mic /> : <MicOff />}
                    {micEnabled ? 'Mic on' : 'Mic off'}
                </Button>
            ) : (
                <Button variant={hasPendingRequest ? 'secondary' : 'brand'} size='lg' disabled={hasPendingRequest} onClick={onRequestToSpeak}>
                    <Hand />
                    {hasPendingRequest ? 'Request sent' : 'Raise hand'}
                </Button>
            )}

            {canModerate && (
                <Button variant='outline' size='lg' className='relative' onClick={onOpenRequests}>
                    Requests
                    {pendingRequestCount > 0 && (
                        <Badge variant='destructive' className='absolute -right-2 -top-2 size-5 justify-center rounded-full p-0'>
                            {pendingRequestCount}
                        </Badge>
                    )}
                </Button>
            )}
        </div>
    )
}
