<template>
  <v-container fluid class="content-manager-page">
    <v-card variant="outlined" class="mb-4 panel-card">
      <v-card-text class="pb-2">
        <v-row dense>
          <v-col cols="12" md="3">
            <v-select
              v-model="filters.page"
              :items="pageFilterOptions"
              item-title="label"
              item-value="value"
              label="Page"
              :title="toolHelp('filterPage')"
              variant="outlined"
              density="compact"
              hide-details
            ></v-select>
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="filters.search"
              label="Search by section, field, or value"
              :title="toolHelp('filterSearch')"
              variant="outlined"
              density="compact"
              hide-details
            ></v-text-field>
          </v-col>
          <v-col cols="12" md="2">
            <v-switch
              v-model="filters.includeInactive"
              color="primary"
              label="Show inactive"
              :title="toolHelp('filterInactive')"
              hide-details
              inset
            ></v-switch>
          </v-col>
          <v-col cols="12" md="3" class="d-flex ga-2 justify-end align-center">
            <v-btn
              color="success"
              variant="outlined"
              :title="toolHelp('saveAll')"
              :disabled="!dirtyEntryCount"
              :loading="savingAll"
              @click="saveAllDirty"
            >
              Save All ({{ dirtyEntryCount }})
            </v-btn>
            <v-btn variant="outlined" color="primary" :title="toolHelp('clearFilters')" @click="resetFilters">Clear</v-btn>
            <v-btn variant="outlined" color="primary" :title="toolHelp('refreshRegistry')" :loading="loading" @click="loadBootstrap">Refresh</v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

      <v-card variant="outlined" class="panel-card">
      <v-card-title class="d-flex align-center">
        Website Content Registry
        <v-tooltip location="top">
          <template #activator="{ props }">
            <v-icon v-bind="props" size="18" class="ml-2 text-medium-emphasis">mdi-information-outline</v-icon>
          </template>
          <span>{{ toolHelp('registryIntro') }}</span>
        </v-tooltip>
        <v-spacer></v-spacer>
        <v-chip size="small" color="primary" variant="tonal" class="me-2">{{ selectedPageLabel }}</v-chip>
        <v-chip size="small" variant="outlined">{{ groupedEntries.length }} sections</v-chip>
      </v-card-title>
      <v-card-text>
        <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-3"></v-progress-linear>

        <v-expansion-panels variant="accordion">
          <v-expansion-panel
            v-for="group in groupedEntries"
            :key="group.groupKey"
          >
            <v-expansion-panel-title>
              <div class="d-flex align-center flex-wrap ga-2">
                <span class="font-weight-medium">{{ formatLabel(group.sectionKey) }}</span>
                <v-chip size="x-small" variant="outlined">{{ group.items.length }} fields</v-chip>
              </div>
            </v-expansion-panel-title>
            <v-expansion-panel-text>
              <div v-if="isMapSectionGroup(group)" class="map-editor-card">
                <div class="d-flex flex-wrap align-center justify-space-between ga-2 mb-3">
                  <div class="text-body-2 text-medium-emphasis map-editor-copy">
                    Click the map to place the pin, drag the marker to fine-tune it, and zoom in or out to save the default view.
                  </div>
                  <v-btn
                    size="small"
                    color="primary"
                    variant="outlined"
                    :loading="savingMapSectionKey === mapEditorKey(group)"
                    :disabled="!hasDirtyEntries(group.items)"
                    @click="saveMapSection(group)"
                  >
                    Save Map
                  </v-btn>
                </div>

                <div class="d-flex flex-wrap ga-2 mb-3">
                  <v-chip size="small" variant="outlined">Lat {{ formatCoordinate(mapSectionLatitude(group)) }}</v-chip>
                  <v-chip size="small" variant="outlined">Lng {{ formatCoordinate(mapSectionLongitude(group)) }}</v-chip>
                  <v-chip size="small" variant="outlined">Zoom {{ mapSectionZoom(group) }}</v-chip>
                </div>

                <div class="d-flex flex-wrap ga-2 align-center mb-3">
                  <v-btn-toggle
                    :model-value="mapSectionType(group)"
                    mandatory
                    density="comfortable"
                    divided
                    @update:modelValue="updateMapType(group, $event)"
                  >
                    <v-btn value="roadmap">Roadmap</v-btn>
                    <v-btn value="satellite">Satellite</v-btn>
                  </v-btn-toggle>

                  <v-btn size="small" color="primary" variant="text" @click="recenterMapSection(group)">
                    Recenter
                  </v-btn>
                </div>

                <div v-if="mapPickerError" class="text-caption text-error mb-2">
                  {{ mapPickerError }}
                </div>

                <div class="map-picker-shell">
                  <div :ref="(element) => setMapCanvasRef(group, element)" class="map-picker-canvas"></div>
                </div>
              </div>

              <v-row
                v-for="entry in group.items"
                :key="entry.localKey"
                dense
                class="entry-row"
              >
                <v-col cols="12" md="3" class="d-flex align-center">
                  <div class="entry-field-name">{{ formatLabel(entry.fieldKey) }}</div>
                </v-col>
                <v-col cols="12" md="7">
                  <template v-if="entry.fieldType === 'image'">
                    <div class="d-flex flex-wrap align-center ga-2 mb-2">
                      <v-btn
                        size="small"
                        color="primary"
                        variant="outlined"
                        :loading="uploadingEntryKey === entry.localKey"
                        :title="toolHelp('uploadImage')"
                        @click="openImagePicker(entry)"
                      >
                        Upload Image
                      </v-btn>
                      <span class="text-caption text-medium-emphasis">{{ shortenPath(entry.contentValue) }}</span>
                    </div>
                    <div
                      v-if="entry.contentValue"
                      class="image-preview-wrap"
                    >
                      <img :src="entry.contentValue" alt="" class="image-preview" />
                    </div>
                  </template>
                  <v-textarea
                    v-else-if="entry.fieldType === 'textarea' || entry.fieldType === 'richtext'"
                    v-model="entry.contentValue"
                    label="Content Value"
                    :title="toolHelp('fieldValue')"
                    rows="2"
                    auto-grow
                    variant="outlined"
                    density="compact"
                    hide-details
                  ></v-textarea>
                  <v-text-field
                    v-else
                    v-model="entry.contentValue"
                    label="Content Value"
                    :title="toolHelp('fieldValue')"
                    variant="outlined"
                    density="compact"
                    hide-details
                  ></v-text-field>
                </v-col>
                <v-col cols="12" md="2" class="d-flex align-center justify-end ga-2">
                  <v-switch
                    v-model="entry.isActive"
                    color="primary"
                    :title="toolHelp('activeToggle')"
                    hide-details
                    inset
                  ></v-switch>
                  <v-btn
                    size="small"
                    color="primary"
                    variant="outlined"
                    :title="toolHelp('saveRow')"
                    :loading="savingEntryKey === entry.localKey || uploadingEntryKey === entry.localKey"
                    :disabled="!isDirty(entry) || uploadingEntryKey === entry.localKey"
                    @click="saveEntry(entry)"
                  >
                    Save
                  </v-btn>
                </v-col>

                <v-col cols="12" class="entry-meta-row">
                  <span class="text-caption text-medium-emphasis">
                    {{ entry.updatedAt ? `Updated ${formatDateTime(entry.updatedAt)}` : 'Not yet saved' }}
                    <span v-if="entry.updatedBy"> by {{ entry.updatedBy }}</span>
                  </span>
                </v-col>
              </v-row>
            </v-expansion-panel-text>
          </v-expansion-panel>
        </v-expansion-panels>

        <div v-if="!groupedEntries.length" class="text-medium-emphasis mt-2">
          No content fields found for the current filters.
        </div>
      </v-card-text>
    </v-card>

    <input
      ref="imageFileInput"
      class="d-none"
      type="file"
      accept="image/*"
      @change="onImageFileSelected"
    >

    <v-snackbar v-model="snackbar.show" :color="snackbar.type" timeout="3200" location="bottom right">
      {{ snackbar.message }}
    </v-snackbar>
  </v-container>
