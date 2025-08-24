export default defineNuxtRouteMiddleware((to, from) => {
  // Mock authentication check
  const isAuthenticated = true // Trong thực tế, kiểm tra từ localStorage, cookie, hoặc store
  
  // Routes that require authentication
  const protectedRoutes = ['/admin', '/client']
  
  // Check if the route requires authentication
  const requiresAuth = protectedRoutes.some(route => to.path.startsWith(route))
  
  if (requiresAuth && !isAuthenticated) {
    // Redirect to login page
    return navigateTo('/login')
  }
  
  // Check admin routes specifically
  if (to.path.startsWith('/admin')) {
    const isAdmin = true // Trong thực tế, kiểm tra role từ user data
    
    if (!isAdmin) {
      // Redirect to unauthorized page or show error
      return navigateTo('/unauthorized')
    }
  }
})
