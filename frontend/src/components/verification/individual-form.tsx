'use client'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import {
    IndividualDetailsSchema,
    IndividualDetailsValues,
    VERIFICATION_INDIVIDUAL_TYPES
} from '@/types/dtos/verification/verification-request.dto'
import { zodResolver } from '@hookform/resolvers/zod'
import { Controller, useForm } from 'react-hook-form'

const TYPE_LABEL: Record<(typeof VERIFICATION_INDIVIDUAL_TYPES)[number], string> = {
    ordinary_user: 'Just me (ordinary user)',
    creator: 'Creator',
    artist: 'Artist / Musician',
    public_figure: 'Public figure',
    other: 'Other'
}

type IndividualFormProps = {
    accountHandle: string
    isSubmitting: boolean
    onBack: () => void
    onSubmit: (values: IndividualDetailsValues) => void
}

export function IndividualForm({ accountHandle, isSubmitting, onBack, onSubmit }: IndividualFormProps) {
    const {
        register,
        handleSubmit,
        control,
        watch,
        formState: { errors }
    } = useForm<IndividualDetailsValues>({
        resolver: zodResolver(IndividualDetailsSchema),
        defaultValues: { verifying_as: 'ordinary_user' }
    })

    const verifyingAs = watch('verifying_as')

    return (
        <form className='flex flex-col gap-4' onSubmit={handleSubmit(onSubmit)}>
            <div className='rounded-lg bg-muted/50 px-3 py-2 text-sm text-muted-foreground'>
                Verifying <span className='font-medium text-foreground'>@{accountHandle}</span>
            </div>

            <div className='space-y-1.5'>
                <Label htmlFor='full_legal_name'>
                    Full legal name, exactly as it appears on your ID/passport
                    <span className='ml-1 text-destructive'>*</span>
                </Label>
                <Input id='full_legal_name' {...register('full_legal_name')} disabled={isSubmitting} />
                {errors.full_legal_name && <p className='text-sm text-destructive'>{errors.full_legal_name.message}</p>}
            </div>

            <div className='space-y-1.5'>
                <Label>
                    What are you verifying as?
                    <span className='ml-1 text-destructive'>*</span>
                </Label>
                <Controller
                    control={control}
                    name='verifying_as'
                    render={({ field }) => (
                        <Select value={field.value} onValueChange={field.onChange} disabled={isSubmitting}>
                            <SelectTrigger className='w-full'>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {VERIFICATION_INDIVIDUAL_TYPES.map((type) => (
                                    <SelectItem key={type} value={type}>
                                        {TYPE_LABEL[type]}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}
                />
            </div>

            {verifyingAs === 'other' && (
                <div className='space-y-1.5'>
                    <Label htmlFor='verifying_as_other'>Please specify</Label>
                    <Input id='verifying_as_other' {...register('verifying_as_other')} disabled={isSubmitting} />
                    {errors.verifying_as_other && (
                        <p className='text-sm text-destructive'>{errors.verifying_as_other.message}</p>
                    )}
                </div>
            )}

            {['creator', 'artist', 'public_figure'].includes(verifyingAs) && (
                <div className='space-y-1.5'>
                    <Label htmlFor='supporting_link'>
                        Link to an existing online presence (official website, another verified profile, press
                        mention)
                    </Label>
                    <Input
                        id='supporting_link'
                        placeholder='https://…'
                        {...register('supporting_link')}
                        disabled={isSubmitting}
                    />
                    <p className='text-xs text-muted-foreground'>
                        Your ID proves who you are — this is what supports the &quot;{TYPE_LABEL[verifyingAs as never]}
                        &quot; claim specifically.
                    </p>
                    {errors.supporting_link && <p className='text-sm text-destructive'>{errors.supporting_link.message}</p>}
                </div>
            )}

            <div className='space-y-1.5'>
                <Label htmlFor='id_issuing_country'>Country that issued your ID document</Label>
                <Input
                    id='id_issuing_country'
                    placeholder='e.g. ZA'
                    maxLength={2}
                    className='w-24 uppercase'
                    {...register('id_issuing_country')}
                    disabled={isSubmitting}
                />
                {errors.id_issuing_country && (
                    <p className='text-sm text-destructive'>{errors.id_issuing_country.message}</p>
                )}
            </div>

            <div className='mt-2 flex gap-2'>
                <Button type='button' variant='outline' onClick={onBack} disabled={isSubmitting}>
                    Back
                </Button>
                <Button type='submit' variant='brand' isLoading={isSubmitting} className='flex-1'>
                    Continue to payment
                </Button>
            </div>
        </form>
    )
}
