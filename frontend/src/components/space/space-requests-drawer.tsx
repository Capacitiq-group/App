import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { Drawer, DrawerContent, DrawerHeader, DrawerTitle } from '@/components/ui/drawer'
import { SpaceSpeakRequestDto } from '@/apis/space.request'
import { Check, X } from 'lucide-react'

type SpaceRequestsDrawerProps = {
    open: boolean
    onOpenChange: (open: boolean) => void
    requests: SpaceSpeakRequestDto[]
    onAccept: (requestId: number) => void
    onDecline: (requestId: number) => void
}

export function SpaceRequestsDrawer({ open, onOpenChange, requests, onAccept, onDecline }: SpaceRequestsDrawerProps) {
    return (
        <Drawer open={open} onOpenChange={onOpenChange}>
            <DrawerContent>
                <DrawerHeader>
                    <DrawerTitle>Requests to speak</DrawerTitle>
                </DrawerHeader>
                <div className='flex flex-col gap-2 px-4 pb-6'>
                    {requests.length === 0 && (
                        <p className='py-6 text-center text-sm text-muted-foreground'>No pending requests</p>
                    )}
                    {requests.map((request) => (
                        <div key={request.id} className='flex items-center justify-between gap-3 py-2'>
                            <div className='flex min-w-0 items-center gap-2.5'>
                                <Avatar className='size-9'>
                                    <AvatarImage src={request.user.avatar_url ?? undefined} alt={request.user.username} />
                                    <AvatarFallback>{request.user.username.slice(0, 2).toUpperCase()}</AvatarFallback>
                                </Avatar>
                                <span className='truncate text-sm font-medium text-foreground'>
                                    {request.user.username}
                                </span>
                            </div>
                            <div className='flex shrink-0 gap-2'>
                                <Button
                                    variant='outline'
                                    size='icon'
                                    aria-label='Decline'
                                    onClick={() => onDecline(request.id)}
                                >
                                    <X />
                                </Button>
                                <Button variant='brand' size='icon' aria-label='Accept' onClick={() => onAccept(request.id)}>
                                    <Check />
                                </Button>
                            </div>
                        </div>
                    ))}
                </div>
            </DrawerContent>
        </Drawer>
    )
}
