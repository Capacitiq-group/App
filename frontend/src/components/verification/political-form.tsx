'use client'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import {
    PoliticalDetailsSchema,
    PoliticalDetailsValues,
    VERIFICATION_POLITICAL_ENTITY_TYPES
} from '@/types/dtos/verification/verification-request.dto'
import { zodResolver } from '@hookform/resolvers/zod'
import { Controller, useForm } from 'react-hook-form'

const ENTITY_TYPE_LABEL: Record<(typeof VERIFICATION_POLITICAL_ENTITY_TYPES)[number], string> = {
    political_party: 'Political party',
    government_department: 'Government department / ministry',
    state_owned_entity: 'State-owned entity',
    other: 'Other'
}

type PoliticalFormProps = {
    isSubmitting: boolean
    onBack: () => void
    onSubmit: (values: PoliticalDetailsValues) => void
}

export function PoliticalForm({ isSubmitting, onBack, onSubmit }: PoliticalFormProps) {
    const {
        register,
        handleSubmit,
        control,
        watch,
        formState: { errors }
    } = useForm<PoliticalDetailsValues>({
        resolver: zodResolver(PoliticalDetailsSchema),
        defaultValues: { entity_type: 'political_party' }
    })

    const entityType = watch('entity_type')

    return (
        <form className='flex flex-col gap-4' onSubmit={handleSubmit(onSubmit)}>
            <div className='space-y-1.5'>
                <Label htmlFor='official_entity_name'>
                    Official entity name
                    <span className='ml-1 text-destructive'>*</span>
                </Label>
                <Input id='official_entity_name' {...register('official_entity_name')} disabled={isSubmitting} />
                {errors.official_entity_name && (
                    <p className='text-sm text-destructive'>{errors.official_entity_name.message}</p>
                )}
            </div>

            <div className='space-y-1.5'>
                <Label>
                    Entity type
                    <span className='ml-1 text-destructive'>*</span>
                </Label>
                <Controller
                    control={control}
                    name='entity_type'
                    render={({ field }) => (
                        <Select value={field.value} onValueChange={field.onChange} disabled={isSubmitting}>
                            <SelectTrigger className='w-full'>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {VERIFICATION_POLITICAL_ENTITY_TYPES.map((type) => (
                                    <SelectItem key={type} value={type}>
                                        {ENTITY_TYPE_LABEL[type]}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}
                />
            </div>

            {entityType === 'other' && (
                <div className='space-y-1.5'>
                    <Label htmlFor='entity_type_other'>Please specify</Label>
                    <Input id='entity_type_other' {...register('entity_type_other')} disabled={isSubmitting} />
                    {errors.entity_type_other && (
                        <p className='text-sm text-destructive'>{errors.entity_type_other.message}</p>
                    )}
                </div>
            )}

            <div className='space-y-1.5'>
                <Label htmlFor='registration_or_gazette_reference'>
                    Registration or government gazette reference
                </Label>
                <Input
                    id='registration_or_gazette_reference'
                    placeholder='Not applicable if none'
                    {...register('registration_or_gazette_reference')}
                    disabled={isSubmitting}
                />
            </div>

            <div className='space-y-1.5'>
                <Label htmlFor='representative_full_name'>
                    Your full legal name
                    <span className='ml-1 text-destructive'>*</span>
                </Label>
                <Input id='representative_full_name' {...register('representative_full_name')} disabled={isSubmitting} />
                {errors.representative_full_name && (
                    <p className='text-sm text-destructive'>{errors.representative_full_name.message}</p>
                )}
            </div>

            <div className='space-y-1.5'>
                <Label htmlFor='representative_role_title'>
                    Your official role / title
                    <span className='ml-1 text-destructive'>*</span>
                </Label>
                <Input id='representative_role_title' {...register('representative_role_title')} disabled={isSubmitting} />
                {errors.representative_role_title && (
                    <p className='text-sm text-destructive'>{errors.representative_role_title.message}</p>
                )}
            </div>

            <p className='rounded-lg bg-muted/50 px-3 py-2 text-xs text-muted-foreground'>
                Political and government entities don&apos;t have a CIPC-style registry to automatically cross-check
                against — a staff member confirms your authority through the entity&apos;s own official channel
                before this is approved, so expect this one to sit in review rather than resolve instantly.
            </p>

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