</template>

<script>
import axios from 'axios'

const isLocalHost =
  typeof window !== 'undefined' &&
  (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')

const LOCAL_API_BASE = 'http://localhost/facilitate/src/php'
const LIVE_API_BASE = 'https://facilitatecareservices.co.uk/php'
const API_BASE = isLocalHost ? LOCAL_API_BASE : LIVE_API_BASE
const AUTH_STORAGE_KEY = 'facilitateCurrentUser'
const LEAFLET_CSS_URL = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
const LEAFLET_JS_URL = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
let leafletAssetsPromise = null

function loadLeafletAssets () {
  if (typeof window === 'undefined') {
    return Promise.reject(new Error('Map picker is only available in the browser.'))
  }

  if (window.L) {
    return Promise.resolve(window.L)
  }

  if (!leafletAssetsPromise) {
    leafletAssetsPromise = new Promise((resolve, reject) => {
      let loadTimeoutId = null
      let existingScriptLoadHandler = null
      let existingScriptErrorHandler = null

      const cleanup = (scriptElement = null) => {
        if (loadTimeoutId !== null) {
          window.clearTimeout(loadTimeoutId)
          loadTimeoutId = null
        }

        if (scriptElement && existingScriptLoadHandler && existingScriptErrorHandler) {
          scriptElement.removeEventListener('load', existingScriptLoadHandler)
          scriptElement.removeEventListener('error', existingScriptErrorHandler)
        }
      }

      const finishResolve = (scriptElement = null) => {
        cleanup(scriptElement)
        resolve(window.L)
      }

      const finishReject = (error, scriptElement = null) => {
        cleanup(scriptElement)
        reject(error)
      }

      loadTimeoutId = window.setTimeout(() => {
        finishReject(new Error('Map picker timed out while loading.'))
      }, 10000)

      const existingLink = document.querySelector(`link[data-leaflet-css="true"]`)
      if (!existingLink) {
        const link = document.createElement('link')
        link.rel = 'stylesheet'
        link.href = LEAFLET_CSS_URL
        link.setAttribute('data-leaflet-css', 'true')
        document.head.appendChild(link)
      }

      const existingScript = document.querySelector(`script[data-leaflet-js="true"]`)
      if (existingScript) {
        existingScriptLoadHandler = () => finishResolve(existingScript)
        existingScriptErrorHandler = () => finishReject(new Error('Failed to load the map picker library.'), existingScript)
        existingScript.addEventListener('load', existingScriptLoadHandler, { once: true })
        existingScript.addEventListener('error', existingScriptErrorHandler, { once: true })
        return
      }

      const script = document.createElement('script')
      script.src = LEAFLET_JS_URL
      script.async = true
      script.defer = true
      script.setAttribute('data-leaflet-js', 'true')
      script.onload = () => finishResolve()
      script.onerror = () => finishReject(new Error('Failed to load the map picker library.'))
      document.head.appendChild(script)
    }).catch((error) => {
      leafletAssetsPromise = null
      throw error
    })
  }

  return leafletAssetsPromise
}
const PAGE_LABELS = {
  global: 'Global Content',
  home: 'Home',
  about: 'About Us',
  contact: 'Contact Us',
  care: 'Social Care',
  caregiver: 'Caregiver Jobs',
  chronical: 'Palliative Care',
  discharge: 'Hospital Discharge',
  lifecare: 'End of Life Care',
  livein: 'Live In Care',
  personalcare: 'Personal Care',
  respitecare: 'Respite Care',
  specialcare: 'Special Needs Care',
  started: 'Get Started',
  support: 'Supported Living',
  surgery: '24/7 Day Support',
  elderlyservice: 'Elderly Care Service',
  gallery: 'Gallery',
  team: 'Team',
  faq: 'FAQ',
  blog: 'Blog',
  blogdetail: 'Blog Detail',
  testimonial: 'Testimonials',
}
const PAGE_NAV_ORDER = [
  'global',
  'home',
  'about',
  'elderlyservice',
  'personalcare',
  'respitecare',
  'livein',
  'discharge',
  'care',
  'chronical',
  'support',
  'specialcare',
  'started',
  'blog',
  'gallery',
  'team',
  'testimonial',
  'faq',
  'caregiver',
  'contact',
  'blogdetail',
  'surgery',
  'lifecare',
]
const DEFAULT_SECTION_ORDER = [
  'header',
  'hero',
  'content',
  'services_intro',
  'services_feature_cards',
  'services_catalog',
  'services_catalog_items',
  'care_tasks',
  'mental_wellbeing',
  'movement_cta',
  'partners',
  'contact_section',
  'map_section',
  'sidebar',
  'cta',
]
const SECTION_ORDER_BY_PAGE = {
  global: ['header', 'footer'],
  home: [
    'hero',
    'services_intro',
    'services_feature_cards',
    'services_catalog',
    'services_catalog_items',
    'care_tasks',
    'mental_wellbeing',
    'movement_cta',
    'partners',
    'contact_section',
    'map_section',
  ],
  about: ['hero', 'content'],
  contact: ['hero', 'content', 'map_section'],
  care: ['hero', 'content', 'sidebar'],
  caregiver: ['hero', 'content'],
  chronical: ['hero', 'content', 'sidebar'],
  discharge: ['hero', 'content', 'sidebar'],
  lifecare: ['hero', 'content', 'sidebar'],
  livein: ['hero', 'content', 'sidebar'],
  personalcare: ['hero', 'content', 'sidebar'],
  respitecare: ['hero', 'content', 'sidebar'],
  specialcare: ['hero', 'content', 'sidebar'],
  started: ['hero', 'content', 'cta'],
  support: ['hero', 'content', 'sidebar'],
  surgery: ['hero', 'content', 'sidebar'],
  elderlyservice: ['hero', 'content', 'sidebar'],
  gallery: ['hero', 'content'],
  team: ['hero', 'content'],
  faq: ['hero', 'content'],
  blog: ['hero', 'content', 'sidebar'],
  blogdetail: ['hero', 'content', 'sidebar'],
  testimonial: ['hero'],
}
const TOOL_HELP = {
  registryIntro: 'Select a page first, then expand sections to edit content values.',
  filterPage: 'Choose the frontend page you want to edit.',
  filterSearch: 'Search by section, field label, or content value on the selected page.',
  filterInactive: 'Include inactive fields that are hidden from public pages.',
  clearFilters: 'Reset page filter, search query, and inactive toggle to defaults.',
  refreshRegistry: 'Reload the latest content fields from the database.',
  saveAll: 'Save all modified rows in one bulk operation.',
  fieldValue: 'The actual content shown on the website frontend.',
  uploadImage: 'Upload an image file. It saves to the current frontend image folder when available.',
  activeToggle: 'Enable or disable this field for public frontend rendering.',
  saveRow: 'Save changes for this row only.',
}

export default {
  data: () => ({
    loading: false,
    savingAll: false,
    savingEntryKey: null,
    savingMapSectionKey: null,
    uploadingEntryKey: null,
    uploadTargetEntryKey: null,
    mapPickerError: '',

    entries: [],
    originalEntryMap: {},
    mapPickerRefreshTimer: null,

    filters: {
      page: '',
      search: '',
      includeInactive: false,
    },

    snackbar: {
      show: false,
      message: '',
      type: 'success',
    },
  }),
  computed: {
    uniquePages () {
      return [...new Set(this.entries.map((entry) => entry.pageKey).filter(Boolean))]
    },
    imageFieldCount () {
      return this.entries.filter((entry) => entry.fieldType === 'image').length
    },
    pageFilterOptions () {
      const pages = this.uniquePages
        .map((page) => ({ label: this.pageLabel(page), value: page }))
        .sort((a, b) => {
          const rankA = this.pageSortRank(a.value)
          const rankB = this.pageSortRank(b.value)
          if (rankA !== rankB) {
            return rankA - rankB
          }
          return a.label.localeCompare(b.label)
        })
      return pages
    },
    selectedPageLabel () {
      if (!this.filters.page) {
        return 'Select a page'
      }
      return this.pageLabel(this.filters.page)
    },
    filteredEntries () {
      const query = String(this.filters.search || '').trim().toLowerCase()
      return this.entries.filter((entry) => {
        if (!this.filters.page) {
          return false
        }
        if (!this.filters.includeInactive && !entry.isActive) {
          return false
        }
        if (entry.pageKey !== this.filters.page) {
          return false
        }
        if (entry.sectionKey === 'footer' && entry.pageKey !== 'global') {
          return false
        }
        if (!query) {
          return true
        }

        const haystack = [
          entry.sectionKey,
          entry.fieldKey,
          entry.contentValue,
        ]
          .join(' ')
          .toLowerCase()

        return haystack.includes(query)
      })
    },
    groupedEntries () {
      const pageKey = this.normalizeKey(this.filters.page)
      const grouped = this.filteredEntries.reduce((acc, entry) => {
        const groupKey = entry.sectionKey
        if (!acc[groupKey]) {
          acc[groupKey] = {
            groupKey,
            sectionKey: entry.sectionKey,
            items: [],
          }
        }
        acc[groupKey].items.push(entry)
        return acc
      }, {})

      return Object.values(grouped)
        .sort((a, b) => {
          const rankA = this.sectionSortRank(pageKey, a.sectionKey)
          const rankB = this.sectionSortRank(pageKey, b.sectionKey)
          if (rankA !== rankB) {
            return rankA - rankB
          }
          return a.sectionKey.localeCompare(b.sectionKey)
        })
        .map((group) => ({
          ...group,
          items: [...group.items].sort((a, b) => this.compareFieldKeys(a.fieldKey, b.fieldKey)),
        }))
    },
    dirtyEntryCount () {
      return this.entries.filter((entry) => this.isDirty(entry)).length
    },
  },
  created () {
    this._mapCanvasRefs = Object.create(null)
    this._mapPickerInstances = Object.create(null)
    this.loadBootstrap()
  },
  beforeUnmount () {
    if (this.mapPickerRefreshTimer) {
      clearTimeout(this.mapPickerRefreshTimer)
      this.mapPickerRefreshTimer = null
    }
    this.destroyAllMapPickers()
  },
  watch: {
    'filters.page' () {
      this.queueMapPickerRefresh()
    },
  },
  methods: {
    apiUrl (action) {
      return `${API_BASE}/websiteContent.php?action=${action}`
    },
    shortenPath (value) {
      const raw = String(value || '').trim()
      if (!raw) {
        return 'No image uploaded'
      }
      return raw.length > 70 ? `${raw.slice(0, 30)}...${raw.slice(-30)}` : raw
    },
    formatLabel (value) {
      return String(value || '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase())
    },
    formatDateTime (value) {
      const raw = String(value || '').trim()
      if (!raw) {
        return '-'
      }
      const parsed = new Date(raw.includes('T') ? raw : raw.replace(' ', 'T'))
      if (Number.isNaN(parsed.getTime())) {
        return raw
      }
      return parsed.toLocaleString()
    },
    notify (message, type = 'success') {
      this.snackbar.message = message
      this.snackbar.type = type
      this.snackbar.show = true
    },
    toolHelp (key) {
      return TOOL_HELP[key] || ''
    },
    hasDirtyEntries (entries = []) {
      return Array.isArray(entries) && entries.some((entry) => this.isDirty(entry))
    },
    isMapSectionGroup (group) {
      return this.normalizeKey(group?.sectionKey) === 'map_section'
    },
    mapEditorKey (group) {
      return `${this.normalizeKey(this.filters.page)}:${this.normalizeKey(group?.sectionKey)}`
    },
    getGroupEntry (group, fieldKey) {
      const normalizedFieldKey = this.normalizeKey(fieldKey)
      return group?.items?.find((entry) => this.normalizeKey(entry.fieldKey) === normalizedFieldKey) || null
    },
    mapEntryValue (group, fieldKey, fallback = '') {
      const entry = this.getGroupEntry(group, fieldKey)
      if (!entry) {
        return String(fallback ?? '')
      }
      return String(entry.contentValue ?? fallback ?? '')
    },
    mapEntryNumber (group, fieldKey, fallback) {
      const parsed = Number.parseFloat(this.mapEntryValue(group, fieldKey, fallback))
      return Number.isFinite(parsed) ? parsed : fallback
    },
    mapSectionLatitude (group) {
      return this.mapEntryNumber(group, 'latitude', 52.4056402)
    },
    mapSectionLongitude (group) {
      return this.mapEntryNumber(group, 'longitude', -1.5236883)
    },
    mapSectionZoom (group) {
      const parsed = Number.parseInt(this.mapEntryValue(group, 'zoom', 18), 10)
      if (!Number.isFinite(parsed)) {
        return 18
      }
      return Math.min(20, Math.max(3, parsed))
    },
    mapSectionType (group) {
      const raw = this.normalizeKey(this.mapEntryValue(group, 'map_type', 'satellite'))
      return raw === 'roadmap' ? 'roadmap' : 'satellite'
    },
    formatCoordinate (value) {
      const parsed = Number(value)
      return Number.isFinite(parsed) ? parsed.toFixed(6) : '-'
    },
    setGroupEntryValue (group, fieldKey, value) {
      const entry = this.getGroupEntry(group, fieldKey)
      if (!entry) {
        return
      }
      entry.contentValue = String(value ?? '')
    },
    updateMapCoordinates (group, latitude, longitude) {
      this.setGroupEntryValue(group, 'latitude', Number(latitude).toFixed(6))
      this.setGroupEntryValue(group, 'longitude', Number(longitude).toFixed(6))
    },
    updateMapZoom (group, zoom) {
      this.setGroupEntryValue(group, 'zoom', String(Math.min(20, Math.max(3, Number.parseInt(zoom, 10) || 18))))
    },
    updateMapType (group, mapType) {
      this.setGroupEntryValue(group, 'map_type', mapType === 'roadmap' ? 'roadmap' : 'satellite')
      this.queueMapPickerRefresh()
    },
    setMapCanvasRef (group, element) {
      const key = this.mapEditorKey(group)
      const currentElement = this._mapCanvasRefs[key]

      if (element) {
        if (currentElement === element) {
          return
        }

        this._mapCanvasRefs[key] = element
        this.queueMapPickerRefresh()
        return
      }

      if (currentElement) {
        delete this._mapCanvasRefs[key]
      }
      this.destroyMapPicker(key)
    },
    queueMapPickerRefresh () {
      if (typeof window === 'undefined') {
        return
      }
      if (this.mapPickerRefreshTimer) {
        clearTimeout(this.mapPickerRefreshTimer)
      }
      this.mapPickerRefreshTimer = window.setTimeout(() => {
        this.mapPickerRefreshTimer = null
        void this.refreshMapPickers()
      }, 0)
    },
    createMapTileLayer (L, mapType) {
      if (mapType === 'satellite') {
        return L.tileLayer(
          'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
          {
            attribution: 'Tiles (c) Esri',
            maxZoom: 20,
          }
        )
      }

      return L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '(c) OpenStreetMap contributors',
        maxZoom: 20,
      })
    },
    createMapPickerInstance (group, container, L) {
      const latitude = this.mapSectionLatitude(group)
      const longitude = this.mapSectionLongitude(group)
      const zoom = this.mapSectionZoom(group)
      const mapType = this.mapSectionType(group)

      const map = L.map(container, {
        center: [latitude, longitude],
        zoom,
        scrollWheelZoom: true,
      })

      const tileLayer = this.createMapTileLayer(L, mapType).addTo(map)
      const marker = L.marker([latitude, longitude], { draggable: true }).addTo(map)

      marker.on('dragend', () => {
        const position = marker.getLatLng()
        this.updateMapCoordinates(group, position.lat, position.lng)
      })

      map.on('click', (event) => {
        const { lat, lng } = event.latlng
        marker.setLatLng([lat, lng])
        this.updateMapCoordinates(group, lat, lng)
      })

      map.on('zoomend', () => {
        this.updateMapZoom(group, map.getZoom())
      })

      return {
        map,
        marker,
        tileLayer,
        mapType,
      }
    },
    syncMapPicker (group, L) {
      const key = this.mapEditorKey(group)
      const container = this._mapCanvasRefs[key]
      if (!container) {
        return
      }

      const latitude = this.mapSectionLatitude(group)
      const longitude = this.mapSectionLongitude(group)
      const zoom = this.mapSectionZoom(group)
      const mapType = this.mapSectionType(group)
      let instance = this._mapPickerInstances[key]

      if (!instance) {
        instance = this.createMapPickerInstance(group, container, L)
        this._mapPickerInstances[key] = instance
      } else {
        if (instance.mapType !== mapType) {
          instance.map.removeLayer(instance.tileLayer)
          instance.tileLayer = this.createMapTileLayer(L, mapType).addTo(instance.map)
          instance.mapType = mapType
        }

        instance.marker.setLatLng([latitude, longitude])
        instance.map.setView([latitude, longitude], zoom, { animate: false })
      }

      window.requestAnimationFrame(() => {
        instance.map.invalidateSize()
      })
    },
    async refreshMapPickers () {
      const mapGroups = this.groupedEntries.filter((group) => this.isMapSectionGroup(group))
      if (!mapGroups.length) {
        return
      }

      try {
        const L = await loadLeafletAssets()
        this.mapPickerError = ''
        mapGroups.forEach((group) => {
          this.syncMapPicker(group, L)
        })
      } catch (error) {
        this.mapPickerError = error?.message || 'Unable to load the map picker.'
      }
    },
    destroyMapPicker (key) {
      const instance = this._mapPickerInstances[key]
      if (!instance) {
        return
      }
      instance.map.remove()
      delete this._mapPickerInstances[key]
    },
    destroyAllMapPickers () {
      Object.keys(this._mapPickerInstances || {}).forEach((key) => {
        this.destroyMapPicker(key)
      })
    },
    recenterMapSection (group) {
      const key = this.mapEditorKey(group)
      const instance = this._mapPickerInstances[key]
      if (!instance) {
        this.queueMapPickerRefresh()
        return
      }
      instance.map.setView(
        [this.mapSectionLatitude(group), this.mapSectionLongitude(group)],
        this.mapSectionZoom(group),
        { animate: true }
      )
    },
    async saveMapSection (group) {
      if (!group || this.savingMapSectionKey) {
        return false
      }

      const dirtyEntries = group.items.filter((entry) => this.isDirty(entry))
      if (!dirtyEntries.length) {
        return false
      }

      this.savingMapSectionKey = this.mapEditorKey(group)
      try {
        const response = await axios.post(this.apiUrl('saveBulk'), {
          entries: dirtyEntries.map((entry) => this.cleanEntryForSave(entry)),
        })
        const payload = response?.data || {}
        if (!payload.success) {
          throw new Error(payload.message || 'Failed to save map section.')
        }

        this.normalizePayloadEntries(payload.entries || [])
        this.notify('Map section saved.')
        return true
      } catch (error) {
        this.notify(error?.response?.data?.message || 'Failed to save map section.', 'error')
        return false
      } finally {
        this.savingMapSectionKey = null
      }
    },
    fieldSortRank (fieldKey) {
      const key = String(fieldKey || '').toLowerCase()
      const priorityOrder = [
        'headline',
        'subheadline',
        'body_text',
        'text',
        'cta_text',
        'cta_url',
        'contact_phone',
        'contact_email_label',
        'contact_email',
        'whatsapp_number',
        'whatsapp_label',
        'whatsapp_message',
      ]
      const priorityIndex = priorityOrder.indexOf(key)
      if (priorityIndex !== -1) {
        return priorityIndex
      }
      return 100
    },
    compareFieldKeys (a, b) {
      const keyA = String(a || '').toLowerCase()
      const keyB = String(b || '').toLowerCase()
      const rankA = this.fieldSortRank(keyA)
      const rankB = this.fieldSortRank(keyB)
      if (rankA !== rankB) {
        return rankA - rankB
      }
      return keyA.localeCompare(keyB, undefined, { numeric: true, sensitivity: 'base' })
    },
    normalizeKey (value) {
      return String(value || '')
        .trim()
        .toLowerCase()
        .replace(/\s+/g, '_')
        .replace(/[^a-z0-9_-]/g, '')
    },
    entrySignature (entry) {
      return JSON.stringify({
        pageKey: this.normalizeKey(entry.pageKey),
        sectionKey: this.normalizeKey(entry.sectionKey),
        fieldKey: this.normalizeKey(entry.fieldKey),
        fieldType: String(entry.fieldType || 'text'),
        contentValue: String(entry.contentValue ?? ''),
        isActive: Boolean(entry.isActive),
      })
    },
    normalizeEntry (entry, index = 0) {
      const id = Number(entry?.id || 0)
      return {
        id: id > 0 ? id : null,
        localKey: id > 0 ? `id-${id}` : `new-${Date.now()}-${index}`,
        pageKey: this.normalizeKey(entry?.pageKey || ''),
        sectionKey: this.normalizeKey(entry?.sectionKey || ''),
        fieldKey: this.normalizeKey(entry?.fieldKey || ''),
        fieldType: String(entry?.fieldType || 'text'),
        contentValue: String(entry?.contentValue ?? ''),
        isActive: Boolean(entry?.isActive),
        updatedBy: String(entry?.updatedBy || ''),
        createdAt: entry?.createdAt || null,
        updatedAt: entry?.updatedAt || null,
      }
    },
    loadOriginalMap () {
      const map = {}
      this.entries.forEach((entry) => {
        map[entry.localKey] = this.entrySignature(entry)
      })
      this.originalEntryMap = map
    },
    isDirty (entry) {
      const original = this.originalEntryMap[entry.localKey]
      if (!original) {
        return true
      }
      return original !== this.entrySignature(entry)
    },
    resetFilters () {
      this.filters = {
        page: this.pageFilterOptions[0]?.value || '',
        search: '',
        includeInactive: false,
      }
    },
    currentUserLabel () {
      try {
        const raw = localStorage.getItem(AUTH_STORAGE_KEY)
        const user = raw ? JSON.parse(raw) : null
        return user?.name || user?.username || user?.email || 'Dashboard User'
      } catch (error) {
        return 'Dashboard User'
      }
    },
    normalizePayloadEntries (rows = []) {
      this.entries = Array.isArray(rows)
        ? rows.map((item, index) => this.normalizeEntry(item, index))
        : []
      if (!this.filters.page || !this.uniquePages.includes(this.filters.page)) {
        this.filters.page = this.pageFilterOptions[0]?.value || ''
      }
      this.loadOriginalMap()
      this.$nextTick(() => {
        this.queueMapPickerRefresh()
      })
    },
    pageLabel (pageKey) {
      const normalized = String(pageKey || '').trim().toLowerCase()
      return PAGE_LABELS[normalized] || this.formatLabel(normalized)
    },
    pageSortRank (pageKey) {
      const normalized = String(pageKey || '').trim().toLowerCase()
      const rank = PAGE_NAV_ORDER.indexOf(normalized)
      return rank === -1 ? 999 : rank
    },
    sectionSortRank (pageKey, sectionKey) {
      const normalizedPage = this.normalizeKey(pageKey)
      const normalizedSection = this.normalizeKey(sectionKey)
      if (normalizedSection === 'footer') {
        return 9999
      }

      const pageOrder = SECTION_ORDER_BY_PAGE[normalizedPage] || []
      const pageIndex = pageOrder.indexOf(normalizedSection)
      if (pageIndex !== -1) {
        return pageIndex
      }

      const defaultIndex = DEFAULT_SECTION_ORDER.indexOf(normalizedSection)
      if (defaultIndex !== -1) {
        return 200 + defaultIndex
      }

      return 1000
    },
    async loadBootstrap () {
      this.loading = true
      try {
        const response = await axios.get(this.apiUrl('getBootstrap'))
        const payload = response?.data || {}
        if (!payload.success) {
          throw new Error(payload.message || 'Failed to load website content.')
        }

        this.normalizePayloadEntries(payload.entries || [])
      } catch (error) {
        this.notify(error?.response?.data?.message || 'Failed to load website content.', 'error')
      } finally {
        this.loading = false
      }
    },
    cleanEntryForSave (entry) {
      return {
        id: entry.id || null,
        pageKey: this.normalizeKey(entry.pageKey),
        sectionKey: this.normalizeKey(entry.sectionKey),
        fieldKey: this.normalizeKey(entry.fieldKey),
        fieldType: String(entry.fieldType || 'text'),
        contentValue: String(entry.contentValue ?? ''),
        isActive: Boolean(entry.isActive),
        updatedBy: this.currentUserLabel(),
      }
    },
    openImagePicker (entry) {
      if (!entry || this.uploadingEntryKey) {
        return
      }
      this.uploadTargetEntryKey = entry.localKey
      const input = this.$refs.imageFileInput
      if (!input) {
        return
      }
      input.value = ''
      input.click()
    },
    async onImageFileSelected (event) {
      const files = event?.target?.files
      if (!files || !files.length || !this.uploadTargetEntryKey) {
        return
      }

      const entry = this.entries.find((item) => item.localKey === this.uploadTargetEntryKey)
      this.uploadTargetEntryKey = null
      event.target.value = ''
      if (!entry) {
        return
      }

      await this.uploadImageForEntry(entry, files[0])
    },
    async uploadImageForEntry (entry, file) {
      if (!entry || !file || this.uploadingEntryKey) {
        return
      }

      this.uploadingEntryKey = entry.localKey
      try {
        const formData = new FormData()
        formData.append('image', file)
        formData.append('pageKey', entry.pageKey)
        formData.append('sectionKey', entry.sectionKey)
        formData.append('fieldKey', entry.fieldKey)
        formData.append('existingPath', String(entry.contentValue || ''))

        const response = await axios.post(this.apiUrl('uploadImage'), formData)
        const payload = response?.data || {}
        if (!payload.success || !payload.path) {
          throw new Error(payload.message || 'Failed to upload image.')
        }

        entry.contentValue = String(payload.path)
        const wasSaved = await this.saveEntry(entry, { silentSuccess: true })
        if (wasSaved) {
          this.notify('Image uploaded and saved.')
        } else {
          this.notify('Image uploaded, but saving the field failed.', 'warning')
        }
      } catch (error) {
        this.notify(error?.response?.data?.message || 'Failed to upload image.', 'error')
      } finally {
        this.uploadingEntryKey = null
      }
    },
    async saveEntry (entry, options = {}) {
      const silentSuccess = Boolean(options?.silentSuccess)
      if (!entry || this.savingEntryKey) {
        return false
      }

      this.savingEntryKey = entry.localKey
      try {
        const response = await axios.post(this.apiUrl('saveEntry'), this.cleanEntryForSave(entry))
        const payload = response?.data || {}
        if (!payload.success) {
          throw new Error(payload.message || 'Failed to save content field.')
        }

        this.normalizePayloadEntries(payload.entries || [])
        if (!silentSuccess) {
          this.notify('Content field saved.')
        }
        return true
      } catch (error) {
        this.notify(error?.response?.data?.message || 'Failed to save content field.', 'error')
        return false
      } finally {
        this.savingEntryKey = null
      }
    },
    async saveAllDirty () {
      if (!this.dirtyEntryCount || this.savingAll) {
        return
      }

      this.savingAll = true
      try {
        const dirty = this.entries
          .filter((entry) => this.isDirty(entry))
          .map((entry) => this.cleanEntryForSave(entry))

        const response = await axios.post(this.apiUrl('saveBulk'), { entries: dirty })
        const payload = response?.data || {}
        if (!payload.success) {
          throw new Error(payload.message || 'Failed to save content changes.')
        }

        this.normalizePayloadEntries(payload.entries || [])
        this.notify('All content changes saved.')
      } catch (error) {
        this.notify(error?.response?.data?.message || 'Failed to save all changes.', 'error')
      } finally {
        this.savingAll = false
      }
    },
  },
}
</script>

