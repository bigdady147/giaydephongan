export function formatVnd(amount: number): string {
  return `${new Intl.NumberFormat('vi-VN').format(amount)}₫`
}

const NEW_PRODUCT_WINDOW_MS = 14 * 24 * 60 * 60 * 1000

export function isNew(createdAt: string): boolean {
  const created = new Date(createdAt).getTime()
  if (Number.isNaN(created)) return false
  return Date.now() - created <= NEW_PRODUCT_WINDOW_MS
}
