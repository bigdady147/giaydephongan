<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    class="base-button"
    :class="[
      `base-button--${variant}`,
      { 'base-button--loading': loading },
      { 'base-button--full': fullWidth }
    ]"
    @click="$emit('click', $event)"
  >
    <div v-if="loading" class="loader"></div>
    <slot v-else></slot>
  </button>
</template>

<script setup lang="ts">
defineProps({
  type: {
    type: String as () => 'button' | 'submit' | 'reset',
    default: 'button'
  },
  variant: {
    type: String as () => 'primary' | 'secondary' | 'outline',
    default: 'primary'
  },
  disabled: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  fullWidth: {
    type: Boolean,
    default: false
  }
})

defineEmits(['click'])
</script>

<style scoped lang="scss">
.base-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 12px 24px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 16px;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
  outline: none;
  width: auto;

  &--full {
    width: 100%;
  }

  &--primary {
    background-color: #FF5C00;
    color: white;

    &:hover:not(:disabled) {
      background-color: darken(#FF5C00, 5%);
      box-shadow: 0 4px 12px rgba(255, 92, 0, 0.3);
    }
  }

  &--secondary {
    background-color: #f3f4f6;
    color: #1f2937;

    &:hover:not(:disabled) {
      background-color: #e5e7eb;
    }
  }

  &--outline {
    background-color: transparent;
    border: 1px solid #e5e7eb;
    color: #374151;

    &:hover:not(:disabled) {
      background-color: #f9fafb;
    }
  }

  &:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
}

.loader {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: #fff;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
