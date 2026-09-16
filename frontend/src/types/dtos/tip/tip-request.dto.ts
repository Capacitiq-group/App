export const TIP_SOURCE_TYPES = ['profile', 'post', 'space'] as const
export type TipSourceType = (typeof TIP_SOURCE_TYPES)[number]

export type CreateTipReq = {
    recipient_user_uuid: string
    amount_cents: number
    source_type: TipSourceType
    source_uuid?: string
    message?: string
    callback_url?: string
}

export type TipRes = {
    uuid: string
    source_type: TipSourceType
    amount_cents: number
    platform_fee_cents: number
    recipient_amount_cents: number
    status: 'pending' | 'completed' | 'failed'
    message: string | null
    created_at: string
}

export type CreateTipRes = {
    tip: TipRes
    paystack_authorization_url: string
}