<style scoped>
.content-manager-page {
  padding: 24px;
}

.panel-card {
  border-radius: 14px;
}

.entry-row {
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 12px;
  padding: 10px 8px 0;
  margin-bottom: 10px;
}

.entry-field-name {
  font-size: 0.88rem;
  font-weight: 600;
  color: rgba(0, 0, 0, 0.82);
  word-break: break-word;
}

.entry-meta-row {
  margin-top: -6px;
  padding: 0 4px 8px;
}

.image-preview-wrap {
  border: 1px dashed rgba(0, 0, 0, 0.16);
  border-radius: 8px;
  padding: 8px;
  max-width: 210px;
}

.image-preview {
  max-width: 100%;
  max-height: 120px;
  object-fit: contain;
  display: block;
}

.map-editor-card {
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 12px;
  padding: 14px;
  margin-bottom: 12px;
  background: rgba(15, 23, 42, 0.02);
}

.map-editor-copy {
  max-width: 680px;
}

.map-picker-shell {
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 12px;
  overflow: hidden;
  background: #fff;
}

.map-picker-canvas {
  width: 100%;
  min-height: 360px;
}

:deep(.leaflet-container) {
  font: inherit;
}

:deep(.leaflet-control-attribution) {
  font-size: 0.72rem;
}

@media (max-width: 960px) {
  .content-manager-page {
    padding: 12px;
  }
}
</style>
