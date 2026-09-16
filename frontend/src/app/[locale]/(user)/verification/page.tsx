import { VerificationFlow } from '@/components/verification/verification-flow'
import { Metadata } from 'next'

export const metadata: Metadata = {
    title: 'Get Verified',
    description: 'Apply for a verification badge'
}

export default function VerificationPage() {
    return (
        <div className='mx-auto max-w-lg px-4 py-10'>
            <VerificationFlow />
        </div>
    )
}
