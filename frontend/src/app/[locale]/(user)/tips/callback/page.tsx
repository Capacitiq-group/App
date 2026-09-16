import { TipCallback } from './_components/tip-callback'
import { Metadata } from 'next'

export const metadata: Metadata = {
    title: 'Tip Sent'
}

export default function TipCallbackPage() {
    return <TipCallback />
}
