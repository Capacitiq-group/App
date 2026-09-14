import { SpaceAvatar } from '@/components/space/space-avatar'
import { Separator } from '@/components/ui/separator'
import { SpacePresenceMember } from '@/hooks/use-space-channel'

type SpaceStageProps = {
    members: SpacePresenceMember[]
    speakingLevels: Map<number, number>
}

export function SpaceStage({ members, speakingLevels }: SpaceStageProps) {
    const speakers = members.filter((m) => m.role !== 'listener')
    const listeners = members.filter((m) => m.role === 'listener')

    return (
        <div className='flex flex-1 flex-col gap-6 overflow-y-auto px-4 py-6'>
            <div className='flex flex-wrap justify-center gap-x-5 gap-y-6'>
                {speakers.map((member) => (
                    <SpaceAvatar
                        key={member.id}
                        name={member.username}
                        avatarUrl={member.avatar_url}
                        role={member.role}
                        isMuted={false}
                        speakingLevel={speakingLevels.get(member.id) ?? 0}
                    />
                ))}
            </div>

            {listeners.length > 0 && (
                <>
                    <Separator />
                    <div>
                        <p className='mb-3 text-xs font-medium text-muted-foreground'>
                            Listening &middot; {listeners.length}
                        </p>
                        <div className='flex flex-wrap gap-x-4 gap-y-4'>
                            {listeners.map((member) => (
                                <SpaceAvatar
                                    key={member.id}
                                    name={member.username}
                                    avatarUrl={member.avatar_url}
                                    role='listener'
                                    isMuted={false}
                                    size='sm'
                                />
                            ))}
                        </div>
                    </div>
                </>
            )}
        </div>
    )
}
