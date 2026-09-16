import httpClient from '@/apis/client'
import { BACKEND_API_ENDPOINT } from '@/constants/api/endpoints'
import { CreateVerificationApplicationReq } from '@/types/dtos/verification/verification-request.dto'
import {
    SubmitVerificationApplicationRes,
    VerificationApplicationRes
} from '@/types/dtos/verification/verification-response.dto'

type ApiEnvelope<T> = { data: T; message: string }

const VerificationRequestApi = {
    submit: (payload: CreateVerificationApplicationReq) =>
        httpClient.post<ApiEnvelope<SubmitVerificationApplicationRes>>(BACKEND_API_ENDPOINT.VERIFICATION.SUBMIT, payload),

    status: () =>
        httpClient.get<ApiEnvelope<VerificationApplicationRes | null>>(BACKEND_API_ENDPOINT.VERIFICATION.STATUS)
}

export default VerificationRequestApi
