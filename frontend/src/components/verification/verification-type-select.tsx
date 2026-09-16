'use client'

import { BillingCycleToggle } from '@/components/verification/billing-cycle-toggle'
import { VerificationApplicantType, VerificationBillingCycle } from '@/types/dtos/verification/verification-request.dto'
import { Building2, Landmark, User } from 'lucide-react'

type VerificationTypeSelectProps = {
    billingCycle: VerificationBillingCycle
    onBillingCycleChange: (cycle: VerificationBillingCycle) => void
    onSelect: (type: VerificationApplicantType) => void
}

const OPTIONS: {
    type: VerificationApplicantType
    icon: typeof User
    title: string
    description: string
}[] = [
    {
        type: 'individual',
        icon: User,
        title: 'Individual',
        description: 'You, as a person — an ordinary user, creator, artist, or public figure'
    },
    {
        type: 'business',
        icon: Building2,
        title: 'Business',
        description: 'A CIPC-registered company, or a sole trader'
    },
    {
        type: 'political_entity',
        icon: Landmark,
        title: 'Political party / Government entity',
        description: 'An official political or government organization'
    }
]

export function VerificationTypeSelect({ billingCycle, onBillingCycleChange, onSelect }: VerificationTypeSelectProps) {
    return (
        <div className='flex flex-col gap-3'>
            <BillingCycleToggle value={billingCycle} onChange={onBillingCycleChange} />

            <div>
                <h2 className='text-lg font-semibold text-foreground'>What are you verifying?</h2>
                <p className='text-sm text-muted-foreground'>This decides which identity check you&apos;ll complete.</p>
            </div>
            {OPTIONS.map(({ type, icon: Icon, title, description }) => (
                <button
                    key={type}
                    type='button'
                    onClick={() => onSelect(type)}
                    className='flex items-start gap-3 rounded-xl border border-border bg-card p-4 text-left transition-colors hover:border-brand hover:bg-accent'
                >
                    <span className='flex size-10 shrink-0 items-center justify-center rounded-lg bg-accent text-foreground'>
                        <Icon className='size-5' />
                    </span>
                    <span className='flex flex-col'>
                        <span className='font-medium text-foreground'>{title}</span>
                        <span className='text-sm text-muted-foreground'>{description}</span>
                    </span>
                </button>
            ))}
            <p className='mt-2 text-xs text-muted-foreground'>
                A payment hold is placed on your card to confirm a real, funded payment method — you&apos;re only
                charged if your application is approved, and it renews automatically on your chosen cycle after that.
            </p>
        </div>
    )
}
