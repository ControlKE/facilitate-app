import axios from 'axios'
import { PHP_API_BASE } from '../utils/phpApi'

const API_BASE = PHP_API_BASE

let sharedCmsTree = null
let sharedCmsPromise = null

function normalizeTree(tree) {
  return tree && typeof tree === 'object' ? tree : {}
}

function normalizeText(value, fallback = '') {
  if (typeof value === 'string') {
    return value
  }
  if (value === null || typeof value === 'undefined') {
    return fallback
  }
  return String(value)
}

function normalizeCmsKey(value) {
  return normalizeText(value, '')
    .trim()
    .toLowerCase()
}

function normalizeWhatsAppNumber(value) {
  const digits = normalizeText(value, '').replace(/[^\d]/g, '')
  if (digits.startsWith('00')) {
    return digits.slice(2)
  }
  return digits
}

export default {
  data() {
    return {
      cmsContent: normalizeTree(sharedCmsTree),
      cmsLoaded: Boolean(sharedCmsTree),
    }
  },
  mounted() {
    if (typeof window === 'undefined') {
      return
    }

    this.__facilitateDialogActionHandler = (event) => {
      const action = String(event?.detail?.action || '').trim().toLowerCase()
      if (!action) {
        return
      }

      if (action === 'caregiver') {
        if (typeof this.openCaregiverDialog === 'function') {
          this.openCaregiverDialog()
          return
        }
        if (Object.prototype.hasOwnProperty.call(this.$data || {}, 'caregiverDialog')) {
          this.caregiverDialog = true
        }
        return
      }

      if (action === 'complaint') {
        if (typeof this.openComplaintDialog === 'function') {
          this.openComplaintDialog()
          return
        }
        if (Object.prototype.hasOwnProperty.call(this.$data || {}, 'complaintDialog')) {
          this.complaintDialog = true
        }
        return
      }

      if (action === 'login') {
        if (typeof this.openLoginDialog === 'function') {
          this.openLoginDialog()
          return
        }
        if (Object.prototype.hasOwnProperty.call(this.$data || {}, 'logindialog')) {
          this.logindialog = true
        }
      }
    }

    window.addEventListener('facilitate:open-dialog', this.__facilitateDialogActionHandler)
  },
  beforeUnmount() {
    if (typeof window === 'undefined' || !this.__facilitateDialogActionHandler) {
      return
    }

    window.removeEventListener('facilitate:open-dialog', this.__facilitateDialogActionHandler)
    this.__facilitateDialogActionHandler = null
  },
  methods: {
    cmsApiUrl(action) {
      return `${API_BASE}/websiteContent.php?action=${action}`
    },
    applyCmsTree(tree) {
      const normalized = normalizeTree(tree)
      sharedCmsTree = normalized
      this.cmsContent = normalized
      this.cmsLoaded = true
    },
    resolveCmsField(pageKey, sectionKey, fieldKey) {
      const normalizedPageKey = normalizeCmsKey(pageKey)
      const normalizedSectionKey = normalizeCmsKey(sectionKey)
      const normalizedFieldKey = normalizeText(fieldKey, '')
      const candidates = []

      if (normalizedSectionKey === 'footer' && normalizedPageKey !== 'global') {
        candidates.push(['global', 'footer'])
      }
      candidates.push([normalizedPageKey, normalizedSectionKey])

      for (const [candidatePageKey, candidateSectionKey] of candidates) {
        const page = this.cmsContent?.[candidatePageKey]
        const section = page?.[candidateSectionKey]
        const field = section?.[normalizedFieldKey]
        if (typeof field !== 'undefined') {
          return field
        }
      }

      return null
    },
    hasCmsField(pageKey, sectionKey, fieldKey) {
      return Boolean(this.resolveCmsField(pageKey, sectionKey, fieldKey))
    },
    isCmsFieldVisible(pageKey, sectionKey, fieldKey) {
      return !this.cmsLoaded || this.hasCmsField(pageKey, sectionKey, fieldKey)
    },
    cmsValue(pageKey, sectionKey, fieldKey, fallback = '') {
      if (!this.cmsLoaded && !sharedCmsPromise) {
        void this.loadCmsContent()
      }

      const field = this.resolveCmsField(pageKey, sectionKey, fieldKey)
      const value = typeof field?.value !== 'undefined' ? field.value : fallback
      return normalizeText(value, fallback)
    },
    footerValue(fieldKey, fallback = '') {
      return this.cmsValue('global', 'footer', fieldKey, fallback)
    },
    footerPhoneHref() {
      const phone = this.footerValue('contact_phone', '024 7623 1188')
      return `tel:${normalizeText(phone, '024 7623 1188').replace(/\s+/g, '') || '02476231188'}`
    },
    footerMailtoHref() {
      const email = this.footerValue('contact_email', 'info@facilitatecareservices.co.uk')
      return `mailto:${normalizeText(email, 'info@facilitatecareservices.co.uk') || 'info@facilitatecareservices.co.uk'}`
    },
    footerWhatsAppNumber() {
      return normalizeWhatsAppNumber(this.footerValue('whatsapp_number', ''))
    },
    footerWhatsAppMessage() {
      return normalizeText(
        this.footerValue('whatsapp_message', 'Hello, I would like to enquire about your care services.'),
        'Hello, I would like to enquire about your care services.'
      )
    },
    footerWhatsAppHref() {
      const number = this.footerWhatsAppNumber()
      if (!number) {
        return ''
      }

      const message = this.footerWhatsAppMessage()
      const query = message ? `?text=${encodeURIComponent(message)}` : ''
      return `https://wa.me/${number}${query}`
    },
    footerCqcHref() {
      return this.footerValue('cqc_url', 'https://www.cqc.org.uk/location/1-2131286214')
    },
    phoneHref() {
      const phone = this.cmsValue('global', 'header', 'phone', '024 7623 1188')
      const normalizedPhone = normalizeText(phone, '024 7623 1188').replace(/\s+/g, '')
      return `tel:${normalizedPhone || '02476231188'}`
    },
    async loadCmsContent(force = false) {
      if (force && sharedCmsPromise) {
        await sharedCmsPromise
      }

      if (!force && sharedCmsTree) {
        this.applyCmsTree(sharedCmsTree)
        return sharedCmsTree
      }

      if (!sharedCmsPromise) {
        sharedCmsPromise = axios
          .get(this.cmsApiUrl('getPublic'))
          .then((response) => {
            const payload = response?.data || {}
            const tree = payload?.success ? normalizeTree(payload.content) : {}
            sharedCmsTree = tree
            return tree
          })
          .catch(() => {
            return normalizeTree(sharedCmsTree)
          })
          .finally(() => {
            sharedCmsPromise = null
          })
      }

      const tree = await sharedCmsPromise
      this.applyCmsTree(tree)
      return tree
    },
  },
}
