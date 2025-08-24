# Nuxt Routing Guide - Giày dép Hồng An

## Tổng quan

Dự án này sử dụng **Nuxt 4 File-based Routing** để quản lý navigation và routing. Không cần cấu hình Vue Router thủ công.

## Cấu trúc Routing

### 1. File-based Routing

```
frontend/pages/
├── index.vue              → / (Trang chủ)
├── login.vue              → /login (Đăng nhập)
├── client/
│   ├── index.vue          → /client (Dashboard client)
│   ├── profile.vue        → /client/profile (Hồ sơ cá nhân)
│   └── services.vue       → /client/services (Dịch vụ)
└── admin/
    ├── index.vue          → /admin (Dashboard admin)
    └── users.vue          → /admin/users (Quản lý người dùng)
```

### 2. Dynamic Routes

```vue
<!-- pages/users/[id].vue -->
<template>
  <div>User ID: {{ $route.params.id }}</div>
</template>

<script setup>
const route = useRoute()
const userId = route.params.id
</script>
```

### 3. Nested Routes

```vue
<!-- pages/users/[id]/profile.vue -->
<template>
  <div>Profile của user {{ $route.params.id }}</div>
</template>
```

## Cách sử dụng

### 1. Navigation với `<NuxtLink>`

```vue
<template>
  <!-- Basic navigation -->
  <NuxtLink to="/">Trang chủ</NuxtLink>
  <NuxtLink to="/client">Client Portal</NuxtLink>
  <NuxtLink to="/admin">Admin Portal</NuxtLink>
  
  <!-- With params -->
  <NuxtLink :to="{ name: 'users-id', params: { id: 123 } }">
    User 123
  </NuxtLink>
  
  <!-- External links -->
  <a href="https://example.com" target="_blank">External Link</a>
</template>
```

### 2. Programmatic Navigation

```vue
<script setup>
const router = useRouter()

// Navigate to route
const goToHome = () => {
  router.push('/')
}

// Navigate with params
const goToUser = (id) => {
  router.push(`/users/${id}`)
}

// Replace current route
const replaceRoute = () => {
  router.replace('/new-route')
}

// Go back
const goBack = () => {
  router.back()
}
</script>
```

### 3. Route Parameters

```vue
<script setup>
const route = useRoute()

// Access route params
const userId = route.params.id
const query = route.query.search

// Watch for route changes
watch(() => route.params.id, (newId) => {
  console.log('User ID changed:', newId)
})
</script>
```

## Layouts

### 1. Sử dụng Layout

```vue
<script setup>
definePageMeta({
  layout: 'client' // hoặc 'admin'
})
</script>
```

### 2. Layout Files

```
frontend/layouts/
├── default.vue    → Layout mặc định
├── client.vue     → Layout cho client portal
└── admin.vue      → Layout cho admin portal
```

## Middleware

### 1. Global Middleware

```typescript
// middleware/auth.global.ts
export default defineNuxtRouteMiddleware((to, from) => {
  const isAuthenticated = true // Check auth status
  
  if (to.path.startsWith('/admin') && !isAuthenticated) {
    return navigateTo('/login')
  }
})
```

### 2. Route Middleware

```vue
<script setup>
definePageMeta({
  middleware: ['auth', 'admin']
})
</script>
```

### 3. Inline Middleware

```vue
<script setup>
definePageMeta({
  middleware: [
    function (to, from) {
      // Custom middleware logic
    }
  ]
})
</script>
```

## Authentication & Authorization

### 1. Auth Composable

```vue
<script setup>
const { isAuthenticated, userRole, login, logout } = useAuth()

// Check auth status
if (!isAuthenticated.value) {
  await navigateTo('/login')
}

// Login
const handleLogin = async () => {
  const result = await login(email, password)
  if (result.success) {
    await navigateTo('/dashboard')
  }
}

// Logout
const handleLogout = () => {
  logout()
  navigateTo('/login')
}
</script>
```

### 2. Role-based Access

```vue
<script setup>
const { isAdmin, isClient } = useAuth()

// Show admin content
<div v-if="isAdmin">
  <h2>Admin Panel</h2>
</div>

// Show client content
<div v-if="isClient">
  <h2>Client Dashboard</h2>
</div>
</script>
```

## Error Handling

### 1. 404 Page

```vue
<!-- error.vue -->
<template>
  <div class="error-page">
    <h1>{{ error.statusCode }}</h1>
    <p>{{ error.message }}</p>
    <button @click="handleError">Quay lại trang chủ</button>
  </div>
</template>

<script setup>
const error = useError()

const handleError = () => {
  clearError()
  navigateTo('/')
}
</script>
```

### 2. Custom Error Pages

```
frontend/
├── error.vue           → Global error page
├── 404.vue            → 404 error page
└── 500.vue            → 500 error page
```

## Best Practices

### 1. Route Naming

- Sử dụng kebab-case cho route names
- Đặt tên có ý nghĩa và dễ hiểu
- Nhóm các route liên quan vào cùng thư mục

### 2. Navigation

- Sử dụng `<NuxtLink>` cho internal navigation
- Sử dụng `router.push()` cho programmatic navigation
- Luôn xử lý loading states khi navigate

### 3. Security

- Luôn kiểm tra authentication trước khi truy cập protected routes
- Sử dụng middleware để bảo vệ routes
- Validate route parameters

### 4. Performance

- Sử dụng lazy loading cho các route ít dùng
- Implement proper caching strategies
- Optimize bundle size

## Examples

### 1. Protected Route

```vue
<!-- pages/admin/users.vue -->
<template>
  <div>Admin Users Page</div>
</template>

<script setup>
definePageMeta({
  layout: 'admin',
  middleware: ['auth']
})

// Check admin role
const { isAdmin } = useAuth()
if (!isAdmin.value) {
  throw createError({
    statusCode: 403,
    message: 'Không có quyền truy cập'
  })
}
</script>
```

### 2. Dynamic Route với API

```vue
<!-- pages/users/[id].vue -->
<template>
  <div v-if="pending">Loading...</div>
  <div v-else-if="error">Error: {{ error.message }}</div>
  <div v-else>
    <h1>{{ user.name }}</h1>
    <p>{{ user.email }}</p>
  </div>
</template>

<script setup>
const route = useRoute()
const { data: user, pending, error } = await useFetch(`/api/users/${route.params.id}`)
</script>
```

### 3. Search với Query Parameters

```vue
<!-- pages/search.vue -->
<template>
  <div>
    <input v-model="searchQuery" @input="updateSearch" placeholder="Tìm kiếm...">
    <div v-for="result in results" :key="result.id">
      {{ result.name }}
    </div>
  </div>
</template>

<script setup>
const route = useRoute()
const router = useRouter()
const searchQuery = ref(route.query.q || '')

const updateSearch = () => {
  router.push({
    query: { q: searchQuery.value }
  })
}

const { data: results } = await useFetch('/api/search', {
  query: { q: route.query.q }
})
</script>
```

## Troubleshooting

### 1. Common Issues

- **Route không hoạt động**: Kiểm tra tên file và cấu trúc thư mục
- **Middleware không chạy**: Đảm bảo middleware được đặt đúng vị trí
- **Layout không áp dụng**: Kiểm tra `definePageMeta`

### 2. Debug Tips

- Sử dụng Vue DevTools để debug routing
- Kiểm tra console logs
- Verify route parameters và query strings

## References

- [Nuxt 4 Documentation](https://nuxt.com/docs)
- [Vue Router Documentation](https://router.vuejs.org/)
- [Nuxt Routing Guide](https://nuxt.com/docs/getting-started/routing)
