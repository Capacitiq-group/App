'use client'

import VerificationRequestApi from '@/apis/verification.request'
import { BusinessForm } from '@/components/verification/business-form'
import { IndividualForm } from '@/components/verification/individual-form'
import { PoliticalForm } from '@/components/verification/political-form'
import { VerificationStatusCard } from '@/components/verification/verification-status-card'
import { VerificationTypeSelect } from '@/components/verification/verification-type-select'
import useCurrentUserData from '@/hooks/data/useCurrentUserData'
import {
    BusinessDetailsValues,
    IndividualDetailsValues,
    PoliticalDetailsValues,
    VerificationApplicantType,
    VerificationBillingCycle
} from '@/types/dtos/verification/verification-request.dto'
import { VerificationApplicationRes } from '@/types/dtos/verification/verification-response.dto'
import { useEffect, useState } from 'react'
import { toast } from 'sonner'

type Step = 'loading' | 'status' | 'select-type' | 'form'

export function VerificationFlow() {
    const currentUser = useCurrentUserData()
    const [step, setStep] = useState<Step>('loading')
    const [applicantType, setApplicantType] = useState<VerificationApplicantType | null>(null)
    const [billingCycle, setBillingCycle] = useState<VerificationBillingCycle>('monthly')
    const [application, setApplication] = useState<VerificationApplicationRes | null>(null)
    const [isSubmitting, setIsSubmitting] = useState(false)

    useEffect(() => {
        VerificationRequestApi.status()
            .then((res) => {
                if (res.data && res.data.status !== 'failed' && res.data.status !== 'extra_review_needed') {
                    setApplication(res.data)
                    setStep('status')
                } else {
                    setApplication(res.data)
                    setStep(res.data ? 'status' : 'select-type')
                }
            })
            .catch(() => setStep('select-type'))
    }, [])

    const startNewApplication = () => {
        setApplication(null)
        setStep('select-type')
    }

    const handleSubmitDetails = async (details: IndividualDetailsValues | BusinessDetailsValues | PoliticalDetailsValues) => {
        if (!applicantType) return

        setIsSubmitting(true)
        try {
            const callbackUrl = `${window.location.origin}/verification/callback`
            const res = await VerificationRequestApi.submit({
                applicant_type: applicantType,
                billing_cycle: billingCycle,
                callback_url: callbackUrl,
                ...details
            })
            // Paystack first — card entry, places the hold. Didit comes next,
            // after Paystack redirects back to callback_url.
            window.location.href = res.data.paystack_authorization_url
        } catch {
            toast.error("Couldn't submit your application — please try again")
            setIsSubmitting(false)
        }
    }

    if (step === 'loading') {
        return <div className='py-12 text-center text-sm text-muted-foreground'>Loading…</div>
    }

    if (step === 'status' && application) {
        return (
            <VerificationStatusCard
                application={application}
                onContinueToDidit={() => {
                    if (application.didit_url) window.location.href = application.didit_url
                }}
                onStartOver={startNewApplication}
            />
        )
    }

    if (step === 'form' && applicantType === 'individual') {
        return (
            <IndividualForm
                accountHandle={currentUser?.username ?? ''}
                isSubmitting={isSubmitting}
                onBack={() => setStep('select-type')}
                onSubmit={handleSubmitDetails}
            />
        )
    }

    if (step === 'form' && applicantType === 'business') {
        return <BusinessForm isSubmitting={isSubmitting} onBack={() => setStep('select-type')} onSubmit={handleSubmitDetails} />
    }

    if (step === 'form' && applicantType === 'political_entity') {
        return <PoliticalForm isSubmitting={isSubmitting} onBack={() => setStep('select-type')} onSubmit={handleSubmitDetails} />
    }

    return (
        <VerificationTypeSelect
            billingCycle={billingCycle}
            onBillingCycleChange={setBillingCycle}
            onSelect={(type) => {
                setApplicantType(type)
                setStep('form')
            }}
        />
    )
}
