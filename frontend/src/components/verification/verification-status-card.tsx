import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { VerificationApplicationRes } from '@/types/dtos/verification/verification-response.dto'
import { CheckCircle2, Clock, ExternalLink, ShieldAlert, XCircle } from 'lucide-react'

type VerificationStatusCardProps = {
    application: VerificationApplicationRes
    onContinueToDidit: () => void
    onStartOver: () => void
}

const STATUS_CONFIG = {
    in_progress: {
        icon: Clock,
        badge: 'outline' as const,
        title: 'Verification in progress',
        description: 'Your payment hold is placed — complete the identity check to continue.'
    },
    under_review: {
        icon: Clock,
        badge: 'warning' as const,
        title: 'Under review',
        description: 'A staff member is reviewing your application. This can take a few days.'
    },
    verified: {
        icon: CheckCircle2,
        badge: 'success' as const,
        title: "You're verified",
        description: 'Your verification badge is now showing on your profile.'
    },
    failed: {
        icon: XCircle,
        badge: 'destructive' as const,
        title: 'Verification failed',
        description: 'Your payment hold was released — nothing was charged.'
    },
    extra_review_needed: {
        icon: ShieldAlert,
        badge: 'warning' as const,
        title: 'Extra review needed',
        description: "Your payment hold expired before a decision was reached — you'll need to submit payment again."
    }
} as const

export function VerificationStatusCard({ application, onContinueToDidit, onStartOver }: VerificationStatusCardProps) {
    const config = STATUS_CONFIG[application.status]
    const Icon = config.icon

    return (
        <div className='flex flex-col gap-4 rounded-xl border border-border bg-card p-5'>
            <div className='flex items-center gap-3'>
                <span className='flex size-10 items-center justify-center rounded-full bg-accent'>
                    <Icon className='size-5' />
                </span>
                <div>
                    <div className='flex items-center gap-2'>
                        <h3 className='font-semibold text-foreground'>{config.title}</h3>
                        <Badge variant={config.badge}>{application.applicant_type.replace('_', ' ')}</Badge>
                    </div>
                    <p className='text-sm text-muted-foreground'>{config.description}</p>
                    {application.status === 'verified' && (
                        <p className='mt-1 text-xs text-muted-foreground'>
                            {application.billing_cycle === 'monthly'
                                ? `R${(application.fee_cents / 100).toFixed(0)}/month`
                                : `R${(application.fee_cents / 100).toFixed(0)}/year`}{' '}
                            — renews automatically
                        </p>
                    )}
                </div>
            </div>

            {application.status === 'failed' && application.decision_reason && (
                <p className='rounded-lg bg-muted/50 px-3 py-2 text-sm text-muted-foreground'>
                    {application.decision_reason}
                </p>
            )}

            {application.status === 'in_progress' && application.didit_url && (
                <Button variant='brand' onClick={onContinueToDidit}>
                    Continue identity verification
                    <ExternalLink />
                </Button>
            )}

            {(application.status === 'failed' || application.status === 'extra_review_needed') && (
                <Button variant='outline' onClick={onStartOver}>
                    Start a new application
                </Button>
            )}
        </div>
    )
}
