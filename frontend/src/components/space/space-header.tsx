import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { SpaceDto } from '@/apis/space.request'
import { LogOut, Users } from 'lucide-react'

type SpaceHeaderProps = {
    space: SpaceDto
    activeCount: number
    isHost: boolean
    onLeave: () => void
    onEnd: () => void
}

export function SpaceHeader({ space, activeCount, isHost, onLeave, onEnd }: SpaceHeaderProps) {
    const isWaiting = space.status === 'waiting'

    return (
        <div className='flex items-start justify-between gap-3 border-b border-border px-4 py-3'>
            <div className='min-w-0'>
                <div className='flex items-center gap-2'>
                    {isWaiting ? (
                        <Badge variant='outline'>Waiting for {space.min_to_start - activeCount} more</Badge>
                    ) : (
                        <Badge variant='brand' className='gap-1.5'>
                            <span className='size-1.5 animate-pulse rounded-full bg-current' />
                            Live
                        </Badge>
                    )}
                    <span className='flex items-center gap-1 text-xs text-muted-foreground'>
                        <Users className='size-3.5' />
                        {activeCount}
                    </span>
                </div>
                <h2 className='mt-1 truncate text-base font-semibold text-foreground'>{space.title}</h2>
                {space.description && (
                    <p className='truncate text-sm text-muted-foreground'>{space.description}</p>
                )}
            </div>

            {isHost ? (
                <Button variant='destructive' size='sm' onClick={onEnd}>
                    End
                </Button>
            ) : (
                <Button variant='outline' size='sm' onClick={onLeave}>
                    <LogOut />
                    Leave
                </Button>
            )}
        </div>
    )
}
