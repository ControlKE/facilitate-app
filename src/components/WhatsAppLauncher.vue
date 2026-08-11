<template>
  <transition name="whatsapp-launcher">
    <a
      v-if="shouldShow"
      :href="whatsAppHref"
      class="whatsapp-launcher"
      target="_blank"
      rel="noopener noreferrer"
      :aria-label="buttonLabel"
    >
      <span class="launcher-glow"></span>
      <span class="launcher-shell">
        <span class="launcher-icon" aria-hidden="true">
          <svg viewBox="0 0 32 32" role="presentation" focusable="false">
            <path
              fill="currentColor"
              d="M19.11 17.19c-.27-.14-1.6-.79-1.85-.88-.25-.09-.43-.14-.61.14-.18.27-.7.88-.86 1.06-.16.18-.32.2-.59.07-.27-.14-1.15-.42-2.18-1.34-.8-.71-1.34-1.59-1.5-1.86-.16-.27-.02-.41.12-.55.12-.12.27-.32.41-.48.14-.16.18-.27.27-.46.09-.18.05-.34-.02-.48-.07-.14-.61-1.48-.84-2.03-.22-.53-.45-.45-.61-.46l-.52-.01c-.18 0-.48.07-.73.34-.25.27-.95.93-.95 2.26 0 1.34.98 2.64 1.11 2.82.14.18 1.91 2.91 4.63 4.08.65.28 1.16.45 1.55.57.65.21 1.23.18 1.7.11.52-.08 1.6-.65 1.82-1.28.23-.63.23-1.16.16-1.28-.06-.11-.25-.18-.52-.32Z"
            />
            <path
              fill="currentColor"
              d="M16.02 3.2c-6.98 0-12.65 5.67-12.65 12.65 0 2.23.59 4.42 1.7 6.34L3 29l6.99-2.06a12.62 12.62 0 0 0 6.03 1.54h.01c6.97 0 12.65-5.67 12.65-12.65S22.99 3.2 16.02 3.2Zm0 23.1h-.01a10.47 10.47 0 0 1-5.34-1.46l-.38-.22-4.15 1.22 1.25-4.04-.25-.42a10.48 10.48 0 0 1-1.61-5.56c0-5.78 4.71-10.49 10.5-10.49 2.8 0 5.44 1.09 7.42 3.08a10.42 10.42 0 0 1 3.07 7.42c0 5.79-4.7 10.49-10.49 10.49Z"
            />
          </svg>
        </span>
        <span class="launcher-copy">
          <strong>{{ buttonLabel }}</strong>
          <small>{{ buttonCaption }}</small>
        </span>
      </span>
    </a>
  </transition>
</template>

<script>
export default {
  name: 'WhatsAppLauncher',
  computed: {
    isDashboardArea() {
      return this.$route?.matched?.some((record) => String(record?.name || '') === 'dashboard') || false
    },
    isLoginPage() {
      return String(this.$route?.name || '') === 'login'
    },
    whatsAppHref() {
      return this.footerWhatsAppHref()
    },
    whatsAppNumber() {
      return this.footerWhatsAppNumber()
    },
    buttonLabel() {
      return this.footerValue('whatsapp_label', 'Chat on WhatsApp')
    },
    buttonCaption() {
      return this.whatsAppNumber ? 'Fastest way to reach our team' : ''
    },
    shouldShow() {
      return !this.isDashboardArea && !this.isLoginPage && Boolean(this.whatsAppHref) && this.isCmsFieldVisible('global', 'footer', 'whatsapp_number')
    },
  },
  mounted() {
    void this.loadCmsContent()
  },
}
</script>

<style scoped>
.whatsapp-launcher {
  position: fixed;
  right: 20px;
  bottom: 20px;
  z-index: 1400;
  text-decoration: none;
  color: #ffffff;
}

.launcher-shell {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: 999px;
  background: linear-gradient(135deg, #1c9c5b 0%, #25d366 58%, #6ef2a0 100%);
  box-shadow: 0 22px 45px rgba(20, 93, 55, 0.28);
  overflow: hidden;
}

.launcher-shell::after {
  content: '';
  position: absolute;
  inset: 1px;
  border-radius: inherit;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.22), rgba(255, 255, 255, 0.06));
  opacity: 0.55;
  pointer-events: none;
}

.launcher-glow {
  position: absolute;
  inset: -8px;
  border-radius: 999px;
  background: radial-gradient(circle, rgba(37, 211, 102, 0.35) 0%, rgba(37, 211, 102, 0) 72%);
  animation: whatsappPulse 2.8s ease-in-out infinite;
}

.launcher-icon,
.launcher-copy {
  position: relative;
  z-index: 1;
}

.launcher-icon {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(12, 69, 40, 0.22);
  backdrop-filter: blur(6px);
  flex: 0 0 42px;
}

.launcher-icon svg {
  width: 24px;
  height: 24px;
}

.launcher-copy {
  display: flex;
  flex-direction: column;
  line-height: 1.12;
}

.launcher-copy strong {
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: 0.01em;
}

.launcher-copy small {
  margin-top: 4px;
  font-size: 0.72rem;
  color: rgba(255, 255, 255, 0.9);
}

.whatsapp-launcher:hover .launcher-shell,
.whatsapp-launcher:focus-visible .launcher-shell {
  transform: translateY(-2px);
  box-shadow: 0 26px 52px rgba(20, 93, 55, 0.34);
}

.whatsapp-launcher:focus-visible {
  outline: none;
}

.whatsapp-launcher:focus-visible .launcher-shell {
  box-shadow: 0 0 0 4px rgba(37, 211, 102, 0.18), 0 26px 52px rgba(20, 93, 55, 0.34);
}

.whatsapp-launcher-enter-active,
.whatsapp-launcher-leave-active {
  transition: opacity 0.24s ease, transform 0.24s ease;
}

.whatsapp-launcher-enter-from,
.whatsapp-launcher-leave-to {
  opacity: 0;
  transform: translateY(12px);
}

@keyframes whatsappPulse {
  0%,
  100% {
    opacity: 0.5;
    transform: scale(0.95);
  }

  50% {
    opacity: 1;
    transform: scale(1.03);
  }
}

@media (max-width: 767px) {
  .whatsapp-launcher {
    right: 14px;
    bottom: 14px;
  }

  .launcher-shell {
    padding: 10px;
  }

  .launcher-copy {
    display: none;
  }
}
</style>
