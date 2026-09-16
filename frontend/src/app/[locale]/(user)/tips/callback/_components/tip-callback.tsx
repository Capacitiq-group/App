'use client'

import { Heart } from 'lucide-react'
import Link from 'next/link'

export function TipCallback() {
    return (
        <div className='mx-auto flex max-w-md flex-col items-center gap-3 px-4 py-16 text-center'>
            <span className='flex size-12 items-center justify-center rounded-full bg-accent'>
                <Heart className='size-6 text-brand' />
            </span>
            <p className='text-sm text-foreground'>
                Payment received — your tip is on its way. It can take a minute to confirm.
            </p>
            <Link href='/' className='text-sm font-medium text-brand underline'>
                Back home
            </Link>
        </div>
    )
}
