'use client'

import TipRequestApi from '@/apis/tip.request'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { cn } from '@/lib/utils'
import { TipSourceType } from '@/types/dtos/tip/tip-request.dto'
import { Heart } from 'lucide-react'
import { useState } from 'react'
import { toast } from 'sonner'

const PRESET_RAND = [20, 50, 100, 250]
const MIN_RAND = 20

type TipDialogProps = {
    open: boolean
    onOpenChange: (open: boolean) => void
    recipientUuid: string
    recipientUsername: string
    recipientAvatarUrl?: string | null
    sourceType: TipSourceType
    sourceUuid?: string
}

export function TipDialog({
    open,
    onOpenChange,
    recipientUuid,
    recipientUsername,
    recipientAvatarUrl,
    sourceType,
    sourceUuid
}: TipDialogProps) {
    const [amountRand, setAmountRand] = useState<number>(PRESET_RAND[0])
    const [customAmount, setCustomAmount] = useState('')
    const [message, setMessage] = useState('')
    const [isSubmitting, setIsSubmitting] = useState(false)

    const effectiveAmountRand = customAmount ? Number(customAmount) : amountRand
    const isValidAmount = Number.isFinite(effectiveAmountRand) && effectiveAmountRand >= MIN_RAND

    const handleSend = async () => {
        if (!isValidAmount) return

        setIsSubmitting(true)
        try {
            const res = await TipRequestApi.create({
                recipient_user_uuid: recipientUuid,
                amount_cents: Math.round(effectiveAmountRand * 100),
                source_type: sourceType,
                source_uuid: sourceUuid,
                message: message || undefined,
                callback_url: `${window.location.origin}/tips/callback`
            })
            window.location.href = res.data.paystack_authorization_url
        } catch {
            toast.error("Couldn't start this tip — please try again")
            setIsSubmitting(false)
        }
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className='sm:max-w-sm'>
                <DialogHeader>
                    <DialogTitle>Send a tip</DialogTitle>
                </DialogHeader>

                <div className='flex items-center gap-2.5'>
                    <Avatar className='size-9'>
                        <AvatarImage src={recipientAvatarUrl ?? undefined} alt={recipientUsername} />
                        <AvatarFallback>{recipientUsername.slice(0, 2).toUpperCase()}</AvatarFallback>
                    </Avatar>
                    <span className='text-sm text-muted-foreground'>
                        to <span className='font-medium text-foreground'>@{recipientUsername}</span>
                    </span>
                </div>

                <div className='grid grid-cols-4 gap-2'>
                    {PRESET_RAND.map((rand) => (
                        <button
                            key={rand}
                            type='button'
                            onClick={() => {
                                setAmountRand(rand)
                                setCustomAmount('')
                            }}
                            className={cn(
                                'rounded-lg border py-2 text-sm font-medium transition-colors',
                                !customAmount && amountRand === rand
                                    ? 'border-brand bg-brand text-white'
                                    : 'border-border text-foreground hover:border-brand'
                            )}
                        >
                            R{rand}
                        </button>
                    ))}
                </div>

                <div className='space-y-1.5'>
                    <Label htmlFor='custom_amount'>Or enter an amount (min R{MIN_RAND})</Label>
                    <Input
                        id='custom_amount'
                        type='number'
                        min={MIN_RAND}
                        placeholder={`R${MIN_RAND}+`}
                        value={customAmount}
                        onChange={(e) => setCustomAmount(e.target.value)}
                    />
                </div>

                <div className='space-y-1.5'>
                    <Label htmlFor='tip_message'>Add a message (optional)</Label>
                    <Input
                        id='tip_message'
                        maxLength={280}
                        value={message}
                        onChange={(e) => setMessage(e.target.value)}
                    />
                </div>

                <DialogFooter>
                    <Button
                        variant='brand'
                        className='w-full'
                        disabled={!isValidAmount}
                        isLoading={isSubmitting}
                        onClick={handleSend}
                    >
                        <Heart />
                        Send R{isValidAmount ? effectiveAmountRand : MIN_RAND} tip
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    )
}
