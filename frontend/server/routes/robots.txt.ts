import { defineEventHandler } from 'h3'

export default defineEventHandler((event) => {
  const config = useRuntimeConfig()
  const siteUrl = config.public.siteUrl

  const text = `User-agent: *
Allow: /
Disallow: /admin/
Disallow: /login
Disallow: /register

Sitemap: ${siteUrl}/sitemap.xml
`

  event.node.res.setHeader('Content-Type', 'text/plain')
  return text
})
