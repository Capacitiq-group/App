'use client'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Switch } from '@/components/ui/switch'
import {
    BusinessDetailsSchema,
    BusinessDetailsValues,
    VERIFICATION_BUSINESS_ROLES
} from '@/types/dtos/verification/verification-request.dto'
import { zodResolver } from '@hookform/resolvers/zod'
import { Controller, useForm } from 'react-hook-form'

const ROLE_LABEL: Record<(typeof VERIFICATION_BUSINESS_ROLES)[number], string> = {
    director_owner: 'Director / Owner',
    officer_executive: 'Officer / Executive',
    authorized_employee: 'Authorized Employee',
    sole_trader_owner: 'Sole Trader (owner)',
    other: 'Other'
}

type BusinessFormProps = {
    isSubmitting: boolean
    onBack: () => void
    onSubmit: (values: BusinessDetailsValues) => void
}

export function BusinessForm({ isSubmitting, onBack, onSubmit }: BusinessFormProps) {
    const {
        register,
        handleSubmit,
        control,
        watch,
        formState: { errors }
    } = useForm<BusinessDetailsValues>({
        resolver: zodResolver(BusinessDetailsSchema),
        defaultValues: { is_cipc_registered: true, representative_role: 'director_owner' }
    })

    const isCipcRegistered = watch('is_cipc_registered')
    const representativeRole = watch('representative_role')

    return (
        <form className='flex flex-col gap-4' onSubmit={handleSubmit(onSubmit)}>
            <div className='space-y-1.5'>
                <Label htmlFor='legal_entity_name'>
                    Legal entity name
                    <span className='ml-1 text-destructive'>*</span>
                </Label>
                <Input id='legal_entity_name' {...register('legal_entity_name')} disabled={isSubmitting} />
                {errors.legal_entity_name && (
                    <p className='text-sm text-destructive'>{errors.legal_entity_name.message}</p>
                )}
            </div>

            <div className='flex items-center justify-between rounded-lg border border-border px-3 py-2.5'>
                <div>
                    <Label htmlFor='is_cipc_registered'>CIPC-registered company</Label>
                    <p className='text-xs text-muted-foreground'>Off if you&apos;re a sole trader</p>
                </div>
                <Controller
                    control={control}
                    name='is_cipc_registered'
                    render={({ field }) => (
                        <Switch
                            id='is_cipc_registered'
                            checked={field.value}
                            onCheckedChange={field.onChange}
                            disabled={isSubmitting}
                        />
                    )}
                />
            </div>

            {isCipcRegistered ? (
                <div className='grid grid-cols-2 gap-3'>
                    <div className='space-y-1.5'>
                        <Label htmlFor='registration_number'>
                            CIPC registration number
                            <span className='ml-1 text-destructive'>*</span>
                        </Label>
                        <Input id='registration_number' {...register('registration_number')} disabled={isSubmitting} />
                        {errors.registration_number && (
                            <p className='text-sm text-destructive'>{errors.registration_number.message}</p>
                        )}
                    </div>
                    <div className='space-y-1.5'>
                        <Label htmlFor='registration_country'>Country</Label>
                        <Input
                            id='registration_country'
                            placeholder='ZA'
                            maxLength={2}
                            className='uppercase'
                            {...register('registration_country')}
                            disabled={isSubmitting}
                        />
                    </div>
                </div>
            ) : (
                <div className='flex flex-col gap-3 rounded-lg border border-dashed border-border p-3'>
                    <p className='text-xs text-muted-foreground'>
                        Optional — strengthens your application, not required to pass.
                    </p>
                    <div className='space-y-1.5'>
                        <Label htmlFor='sole_trader_trading_name'>Trading name</Label>
                        <Input id='sole_trader_trading_name' {...register('sole_trader_trading_name')} disabled={isSubmitting} />
                    </div>
                    <div className='space-y-1.5'>
                        <Label htmlFor='sole_trader_tax_vat_number'>Tax / VAT number</Label>
                        <Input id='sole_trader_tax_vat_number' {...register('sole_trader_tax_vat_number')} disabled={isSubmitting} />
                    </div>
                    <div className='space-y-1.5'>
                        <Label htmlFor='sole_trader_address_or_bank'>Business address or bank details</Label>
                        <Input id='sole_trader_address_or_bank' {...register('sole_trader_address_or_bank')} disabled={isSubmitting} />
                    </div>
                </div>
            )}

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
                <Label>
                    Your role
                    <span className='ml-1 text-destructive'>*</span>
                </Label>
                <Controller
                    control={control}
                    name='representative_role'
                    render={({ field }) => (
                        <Select value={field.value} onValueChange={field.onChange} disabled={isSubmitting}>
                            <SelectTrigger className='w-full'>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {VERIFICATION_BUSINESS_ROLES.map((role) => (
                                    <SelectItem key={role} value={role}>
                                        {ROLE_LABEL[role]}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    )}
                />
            </div>

            {representativeRole === 'other' && (
                <div className='space-y-1.5'>
                    <Label htmlFor='representative_role_other'>Please specify</Label>
                    <Input id='representative_role_other' {...register('representative_role_other')} disabled={isSubmitting} />
                    {errors.representative_role_other && (
                        <p className='text-sm text-destructive'>{errors.representative_role_other.message}</p>
                    )}
                </div>
            )}

            {isCipcRegistered && (
                <p className='rounded-lg bg-muted/50 px-3 py-2 text-xs text-muted-foreground'>
                    If this is a CIPC-registered company, the identity check will confirm you against the
                    company&apos;s real, registered directors/officers — account control only transfers if that
                    match succeeds, not automatically to whoever fills out this form.
                </p>
            )}

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
