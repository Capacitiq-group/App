import z from 'zod'

const configSchema = z.object({
    NEXT_PUBLIC_API_ENDPOINT: z.string().url(),
    NEXT_PUBLIC_URL: z.string().url(),
    // Google sign-in is optional: a blank value simply disables it instead of failing the build.
    NEXT_PUBLIC_GOOGLE_AUTHORIZED_REDIRECT_URI: z.string().url().or(z.literal('')).default(''),
    NEXT_PUBLIC_GOOGLE_CLIENT_ID: z.string().default(''),
    NEXT_APP_ENV: z.enum(['development', 'production', 'test']).default('production'),
    NEXT_PUBLIC_APP_NAME: z.string().min(2).max(100),
    NEXT_PUBLIC_COMPANY_NAME: z.string().min(2).max(100),
    NEXT_PUBLIC_CONTACT_EMAIL: z.string().email(),
    NEXT_PUBLIC_JURISDICTION: z.string().min(2).max(100),
    NEXT_PUBLIC_REVERB_APP_KEY: z.string().min(1),
    NEXT_PUBLIC_REVERB_HOST: z.string().min(1),
    NEXT_PUBLIC_REVERB_PORT: z.coerce.number().int().positive(),
    NEXT_PUBLIC_REVERB_SCHEME: z.enum(['http', 'https'])
})

const config = configSchema.safeParse({
    NEXT_PUBLIC_API_ENDPOINT: process.env.NEXT_PUBLIC_API_ENDPOINT,
    NEXT_PUBLIC_URL: process.env.NEXT_PUBLIC_URL,
    NEXT_PUBLIC_GOOGLE_AUTHORIZED_REDIRECT_URI: process.env.NEXT_PUBLIC_GOOGLE_AUTHORIZED_REDIRECT_URI,
    NEXT_PUBLIC_GOOGLE_CLIENT_ID: process.env.NEXT_PUBLIC_GOOGLE_CLIENT_ID,
    NEXT_APP_ENV: process.env.NEXT_APP_ENV,
    NEXT_PUBLIC_APP_NAME: process.env.NEXT_PUBLIC_APP_NAME,
    NEXT_PUBLIC_COMPANY_NAME: process.env.NEXT_PUBLIC_COMPANY_NAME,
    NEXT_PUBLIC_CONTACT_EMAIL: process.env.NEXT_PUBLIC_CONTACT_EMAIL,
    NEXT_PUBLIC_JURISDICTION: process.env.NEXT_PUBLIC_JURISDICTION,
    NEXT_PUBLIC_REVERB_APP_KEY: process.env.NEXT_PUBLIC_REVERB_APP_KEY,
    NEXT_PUBLIC_REVERB_HOST: process.env.NEXT_PUBLIC_REVERB_HOST,
    NEXT_PUBLIC_REVERB_PORT: process.env.NEXT_PUBLIC_REVERB_PORT,
    NEXT_PUBLIC_REVERB_SCHEME: process.env.NEXT_PUBLIC_REVERB_SCHEME
})

if (!config.success) {
    console.error('Invalid environment variables:', config.error.format())
    throw new Error('Invalid environment variables')
}

const envConfig = config.data

export const isProduction = envConfig.NEXT_APP_ENV === 'production'
export const isGoogleAuthEnabled =
    envConfig.NEXT_PUBLIC_GOOGLE_CLIENT_ID !== '' && envConfig.NEXT_PUBLIC_GOOGLE_AUTHORIZED_REDIRECT_URI !== ''
export default envConfig
