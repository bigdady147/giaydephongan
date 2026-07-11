export default defineNuxtRouteMiddleware((to) => {
  if (import.meta.server) return

  const { isAuthenticated, isStaff } = useAuth()

  const isAdminRoute = to.path.startsWith('/admin')
  const isGuestOnlyRoute = to.path === '/login' || to.path === '/register'

  if (isGuestOnlyRoute && isAuthenticated.value) {
    return navigateTo(isStaff.value ? '/admin' : '/')
  }

  if (isAdminRoute) {
    if (!isAuthenticated.value) {
      return navigateTo('/login')
    }
    if (!isStaff.value) {
      return navigateTo('/')
    }
  }
})
