import { defineEventHandler } from 'h3'

export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()
  const siteUrl = config.public.siteUrl
  const apiBase = config.public.apiBase

  let routes: string[] = ['/', '/cam-nang']

  try {
    const data = await $fetch<{ products: string[]; categories: string[]; pages: string[]; blog: string[] }>(`${apiBase}/slugs`)
    if (data.products) data.products.forEach(slug => routes.push(`/san-pham/${slug}`))
    if (data.categories) data.categories.forEach(slug => routes.push(`/danh-muc/${slug}`))
    if (data.pages) data.pages.forEach(slug => routes.push(`/${slug}`))
    if (data.blog) data.blog.forEach(slug => routes.push(`/cam-nang/${slug}`))
  } catch (e) {
    console.error('Sitemap generation failed to fetch slugs', e)
  }

  const xml = `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  ${routes.map(r => `
  <url>
    <loc>${siteUrl}${r}</loc>
    <changefreq>daily</changefreq>
    <priority>${r === '/' ? '1.0' : '0.8'}</priority>
  </url>`).join('')}
</urlset>`

  event.node.res.setHeader('Content-Type', 'application/xml')
  return xml
})
