export type VerificationStatus = 'in_progress' | 'under_review' | 'verified' | 'failed' | 'extra_review_needed'

export type VerificationApplicationRes = {
    uuid: string
    applicant_type: 'individual' | 'business' | 'political_entity'
    billing_cycle: 'monthly' | 'annual'
    fee_cents: number
    status: VerificationStatus
    decision_reason?: string
    hold_expires_at: string | null
    submitted_at: string
    decided_at: string | null
    didit_url?: string
}

export type SubmitVerificationApplicationRes = {
    application: VerificationApplicationRes
    paystack_authorization_url: string
}
