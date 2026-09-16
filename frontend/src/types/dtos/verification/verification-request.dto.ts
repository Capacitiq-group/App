import { z } from 'zod'

export const VERIFICATION_APPLICANT_TYPES = ['individual', 'business', 'political_entity'] as const
export type VerificationApplicantType = (typeof VERIFICATION_APPLICANT_TYPES)[number]

export const VERIFICATION_BILLING_CYCLES = ['monthly', 'annual'] as const
export type VerificationBillingCycle = (typeof VERIFICATION_BILLING_CYCLES)[number]

export const VERIFICATION_INDIVIDUAL_TYPES = ['ordinary_user', 'creator', 'artist', 'public_figure', 'other'] as const
export const VERIFICATION_BUSINESS_ROLES = [
    'director_owner',
    'officer_executive',
    'authorized_employee',
    'sole_trader_owner',
    'other'
] as const
export const VERIFICATION_POLITICAL_ENTITY_TYPES = [
    'political_party',
    'government_department',
    'state_owned_entity',
    'other'
] as const

export const IndividualDetailsSchema = z
    .object({
        full_legal_name: z.string().min(2, 'Enter your full legal name as it appears on your ID').max(150),
        verifying_as: z.enum(VERIFICATION_INDIVIDUAL_TYPES),
        verifying_as_other: z.string().max(100).optional(),
        supporting_link: z.string().url('Enter a valid URL').max(500).optional().or(z.literal('')),
        id_issuing_country: z.string().length(2, 'Use a 2-letter country code').optional().or(z.literal(''))
    })
    .superRefine((data, ctx) => {
        if (data.verifying_as === 'other' && !data.verifying_as_other) {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                path: ['verifying_as_other'],
                message: 'Please specify'
            })
        }
    })

export type IndividualDetailsValues = z.infer<typeof IndividualDetailsSchema>

export const BusinessDetailsSchema = z
    .object({
        legal_entity_name: z.string().min(2).max(200),
        is_cipc_registered: z.boolean(),
        registration_number: z.string().max(50).optional(),
        registration_country: z.string().length(2).optional().or(z.literal('')),
        representative_full_name: z.string().min(2).max(150),
        representative_role: z.enum(VERIFICATION_BUSINESS_ROLES),
        representative_role_other: z.string().max(100).optional(),
        sole_trader_trading_name: z.string().max(200).optional(),
        sole_trader_tax_vat_number: z.string().max(50).optional(),
        sole_trader_address_or_bank: z.string().max(255).optional()
    })
    .superRefine((data, ctx) => {
        if (data.is_cipc_registered && !data.registration_number) {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                path: ['registration_number'],
                message: 'Required for a CIPC-registered company'
            })
        }
        if (data.representative_role === 'other' && !data.representative_role_other) {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                path: ['representative_role_other'],
                message: 'Please specify'
            })
        }
    })

export type BusinessDetailsValues = z.infer<typeof BusinessDetailsSchema>

export const PoliticalDetailsSchema = z
    .object({
        official_entity_name: z.string().min(2).max(200),
        entity_type: z.enum(VERIFICATION_POLITICAL_ENTITY_TYPES),
        entity_type_other: z.string().max(100).optional(),
        registration_or_gazette_reference: z.string().max(200).optional(),
        representative_full_name: z.string().min(2).max(150),
        representative_role_title: z.string().min(2).max(100)
    })
    .superRefine((data, ctx) => {
        if (data.entity_type === 'other' && !data.entity_type_other) {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                path: ['entity_type_other'],
                message: 'Please specify'
            })
        }
    })

export type PoliticalDetailsValues = z.infer<typeof PoliticalDetailsSchema>

export type CreateVerificationApplicationReq = {
    applicant_type: VerificationApplicantType
    billing_cycle: VerificationBillingCycle
    callback_url?: string
} & (IndividualDetailsValues | BusinessDetailsValues | PoliticalDetailsValues)
