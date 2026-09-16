'use client'

import { cn } from '@/lib/utils'
import { VerificationBillingCycle } from '@/types/dtos/verification/verification-request.dto'

const MONTHLY_RAND = 99
const ANNUAL_RAND = 999
const ANNUAL_SAVING_RAND = MONTHLY_RAND * 12 - ANNUAL_RAND

type BillingCycleToggleProps = {
    value: VerificationBillingCycle
    onChange: (value: VerificationBillingCycle) => void
}

export function BillingCycleToggle({ value, onChange }: BillingCycleToggleProps) {
    return (
        <div className='flex flex-col gap-2'>
            <div className='flex rounded-lg border border-border p-1'>
                <button
                    type='button'
                    onClick={() => onChange('monthly')}
                    className={cn(
                        'flex-1 rounded-md py-2 text-sm font-medium transition-colors',
                        value === 'monthly' ? 'bg-brand text-white' : 'text-muted-foreground'
                    )}
                >
                    R{MONTHLY_RAND}/month
                </button>
                <button
                    type='button'
                    onClick={() => onChange('annual')}
                    className={cn(
                        'flex-1 rounded-md py-2 text-sm font-medium transition-colors',
                        value === 'annual' ? 'bg-brand text-white' : 'text-muted-foreground'
                    )}
                >
                    R{ANNUAL_RAND}/year
                </button>
            </div>
            {value === 'annual' && (
                <p className='text-center text-xs text-muted-foreground'>
                    Save R{ANNUAL_SAVING_RAND} a year compared to paying monthly
                </p>
            )}
        </div>
    )
}
