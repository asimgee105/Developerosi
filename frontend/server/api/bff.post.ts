import { defineEventHandler, readBody, getHeader, setResponseStatus } from 'h3'

export default defineEventHandler(async (event) => {
  const body = await readBody(event)
  const cookieHeader = getHeader(event, 'cookie')
  const authHeader = getHeader(event, 'authorization')

  try {
    // Translate client request and proxy to Laravel backend securely
    const response = await $fetch.raw('http://localhost:8000/api/v1/billing/subscriptions/checkout', {
      method: 'POST',
      body,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Cookie': cookieHeader || '',
        'Authorization': authHeader || '',
      }
    })

    // Forward backend response headers (like Set-Cookie) statefully to the browser
    const responseCookies = response.headers.get('set-cookie')
    if (responseCookies) {
      event.node.res.setHeader('set-cookie', responseCookies)
    }

    setResponseStatus(event, response.status)
    return response._data
  } catch (err: any) {
    setResponseStatus(event, err.status || 500)
    return {
      message: 'BFF Proxy Error: Failed to secure authenticate token proxy.',
      error: err.message,
    }
  }
})
