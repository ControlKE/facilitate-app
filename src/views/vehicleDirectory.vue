<template>
  <v-container fluid class="directory-page">
    <v-row dense class="mb-4">
      <v-col cols="12" sm="6" md="4" lg="2">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">Total Vehicles</div>
            <div class="summary-value">{{ summary.total }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" md="4" lg="2">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">Available</div>
            <div class="summary-value">{{ summary.available }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" md="4" lg="2">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">Assigned</div>
            <div class="summary-value">{{ summary.assigned }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" md="4" lg="2">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">Off-Road</div>
            <div class="summary-value">{{ summary.offRoad }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" md="4" lg="2">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">Inactive</div>
            <div class="summary-value">{{ summary.inactive }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-card variant="outlined" class="mb-4 panel-card">
      <v-card-text class="d-flex flex-wrap align-center ga-3">
        <div class="text-body-2">
          Free-period setting (hours):
        </div>
        <v-text-field
          v-model.number="freeHoursDraft"
          type="number"
          min="1"
          max="720"
          step="1"
          density="compact"
          variant="outlined"
          hide-details
          style="max-width: 150px;"
        ></v-text-field>
        <v-btn
          color="primary"
          variant="outlined"
          :disabled="!canSaveFreeHours"
          :loading="savingConfig"
          @click="saveFreeHoursSetting"
        >
          Save Free Period
        </v-btn>
        <v-chip size="small" variant="tonal" color="info">
          Applies to new assignments only
        </v-chip>
      </v-card-text>
    </v-card>

    <v-card variant="outlined" class="mb-4 panel-card">
      <v-card-text class="pb-2">
        <v-row dense>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="filters.search"
              label="Search by reg, make, model, assignee..."
              density="compact"
              variant="outlined"
              hide-details
            ></v-text-field>
          </v-col>
          <v-col cols="6" md="3">
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
          <v-col cols="6" md="3">
            <v-select
              v-model="filters.make"
              :items="makeFilterOptions"
              item-title="label"
              item-value="value"
              label="Make"
              density="compact"
              variant="outlined"
              hide-details
            ></v-select>
          </v-col>
          <v-col cols="12" md="2" class="d-flex ga-2 justify-end">
            <v-btn variant="outlined" color="primary" @click="resetFilters">Clear</v-btn>
            <v-btn variant="outlined" color="primary" :loading="loading" @click="loadBootstrap">Refresh</v-btn>
            <v-btn color="success" @click="openCreateDialog">Add</v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-card variant="outlined" class="panel-card">
      <v-card-title class="d-flex align-center">
        Vehicle Directory
        <v-spacer></v-spacer>
        <v-chip size="small" variant="outlined">{{ filteredVehicles.length }} records</v-chip>
      </v-card-title>
      <v-card-text>
        <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-3"></v-progress-linear>
        <div class="table-wrap">
          <v-table density="comfortable">
            <thead>
              <tr>
                <th>Registration</th>
                <th>Vehicle</th>
                <th>Rate</th>
                <th>Status</th>
                <th>Assigned To</th>
                <th>Maintenance</th>
                <th>Updated</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="vehicle in filteredVehicles" :key="vehicle.id">
                <td>
                  <div class="font-weight-medium">{{ vehicle.regNumber }}</div>
                </td>
                <td>
                  <div class="font-weight-medium">{{ vehicle.make || 'N/A' }} {{ vehicle.model || '' }}</div>
                </td>
                <td>{{ formatCurrency(vehicle.hourlyRateAfterFree) }} /h</td>
                <td>
                  <v-chip size="x-small" :color="statusColor(vehicleStatus(vehicle))" variant="tonal">
                    {{ statusLabel(vehicleStatus(vehicle)) }}
                  </v-chip>
                </td>
                <td>
                  <div v-if="vehicle.isAssigned">
                    <div class="text-body-2">{{ vehicle.assignedTo || 'Assigned' }}</div>
                    <div class="text-caption text-medium-emphasis">
                      {{ vehicle.assignedAt ? formatDateTime(vehicle.assignedAt) : '' }}
                    </div>
                  </div>
                  <span v-else class="text-medium-emphasis">-</span>
                </td>
                <td>
                  <div v-if="vehicle.isOffRoad">
                    <v-chip size="x-small" color="warning" variant="tonal" class="mb-1">
                      {{ formatLabel(vehicle.maintenanceStatus || 'off_road') }}
                    </v-chip>
                    <div class="text-caption text-medium-emphasis">
                      {{ formatLabel(vehicle.maintenanceIssueType || 'other') }}
                      <span v-if="vehicle.maintenanceEta"> | ETA {{ formatDateTime(vehicle.maintenanceEta) }}</span>
                    </div>
                  </div>
                  <span v-else class="text-medium-emphasis">-</span>
                </td>
                <td class="text-caption">{{ formatDateTime(vehicle.updatedAt) }}</td>
                <td class="text-right">
                  <v-btn size="small" variant="text" color="primary" @click="openEditDialog(vehicle)">Edit</v-btn>
                  <v-btn size="small" variant="text" color="primary" @click="openMaintenanceForVehicle(vehicle)">Maintenance</v-btn>
                </td>
              </tr>
              <tr v-if="!filteredVehicles.length">
                <td colspan="8" class="text-medium-emphasis">No vehicles found.</td>
              </tr>
            </tbody>
          </v-table>
        </div>
      </v-card-text>
    </v-card>

    <v-dialog v-model="dialog" max-width="560">
      <v-card>
        <v-card-title>{{ dialogTitle }}</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="edited.regNumber"
            label="Registration Number"
            variant="outlined"
            class="mb-3"
          ></v-text-field>
          <v-text-field
            v-model="edited.make"
            label="Make"
            variant="outlined"
            class="mb-3"
          ></v-text-field>
          <v-text-field
            v-model="edited.model"
            label="Model"
            variant="outlined"
            class="mb-3"
          ></v-text-field>
          <v-text-field
            v-model.number="edited.hourlyRateAfterFree"
            label="Hourly Rate After Free Period"
            type="number"
            min="0"
            step="0.01"
            variant="outlined"
            class="mb-2"
          ></v-text-field>
          <v-switch
            v-model="edited.isActive"
            color="primary"
            label="Vehicle is active for assignment"
            hide-details
            class="mb-1"
          ></v-switch>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
          <v-btn color="success" :loading="saving" :disabled="!canSaveVehicle" @click="saveVehicle">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

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

const defaultEdited = () => ({
  id: null,
  regNumber: '',
  make: '',
  model: '',
  hourlyRateAfterFree: 4.5,
  isActive: true,
})

export default {
  data: () => ({
    loading: false,
    saving: false,
    savingConfig: false,

    vehicles: [],
    freeHours: 72,
    freeHoursDraft: 72,

    filters: {
      search: '',
      status: 'all',
      make: 'all',
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
    summary () {
      return {
        total: this.vehicles.length,
        available: this.vehicles.filter((item) => this.vehicleStatus(item) === 'available').length,
        assigned: this.vehicles.filter((item) => this.vehicleStatus(item) === 'assigned').length,
        offRoad: this.vehicles.filter((item) => this.vehicleStatus(item) === 'off_road').length,
        inactive: this.vehicles.filter((item) => this.vehicleStatus(item) === 'inactive').length,
      }
    },
    statusFilterOptions () {
      return [
        { label: 'All Statuses', value: 'all' },
        { label: 'Available', value: 'available' },
        { label: 'Assigned', value: 'assigned' },
        { label: 'Off-Road', value: 'off_road' },
        { label: 'Inactive', value: 'inactive' },
      ]
    },
    makeFilterOptions () {
      const uniqueMakes = [...new Set(this.vehicles.map((item) => String(item.make || '').trim()).filter(Boolean))]
      return [
        { label: 'All Makes', value: 'all' },
        ...uniqueMakes.map((make) => ({ label: make, value: make })),
      ]
    },
    filteredVehicles () {
      const query = String(this.filters.search || '').trim().toLowerCase()
      return this.vehicles.filter((item) => {
        const status = this.vehicleStatus(item)
        if (this.filters.status !== 'all' && status !== this.filters.status) {
          return false
        }
        if (this.filters.make !== 'all' && String(item.make || '').trim() !== this.filters.make) {
          return false
        }
        if (!query) {
          return true
        }

        const haystack = [
          item.regNumber,
          item.make,
          item.model,
          item.assignedTo,
          item.assignedToEmail,
          item.maintenanceIssueType,
        ]
          .join(' ')
          .toLowerCase()

        return haystack.includes(query)
      })
    },
    dialogTitle () {
      return this.edited.id ? 'Edit Vehicle' : 'Add Vehicle'
    },
    canSaveVehicle () {
      return this.edited.regNumber.trim() !== '' && Number(this.edited.hourlyRateAfterFree) > 0
    },
    canSaveFreeHours () {
      const parsed = Number(this.freeHoursDraft)
      return Number.isInteger(parsed) && parsed >= 1 && parsed <= 720 && parsed !== Number(this.freeHours)
    },
  },
  created () {
    this.loadBootstrap()
  },
  methods: {
    apiUrl (action) {
      return `${API_BASE}/carAllocation.php?action=${action}`
    },
    vehicleStatus (vehicle) {
      if (!vehicle?.isActive) {
        return 'inactive'
      }
      if (vehicle?.isOffRoad) {
        return 'off_road'
      }
      if (vehicle?.isAssigned) {
        return 'assigned'
      }
      return 'available'
    },
    statusLabel (status) {
      const map = {
        available: 'Available',
        assigned: 'Assigned',
        off_road: 'Off-Road',
        inactive: 'Inactive',
      }
      return map[status] || 'Unknown'
    },
    statusColor (status) {
      const map = {
        available: 'success',
        assigned: 'warning',
        off_road: 'error',
        inactive: 'grey',
      }
      return map[status] || 'grey'
    },
    formatLabel (value) {
      return String(value || '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase())
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
    notify (message, type = 'success') {
      this.snackbar.message = message
      this.snackbar.type = type
      this.snackbar.show = true
    },
    normalizeBootstrapData (payload = {}) {
      this.vehicles = Array.isArray(payload.vehicles) ? payload.vehicles : []
      this.freeHours = Number(payload?.config?.freeHours || 72)
      this.freeHoursDraft = this.freeHours
    },
    async loadBootstrap () {
      this.loading = true
      try {
        const response = await axios.get(this.apiUrl('getBootstrap'))
        const payload = response?.data || {}
        if (!payload.success) {
          throw new Error(payload.message || 'Failed to load vehicle directory.')
        }
        this.normalizeBootstrapData(payload)
      } catch (error) {
        this.notify(error?.response?.data?.message || 'Failed to load vehicle directory.', 'error')
      } finally {
        this.loading = false
      }
    },
    resetFilters () {
      this.filters = {
        search: '',
        status: 'all',
        make: 'all',
      }
    },
    openCreateDialog () {
      this.edited = defaultEdited()
      this.dialog = true
    },
    openEditDialog (vehicle) {
      this.edited = {
        id: vehicle.id,
        regNumber: vehicle.regNumber || '',
        make: vehicle.make || '',
        model: vehicle.model || '',
        hourlyRateAfterFree: Number(vehicle.hourlyRateAfterFree || 0),
        isActive: Boolean(vehicle.isActive),
      }
      this.dialog = true
    },
    async saveVehicle () {
      if (!this.canSaveVehicle || this.saving) {
        return
      }

      this.saving = true
      try {
        const response = await axios.post(this.apiUrl('addVehicle'), {
          id: this.edited.id,
          regNumber: this.edited.regNumber,
          make: this.edited.make,
          model: this.edited.model,
          hourlyRateAfterFree: Number(this.edited.hourlyRateAfterFree || 0),
          isActive: Boolean(this.edited.isActive),
        })
        const payload = response?.data || {}
        if (!payload.success) {
          throw new Error(payload.message || 'Failed to save vehicle.')
        }

        if (Array.isArray(payload.vehicles)) {
          this.vehicles = payload.vehicles
        } else {
          await this.loadBootstrap()
        }

        this.dialog = false
        this.notify('Vehicle saved successfully.')
      } catch (error) {
        this.notify(error?.response?.data?.message || 'Failed to save vehicle.', 'error')
      } finally {
        this.saving = false
      }
    },
    async saveFreeHoursSetting () {
      if (!this.canSaveFreeHours || this.savingConfig) {
        return
      }

      this.savingConfig = true
      try {
        const response = await axios.post(this.apiUrl('updateConfig'), {
          freeHours: Number(this.freeHoursDraft),
        })
        const payload = response?.data || {}
        if (!payload.success) {
          throw new Error(payload.message || 'Failed to save free-period setting.')
        }

        this.freeHours = Number(payload?.config?.freeHours || this.freeHoursDraft)
        this.freeHoursDraft = this.freeHours
        this.notify(`Free period updated to ${this.freeHours} hours.`)
      } catch (error) {
        this.notify(error?.response?.data?.message || 'Failed to update free period.', 'error')
      } finally {
        this.savingConfig = false
      }
    },
    openMaintenanceForVehicle (vehicle) {
      const vehicleId = Number(vehicle?.id || 0)
      if (vehicleId <= 0) {
        return
      }

      this.$router.push({
        name: 'maintenancelog',
        query: {
          vehicleId: String(vehicleId),
          open: '1',
        },
      })
    },
  },
}
</script>

<style scoped>
.directory-page {
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

@media (max-width: 960px) {
  .directory-page {
    padding: 12px;
  }
}
</style>
