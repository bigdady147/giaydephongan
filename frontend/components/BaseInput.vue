<template>
  <div class="base-input-container">
    <label v-if="label" class="base-input-label">{{ label }}</label>
    <div class="base-input-wrapper" :class="{ 'has-error': error, 'has-icon': hasIcon }">
      <span v-if="hasIcon" class="icon-left">
        <slot name="icon"></slot>
      </span>
      <input
        :value="modelValue"
        :type="inputType"
        :placeholder="placeholder"
        :required="required"
        class="base-input-field"
        @input="onInput"
      />
      <button 
        v-if="type === 'password'" 
        type="button" 
        class="toggle-password" 
        @click="togglePassword"
      >
        <!-- Simple SVG for eyes -->
        <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
        <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
      </button>
    </div>
    <span v-if="error" class="error-message">{{ error }}</span>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

const props = defineProps({
  modelValue: [String, Number],
  label: String,
  type: {
    type: String,
    default: 'text'
  },
  placeholder: String,
  error: String,
  required: Boolean,
  hasIcon: Boolean
})

const emit = defineEmits(['update:modelValue'])

const showPassword = ref(false)

const inputType = computed(() => {
  if (props.type === 'password') {
    return showPassword.value ? 'text' : 'password'
  }
  return props.type
})

const onInput = (event: Event) => {
  emit('update:modelValue', (event.target as HTMLInputElement).value)
}

const togglePassword = () => {
  showPassword.value = !showPassword.value
}
</script>

<style scoped lang="scss">
.base-input-container {
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 100%;
}

.base-input-label {
  font-size: 14px;
  font-weight: 500;
  color: #374151;
}

.base-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;

  &.has-error .base-input-field {
    border-color: #ef4444;
  }
}

.base-input-field {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 16px;
  background-color: #f9fafb;
  transition: all 0.2s ease;

  &:focus {
    outline: none;
    border-color: #FF5C00;
    background-color: white;
    box-shadow: 0 0 0 4px rgba(255, 92, 0, 0.1);
  }

  &::placeholder {
    color: #9ca3af;
  }
}

.has-icon .base-input-field {
  padding-left: 44px;
}

.icon-left {
  position: absolute;
  left: 14px;
  color: #9ca3af;
  display: flex;
  align-items: center;
}

.toggle-password {
  position: absolute;
  right: 14px;
  background: none;
  border: none;
  cursor: pointer;
  color: #9ca3af;
  display: flex;
  align-items: center;
  transition: color 0.2s;

  &:hover {
    color: #6b7280;
  }
}

.error-message {
  font-size: 12px;
  color: #ef4444;
}
</style>
