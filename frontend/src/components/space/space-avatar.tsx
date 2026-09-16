import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { cn } from '@/lib/utils'
import { MicOff } from 'lucide-react'

type SpaceAvatarProps = {
    name: string
    avatarUrl: string | null
    role: 'host' | 'co_host' | 'speaker' | 'listener'
    isMuted: boolean
    /** Live Agora volume level, 0–1. Drives the speaking ring's intensity directly. */
    speakingLevel?: number
    size?: 'sm' | 'lg'
    /** When provided, the avatar becomes tappable to open the tip dialog for this person. */
    onTip?: () => void
}

const ROLE_LABEL: Record<SpaceAvatarProps['role'], string> = {
    host: 'Host',
    co_host: 'Co-host',
    speaker: 'Speaker',
    listener: ''
}

export function SpaceAvatar({ name, avatarUrl, role, isMuted, speakingLevel = 0, size = 'lg', onTip }: SpaceAvatarProps) {
    const initials = name.slice(0, 2).toUpperCase()
    const canSpeak = role !== 'listener'
    const isSpeaking = canSpeak && !isMuted && speakingLevel > 0.05

    const avatarBlock = (
        <div className='flex flex-col items-center gap-1.5'>
            <div className='relative'>
                <Avatar
                    className={cn(size === 'lg' ? 'size-16' : 'size-11', 'ring-2 ring-transparent transition-shadow')}
                    style={
                        isSpeaking
                            ? {
                                  boxShadow: `0 0 0 ${2 + speakingLevel * 5}px color-mix(in oklch, var(--primary) ${30 + speakingLevel * 50}%, transparent)`
                              }
                            : undefined
                    }
                >
                    <AvatarImage src={avatarUrl ?? undefined} alt={name} />
                    <AvatarFallback className={size === 'lg' ? 'text-base' : 'text-xs'}>{initials}</AvatarFallback>
                </Avatar>
                {canSpeak && isMuted && (
                    <span className='absolute -bottom-0.5 -right-0.5 flex size-5 items-center justify-center rounded-full bg-destructive text-white'>
                        <MicOff className='size-3' />
                    </span>
                )}
            </div>
            <div className='flex flex-col items-center gap-0'>
                <span className='max-w-20 truncate text-xs font-medium text-foreground'>{name}</span>
                {ROLE_LABEL[role] && (
                    <span className='text-[10px] leading-tight text-muted-foreground'>{ROLE_LABEL[role]}</span>
                )}
            </div>
        </div>
    )

    if (!onTip) return avatarBlock

    return (
        <button type='button' onClick={onTip} className='rounded-lg transition-opacity hover:opacity-80' aria-label={`Tip ${name}`}>
            {avatarBlock}
        </button>
    )
}
