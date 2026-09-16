import { VerificationCallback } from './_components/verification-callback'
import { Metadata } from 'next'

export const metadata: Metadata = {
    title: 'Confirming Verification Payment'
}

export default function VerificationCallbackPage() {
    return <VerificationCallback />
}
