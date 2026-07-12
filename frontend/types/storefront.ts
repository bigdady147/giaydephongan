export interface CardProduct {
  id: number
  name: string
  slug: string
  thumbnail: string | null
  base_price: number
  sale_price: number | null
  created_at: string
}

export interface CategoryInfo {
  id: number
  name: string
  slug: string
  description: string | null
  seo_title: string | null
  seo_description: string | null
  thumbnail: string | null
  products_count?: number
}

export interface BrandInfo {
  id: number
  name: string
  slug: string
}

export interface VariantInfo {
  id: number
  size: string
  color: string
  stock_quantity: number
  price_override: number | null
}

export interface ProductImageInfo {
  id: number
  url: string
  sort_order: number
}

export interface ProductDetail extends CardProduct {
  description: string
  material: 'full_grain_leather' | 'suede' | 'pu_leather' | 'other'
  seo_title: string | null
  seo_description: string | null
  images: ProductImageInfo[]
  variants: VariantInfo[]
  category: CategoryInfo | null
  brand: BrandInfo | null
  related: CardProduct[]
}

export interface BannerInfo {
  id: number
  image: string
  link: string | null
  position: 'homepage_hero' | 'homepage_promo'
  sort_order: number
}

export interface BannersResponse {
  homepage_hero: BannerInfo[]
  homepage_promo: BannerInfo[]
}

export interface PageLink {
  title: string
  slug: string
}

export interface PageDetail extends PageLink {
  content: string
  seo_title: string | null
  seo_description: string | null
}

export type SettingsMap = Record<string, string | null>

export interface Paginated<T> {
  current_page: number
  data: T[]
  last_page: number
  per_page: number
  total: number
}

export interface AvailabilityInfo {
  base_price: number
  sale_price: number | null
  variants: VariantInfo[]
}

export type BlogPillar = 'cam-nang-chon-giay' | 'bao-quan-giay-da' | 'giay-theo-dip'

export interface BlogPostCard {
  id: number
  title: string
  slug: string
  excerpt: string | null
  thumbnail: string | null
  pillar: BlogPillar | null
  published_at: string | null
}

export interface BlogPostDetail extends BlogPostCard {
  content: string
  seo_title: string | null
  seo_description: string | null
  related: BlogPostCard[]
}
