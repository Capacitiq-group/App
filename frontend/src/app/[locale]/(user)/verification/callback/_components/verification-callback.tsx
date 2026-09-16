'use client'

import VerificationRequestApi from '@/apis/verification.request'
import Link from 'next/link'
import { useEffect, useState } from 'react'

export function VerificationCallback() {
    const [errorMessage, setErrorMessage] = useState<string | null>(null)

    useEffect(() => {
        VerificationRequestApi.status()
            .then((res) => {
                if (res.data?.didit_url) {
                    window.location.href = res.data.didit_url
                    return
                }
                setErrorMessage('Payment confirmed, but the identity verification link is not ready yet.')
            })
            .catch(() => setErrorMessage("Couldn't confirm your payment status."))
    }, [])

    return (
        <div className='mx-auto flex max-w-md flex-col items-center gap-3 px-4 py-16 text-center'>
            {errorMessage ? (
                <>
                    <p className='text-sm text-foreground'>{errorMessage}</p>
                    <Link href='/verification' className='text-sm font-medium text-brand underline'>
                        Back to verification
                    </Link>
                </>
            ) : (
                <p className='text-sm text-muted-foreground'>Confirming your payment…</p>
            )}
        </div>
    )
}
