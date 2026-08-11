<template>
  <v-container fluid class="maintenance-page">
    <v-row dense class="mb-4">
      <v-col cols="12" md="3">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">Open Jobs</div>
            <div class="summary-value">{{ summary.openJobs }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">Vehicles Off-Road</div>
            <div class="summary-value">{{ summary.offRoadVehicles }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">Overdue Jobs</div>
            <div class="summary-value">{{ summary.overdue }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">This Month Cost</div>
            <div class="summary-value">{{ formatCurrency(summary.thisMonthCost) }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-card variant="outlined" class="mb-4 panel-card">
      <v-card-text class="pb-2">
        <v-row dense>
          <v-col cols="12" md="4" lg="3">
            <v-text-field
              v-model="filters.search"
              label="Search by vehicle, garage, issue..."
              density="compact"
              variant="outlined"
              hide-details
            ></v-text-field>
          </v-col>
          <v-col cols="6" md="2" lg="2">
            <v-select
              v-model="filters.status"
              :items="statusFilterOptions"
              item-title="label"
              item-value="value"
              label="Status"
              density="compact"
              variant="outlined"
              hide-details
            ></v-select>
          </v-col>
          <v-col cols="6" md="2" lg="2">
            <v-select
              v-model="filters.severity"
              :items="severityFilterOptions"
              item-title="label"
              item-value="value"
              label="Severity"
              density="compact"
              variant="outlined"
              hide-details
            ></v-select>
          </v-col>
          <v-col cols="12" md="4" lg="3">
            <v-select
              v-model="filters.issueType"
              :items="issueTypeFilterOptions"
              item-title="label"
              item-value="value"
              label="Issue Type"
              density="compact"
              variant="outlined"
              hide-details
            ></v-select>
          </v-col>
          <v-col cols="12" lg="2" class="d-flex ga-2 align-center justify-end">
            <v-btn variant="outlined" color="primary" @click="resetFilters">Clear</v-btn>
            <v-btn variant="outlined" color="primary" :loading="loading" @click="loadBootstrap">Refresh</v-btn>
            <v-btn color="success" @click="openCreateDialog">Add</v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-card variant="outlined" class="panel-card">
      <v-card-title class="d-flex align-center">
        Maintenance Records
        <v-spacer></v-spacer>
        <v-chip size="small" variant="outlined">{{ filteredLogs.length }} records</v-chip>
      </v-card-title>
      <v-card-text>
        <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-3"></v-progress-linear>
        <div class="table-wrap">
          <v-table density="comfortable">
            <thead>
              <tr>
                <th>Date Logged</th>
                <th>Vehicle</th>
                <th>Issue</th>
                <th>Severity</th>
                <th>Status</th>
                <th>Expected Return</th>
                <th>Garage</th>
                <th>Cost</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in filteredLogs" :key="item.id">
                <td>
                  <div class="text-body-2">{{ formatDateTime(item.loggedAt) }}</div>
                  <div class="text-caption text-medium-emphasis">{{ item.createdBy || 'Unassigned' }}</div>
                </td>
                <td>
                  <div class="font-weight-medium">{{ item.vehicleReg }}</div>
                  <v-chip
                    v-if="isOffRoad(item.status)"
                    size="x-small"
                    color="warning"
                    variant="tonal"
                    class="mt-1"
                  >
                    Off-Road
                  </v-chip>
                </td>
                <td>
                  <div class="font-weight-medium">{{ formatIssueType(item.issueType) }}</div>
                  <div class="text-caption text-medium-emphasis text-truncate issue-snippet">
                    {{ item.description || 'No description' }}
                  </div>
                </td>
                <td>
                  <v-chip size="x-small" :color="severityColor(item.severity)" variant="tonal">
                    {{ formatLabel(item.severity) }}
                  </v-chip>
                </td>
                <td>
                  <v-chip size="x-small" :color="statusColor(item.status)" variant="tonal">
                    {{ formatLabel(item.status) }}
                  </v-chip>
                </td>
                <td>
                  <div class="text-body-2">{{ item.estimatedReturnAt ? formatDateTime(item.estimatedReturnAt) : '-' }}</div>
                  <div class="text-caption text-error" v-if="isOverdue(item)">Overdue</div>
                </td>
                <td>{{ item.assignedGarage || '-' }}</td>
                <td>
                  <div class="font-weight-medium">{{ formatCurrency(item.effectiveFinalCost) }}</div>
                  <div class="text-caption text-medium-emphasis">Est: {{ formatCurrency(item.estimatedCost) }}</div>
                </td>
                <td class="text-right">
                  <v-btn size="small" variant="text" color="primary" @click="openEditDialog(item)">Edit</v-btn>
                  <v-btn
                    v-if="item.status !== 'in_progress' && item.status !== 'completed' && item.status !== 'cancelled'"
                    size="small"
                    variant="text"
                    color="warning"
                    :loading="statusLoadingId === item.id"
                    @click="quickUpdateStatus(item, 'in_progress')"
                  >
                    Start
                  </v-btn>
                  <v-btn
                    v-if="item.status !== 'completed' && item.status !== 'cancelled'"
                    size="small"
                    variant="text"
                    color="success"
                    :loading="statusLoadingId === item.id"
                    @click="quickUpdateStatus(item, 'completed')"
                  >
                    Complete
                  </v-btn>
                </td>
              </tr>
              <tr v-if="!filteredLogs.length">
                <td colspan="9" class="text-medium-emphasis">No maintenance records found.</td>
              </tr>
            </tbody>
          </v-table>
        </div>
      </v-card-text>
    </v-card>

    <v-dialog v-model="dialog" max-width="980">
      <v-card>
        <v-card-title>{{ dialogTitle }}</v-card-title>
        <v-card-text>
          <v-row dense>
            <v-col cols="12" md="4">
              <v-select
                v-model="edited.vehicleId"
                :items="vehicleOptions"
                item-title="label"
                item-value="value"
                label="Vehicle"
                variant="outlined"
                density="comfortable"
              ></v-select>
            </v-col>
            <v-col cols="12" md="4">
              <v-select
                v-model="edited.issueType"
                :items="issueTypeOptions"
                label="Issue Type"
                variant="outlined"
                density="comfortable"
              ></v-select>
            </v-col>
            <v-col cols="12" md="4">
              <v-select
                v-model="edited.severity"
                :items="severityOptions"
                label="Severity"
                variant="outlined"
                density="comfortable"
              ></v-select>
            </v-col>

            <v-col cols="12" md="4">
              <v-select
                v-model="edited.status"
                :items="statusOptions"
                label="Status"
                variant="outlined"
                density="comfortable"
              ></v-select>
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="edited.loggedAt"
                type="datetime-local"
                label="Date Logged"
                variant="outlined"
                density="comfortable"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="edited.estimatedReturnAt"
                type="datetime-local"
                label="Expected Return"
                variant="outlined"
                density="comfortable"
              ></v-text-field>
            </v-col>

            <v-col cols="12" md="4">
              <v-text-field
                v-model="edited.actualReturnAt"
                type="datetime-local"
                label="Actual Return"
                variant="outlined"
                density="comfortable"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="edited.assignedGarage"
                label="Garage / Mechanic"
                variant="outlined"
                density="comfortable"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="edited.createdBy"
                label="Logged By"
                variant="outlined"
                density="comfortable"
              ></v-text-field>
            </v-col>

            <v-col cols="12" md="3">
              <v-text-field
                v-model="edited.estimatedCost"
                label="Estimated Cost"
                type="number"
                min="0"
                step="0.01"
                variant="outlined"
                density="comfortable"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="3">
              <v-text-field
                v-model="edited.partsCost"
                label="Parts Cost"
                type="number"
                min="0"
                step="0.01"
                variant="outlined"
                density="comfortable"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="3">
              <v-text-field
                v-model="edited.labourCost"
                label="Labour Cost"
                type="number"
                min="0"
                step="0.01"
                variant="outlined"
                density="comfortable"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="3">
              <v-text-field
                v-model="edited.finalCost"
                label="Final Cost"
                type="number"
                min="0"
                step="0.01"
                variant="outlined"
                density="comfortable"
              ></v-text-field>
            </v-col>

            <v-col cols="12" md="4">
              <v-text-field
                v-model="edited.mileage"
                label="Mileage"
                type="number"
                min="0"
                step="1"
                variant="outlined"
                density="comfortable"
              ></v-text-field>
            </v-col>

            <v-col cols="12">
              <v-textarea
                v-model="edited.description"
                label="Issue Description"
                rows="3"
                counter="700"
                maxlength="700"
                variant="outlined"
              ></v-textarea>
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
          <v-btn color="success" :disabled="!canSave" :loading="saving" @click="saveLog">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.type" timeout="3000" location="bottom right">
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

const defaultSummary = {
  openJobs: 0,
  offRoadVehicles: 0,
  overdue: 0,
  thisMonthCost: 0,
}

const defaultEdited = () => ({
  id: null,
  vehicleId: null,
  issueType: 'service',
  severity: 'medium',
  status: 'reported',
  loggedAt: '',
  description: '',
  assignedGarage: '',
  estimatedReturnAt: '',
  actualReturnAt: '',
  estimatedCost: 0,
  partsCost: 0,
  labourCost: 0,
  finalCost: 0,
  mileage: '',
  createdBy: '',
})

export default {
  data: () => ({
    loading: false,
    saving: false,
    statusLoadingId: null,

    vehicles: [],
    logs: [],
    summary: { ...defaultSummary },

    issueTypeOptions: ['service', 'mot', 'tyres', 'brakes', 'engine', 'electrical', 'bodywork', 'breakdown', 'other'],
    severityOptions: ['low', 'medium', 'high', 'critical'],
    statusOptions: ['reported', 'approved', 'in_progress', 'completed', 'cancelled'],

    filters: {
      search: '',
      status: 'all',
      severity: 'all',
      issueType: 'all',
    },

    dialog: false,
    edited: defaultEdited(),

    snackbar: {
      show: false,
      message: '',
      type: 'success',
    },
  }),
  computed: {
    dialogTitle () {
      return this.edited.id ? 'Edit Maintenance Record' : 'Add Maintenance Record'
    },
    vehicleOptions () {
      return this.vehicles
        .filter((item) => item.isActive)
        .map((item) => ({
          value: item.id,
          label: `${item.regNumber} - ${item.make} ${item.model}`,
        }))
    },
    filteredLogs () {
      const query = String(this.filters.search || '').trim().toLowerCase()

      return this.logs.filter((item) => {
        if (this.filters.status !== 'all' && item.status !== this.filters.status) {
          return false
        }
        if (this.filters.severity !== 'all' && item.severity !== this.filters.severity) {
          return false
        }
        if (this.filters.issueType !== 'all' && item.issueType !== this.filters.issueType) {
          return false
        }

        if (!query) {
          return true
        }

        const haystack = [
          item.vehicleReg,
          item.vehicleLabel,
          item.issueType,
          item.description,
          item.assignedGarage,
          item.createdBy,
        ]
          .join(' ')
          .toLowerCase()

        return haystack.includes(query)
      })
    },
    statusFilterOptions () {
      return [{ label: 'All Statuses', value: 'all' }, ...this.statusOptions.map((item) => ({ label: this.formatLabel(item), value: item }))]
    },
    severityFilterOptions () {
      return [{ label: 'All Severities', value: 'all' }, ...this.severityOptions.map((item) => ({ label: this.formatLabel(item), value: item }))]
    },
    issueTypeFilterOptions () {
      return [{ label: 'All Issue Types', value: 'all' }, ...this.issueTypeOptions.map((item) => ({ label: this.formatIssueType(item), value: item }))]
    },
    canSave () {
      return Boolean(this.edited.vehicleId && this.edited.issueType && this.edited.severity && this.edited.status && this.edited.loggedAt)
    },
  },
  watch: {
    '$route.query': {
      handler () {
        this.applyRouteVehicleIntent()
      },
      deep: true,
    },
  },
  created () {
    this.loadBootstrap()
  },
  methods: {
    apiUrl (action) {
      return `${API_BASE}/maintenanceLog.php?action=${action}`
    },
    prefillCreatedBy () {
      try {
        const raw = localStorage.getItem(AUTH_STORAGE_KEY)
        const user = raw ? JSON.parse(raw) : null
        return user?.name || user?.username || user?.email || ''
      } catch (error) {
        return ''
      }
    },
    currentLocalInputDateTime () {
      const now = new Date()
      now.setMinutes(now.getMinutes() - now.getTimezoneOffset())
      return now.toISOString().slice(0, 16)
    },
    toApiDateTime (inputValue) {
      const raw = String(inputValue || '').trim()
      if (!raw) {
        return null
      }
      const normalized = raw.replace('T', ' ')
      return normalized.length === 16 ? `${normalized}:00` : normalized
    },
    toInputDateTime (apiValue) {
      const raw = String(apiValue || '').trim()
      if (!raw) {
        return ''
      }
      const parsed = new Date(raw.includes('T') ? raw : raw.replace(' ', 'T'))
      if (Number.isNaN(parsed.getTime())) {
        return ''
      }
      parsed.setMinutes(parsed.getMinutes() - parsed.getTimezoneOffset())
      return parsed.toISOString().slice(0, 16)
    },
    formatCurrency (value) {
      return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: 'GBP',
      }).format(Number(value || 0))
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
    formatLabel (value) {
      return String(value || '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase())
    },
    formatIssueType (value) {
      return this.formatLabel(value)
    },
    severityColor (severity) {
      const map = {
        low: 'success',
        medium: 'primary',
        high: 'warning',
        critical: 'error',
      }
      return map[severity] || 'primary'
    },
    statusColor (status) {
      const map = {
        reported: 'grey-darken-1',
        approved: 'warning',
        in_progress: 'info',
        completed: 'success',
        cancelled: 'error',
      }
      return map[status] || 'grey-darken-1'
    },
    isOffRoad (status) {
      return status === 'approved' || status === 'in_progress'
    },
    isOverdue (item) {
      if (!item || !item.estimatedReturnAt) {
        return false
      }
      if (!['reported', 'approved', 'in_progress'].includes(item.status)) {
        return false
      }
      const eta = new Date(String(item.estimatedReturnAt).replace(' ', 'T')).getTime()
      if (!Number.isFinite(eta)) {
        return false
      }
      return eta < Date.now()
    },
    notify (message, type = 'success') {
      this.snackbar.message = message
      this.snackbar.type = type
      this.snackbar.show = true
    },
    normalizeBootstrap (payload = {}) {
      this.vehicles = Array.isArray(payload.vehicles) ? payload.vehicles : []
      this.logs = Array.isArray(payload.logs) ? payload.logs : []
      this.summary = { ...defaultSummary, ...(payload.summary || {}) }
      this.issueTypeOptions = Array.isArray(payload?.config?.issueTypes) && payload.config.issueTypes.length ? payload.config.issueTypes : this.issueTypeOptions
      this.severityOptions = Array.isArray(payload?.config?.severities) && payload.config.severities.length ? payload.config.severities : this.severityOptions
      this.statusOptions = Array.isArray(payload?.config?.statuses) && payload.config.statuses.length ? payload.config.statuses : this.statusOptions
    },
    clearRouteVehicleIntent () {
      const query = { ...(this.$route?.query || {}) }
      delete query.vehicleId
      delete query.open
      this.$router.replace({ name: 'maintenancelog', query }).catch(() => {})
    },
    applyRouteVehicleIntent () {
      const query = this.$route?.query || {}
      const openValue = String(query.open || '').toLowerCase()
      const shouldOpen = ['1', 'true', 'yes'].includes(openValue)
      const vehicleId = Number(query.vehicleId || 0)

      if (!shouldOpen || vehicleId <= 0) {
        return
      }

      const matchingOpenLog = this.logs.find((item) =>
        Number(item.vehicleId) === vehicleId && ['reported', 'approved', 'in_progress'].includes(item.status)
      )

      if (matchingOpenLog) {
        this.openEditDialog(matchingOpenLog)
      } else {
        this.openCreateDialog()
        this.edited.vehicleId = vehicleId
      }

      this.clearRouteVehicleIntent()
    },
    async loadBootstrap () {
      this.loading = true
      try {
        const response = await axios.get(this.apiUrl('getBootstrap'))
        const payload = response?.data || {}
        if (!payload.success) {
          throw new Error(payload.message || 'Failed to load maintenance records.')
        }
        this.normalizeBootstrap(payload)
        this.applyRouteVehicleIntent()
      } catch (error) {
        this.notify('Failed to load maintenance records.', 'error')
      } finally {
        this.loading = false
      }
    },
    resetFilters () {
      this.filters = {
        search: '',
        status: 'all',
        severity: 'all',
        issueType: 'all',
      }
    },
    openCreateDialog () {
      this.edited = defaultEdited()
      this.edited.loggedAt = this.currentLocalInputDateTime()
      this.edited.createdBy = this.prefillCreatedBy()
      this.dialog = true
    },
    openEditDialog (item) {
      this.edited = {
        id: item.id,
        vehicleId: item.vehicleId,
        issueType: item.issueType,
        severity: item.severity,
        status: item.status,
        loggedAt: this.toInputDateTime(item.loggedAt),
        description: item.description || '',
        assignedGarage: item.assignedGarage || '',
        estimatedReturnAt: this.toInputDateTime(item.estimatedReturnAt),
        actualReturnAt: this.toInputDateTime(item.actualReturnAt),
        estimatedCost: Number(item.estimatedCost || 0),
        partsCost: Number(item.partsCost || 0),
        labourCost: Number(item.labourCost || 0),
        finalCost: Number(item.finalCost || 0),
        mileage: item.mileage ?? '',
        createdBy: item.createdBy || this.prefillCreatedBy(),
      }
      this.dialog = true
    },
    async saveLog () {
      if (!this.canSave || this.saving) {
        return
      }

      this.saving = true
      try {
        const payload = {
          id: this.edited.id,
          vehicleId: Number(this.edited.vehicleId),
          issueType: this.edited.issueType,
          severity: this.edited.severity,
          status: this.edited.status,
          loggedAt: this.toApiDateTime(this.edited.loggedAt),
          description: this.edited.description,
          assignedGarage: this.edited.assignedGarage,
          estimatedReturnAt: this.toApiDateTime(this.edited.estimatedReturnAt),
          actualReturnAt: this.toApiDateTime(this.edited.actualReturnAt),
          estimatedCost: Number(this.edited.estimatedCost || 0),
          partsCost: Number(this.edited.partsCost || 0),
          labourCost: Number(this.edited.labourCost || 0),
          finalCost: Number(this.edited.finalCost || 0),
          mileage: this.edited.mileage === '' ? null : Number(this.edited.mileage),
          createdBy: this.edited.createdBy,
        }

        const response = await axios.post(this.apiUrl('saveLog'), payload)
        const result = response?.data || {}
        if (!result.success) {
          throw new Error(result.message || 'Failed to save maintenance record.')
        }

        if (Array.isArray(result.logs)) {
          this.logs = result.logs
        }
        if (result.summary) {
          this.summary = { ...defaultSummary, ...result.summary }
        }

        this.dialog = false
        this.notify('Maintenance record saved.')
      } catch (error) {
        this.notify(error?.response?.data?.message || 'Failed to save maintenance record.', 'error')
      } finally {
        this.saving = false
      }
    },
    async quickUpdateStatus (item, status) {
      if (!item || this.statusLoadingId) {
        return
      }

      this.statusLoadingId = item.id
      try {
        const response = await axios.post(this.apiUrl('setStatus'), {
          id: item.id,
          status,
        })
        const result = response?.data || {}
        if (!result.success) {
          throw new Error(result.message || 'Failed to update maintenance status.')
        }

        if (Array.isArray(result.logs)) {
          this.logs = result.logs
        }
        if (result.summary) {
          this.summary = { ...defaultSummary, ...result.summary }
        }

        this.notify(`Status updated to ${this.formatLabel(status)}.`)
      } catch (error) {
        this.notify(error?.response?.data?.message || 'Failed to update status.', 'error')
      } finally {
        this.statusLoadingId = null
      }
    },
  },
}
</script>
<style scoped>
.maintenance-page {
  padding: 24px;
}

.summary-card,
.panel-card {
  border-radius: 14px;
}

.summary-label {
  font-size: 0.85rem;
  color: rgba(0, 0, 0, 0.62);
}

.summary-value {
  font-size: 1.7rem;
  font-weight: 700;
  margin-top: 6px;
}

.table-wrap {
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 12px;
  overflow: auto;
}

.issue-snippet {
  max-width: 220px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

@media (max-width: 960px) {
  .maintenance-page {
    padding: 12px;
  }

  .issue-snippet {
    max-width: 160px;
  }
}
</style>
