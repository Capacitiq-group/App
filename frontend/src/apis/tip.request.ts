import httpClient from '@/apis/client'
import { BACKEND_API_ENDPOINT } from '@/constants/api/endpoints'
import { CreateTipReq, CreateTipRes } from '@/types/dtos/tip/tip-request.dto'

type ApiEnvelope<T> = { data: T; message: string }

const TipRequestApi = {
    create: (payload: CreateTipReq) => httpClient.post<ApiEnvelope<CreateTipRes>>(BACKEND_API_ENDPOINT.TIP.CREATE, payload)
}

export default TipRequestApi
