<template>
  <v-container fluid class="allocation-page">
    <v-row dense class="mb-4">
      <v-col cols="12" md="3">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">Active Assignments</div>
            <div class="summary-value">{{ activeAssignments.length }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">Available Cars</div>
            <div class="summary-value">{{ availableVehicleOptions.length }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">Off-Road (Maintenance)</div>
            <div class="summary-value">{{ offRoadVehicles.length }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">Invoiced Value</div>
            <div class="summary-value">{{ formatCurrency(totalInvoicedAmount) }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-alert type="info" variant="tonal" class="mb-4">
      Company car usage is free for the first <strong>{{ freeHours }} hours</strong>.
      Charges begin from hour {{ freeHours + 1 }} using each vehicle's hourly rate.
    </v-alert>

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

    <v-alert v-if="offRoadVehicles.length" type="warning" variant="tonal" class="mb-4">
      <div class="font-weight-medium mb-1">Unavailable due to maintenance:</div>
      <div v-for="vehicle in offRoadVehicles" :key="vehicle.id" class="d-flex align-center flex-wrap ga-2 mb-1">
        <span class="text-body-2">
          {{ vehicle.regNumber }} ({{ formatLabel(vehicle.maintenanceStatus) }} - {{ formatLabel(vehicle.maintenanceIssueType || 'other') }})
          <span v-if="vehicle.maintenanceEta"> | ETA: {{ formatDateTime(vehicle.maintenanceEta) }}</span>
        </span>
        <v-btn size="x-small" variant="text" color="primary" @click="openMaintenanceForVehicle(vehicle)">
          Open Log
        </v-btn>
      </div>
    </v-alert>

    <v-row dense class="mb-4">
      <v-col cols="12" lg="5">
        <v-card variant="outlined" class="panel-card">
          <v-card-title class="d-flex align-center">
            Assign Company Car
            <v-spacer></v-spacer>
            <v-btn size="small" variant="outlined" color="primary" @click="carerDialog = true">Add Carer</v-btn>
            <v-btn size="small" variant="outlined" color="primary" class="ml-2" @click="vehicleDialog = true">Add Car</v-btn>
          </v-card-title>
          <v-card-text>
            <v-select
              v-model="assignForm.carerId"
              :items="carerOptions"
              item-title="label"
              item-value="value"
              label="Carer"
              variant="outlined"
              density="comfortable"
              class="mb-3"
            ></v-select>

            <v-select
              v-model="assignForm.vehicleId"
              :items="availableVehicleOptions"
              item-title="label"
              item-value="value"
              label="Company Car"
              variant="outlined"
              density="comfortable"
              class="mb-3"
            ></v-select>

            <v-text-field
              v-model="assignForm.assignedBy"
              label="Assigned By (Office Staff)"
              variant="outlined"
              density="comfortable"
              class="mb-3"
            ></v-text-field>

            <v-text-field
              v-model="assignForm.assignedAt"
              type="datetime-local"
              label="Assignment Date & Time"
              variant="outlined"
              density="comfortable"
              class="mb-3"
            ></v-text-field>

            <v-textarea
              v-model="assignForm.notes"
              label="Notes"
              variant="outlined"
              rows="2"
              counter="280"
              maxlength="280"
              class="mb-3"
            ></v-textarea>

            <div class="d-flex align-center ga-2">
              <v-chip size="small" variant="tonal" color="primary">
                Free period: {{ freeHours }}h
              </v-chip>
              <v-spacer></v-spacer>
              <v-btn
                color="success"
                :loading="savingAssignment"
                :disabled="!canAssign"
                @click="assignVehicle"
              >
                Assign Car
              </v-btn>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" lg="7">
        <v-card variant="outlined" class="panel-card">
          <v-card-title>Active Assignments</v-card-title>
          <v-card-text>
            <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-3"></v-progress-linear>
            <div class="table-wrap">
              <v-table density="comfortable">
                <thead>
                  <tr>
                    <th>Carer</th>
                    <th>Car</th>
                    <th>Assigned</th>
                    <th>Usage</th>
                    <th class="text-right">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in activeAssignments" :key="item.id">
                    <td>
                      <div class="font-weight-medium">{{ item.carerName }}</div>
                      <div class="text-caption text-medium-emphasis">{{ item.carerEmail }}</div>
                    </td>
                    <td>
                      <div class="font-weight-medium">{{ item.vehicleReg }}</div>
                      <div class="text-caption text-medium-emphasis">GBP {{ Number(item.hourlyRateLocked).toFixed(2) }}/h after free period</div>
                    </td>
                    <td>
                      <div class="text-caption">{{ formatDateTime(item.assignedAt) }}</div>
                      <div class="text-caption text-medium-emphasis">By {{ item.assignedBy }}</div>
                    </td>
                    <td>
                      <div class="text-caption">Total: {{ item.currentTotalHours }}h</div>
                      <div class="text-caption">Billable: {{ item.currentBillableHours }}h</div>
                      <div class="text-caption text-medium-emphasis">Current: {{ formatCurrency(item.currentSubtotal) }}</div>
                    </td>
                    <td class="text-right">
                      <v-btn size="small" color="primary" variant="outlined" @click="openReturnDialog(item)">
                        Return & Invoice
                      </v-btn>
                    </td>
                  </tr>
                  <tr v-if="!activeAssignments.length">
                    <td colspan="5" class="text-medium-emphasis">No active company car assignments.</td>
                  </tr>
                </tbody>
              </v-table>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-row dense>
      <v-col cols="12" lg="5">
        <v-card variant="outlined" class="panel-card">
          <v-card-title>Carer Directory</v-card-title>
          <v-card-text>
            <div class="table-wrap">
              <v-table density="comfortable">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Current</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="carer in carers" :key="carer.id">
                    <td>
                      <div class="font-weight-medium">{{ carer.fullName }}</div>
                      <div class="text-caption text-medium-emphasis">{{ carer.employeeCode || 'No code' }}</div>
                    </td>
                    <td>
                      <div class="text-caption">{{ carer.email }}</div>
                      <div class="text-caption text-medium-emphasis">{{ carer.phone || 'No phone' }}</div>
                    </td>
                    <td>
                      <v-chip size="x-small" :color="activeAssignmentCount(carer.id) ? 'warning' : 'success'" variant="tonal">
                        {{ activeAssignmentCount(carer.id) ? 'Has car' : 'No active car' }}
                      </v-chip>
                    </td>
                  </tr>
                  <tr v-if="!carers.length">
                    <td colspan="3" class="text-medium-emphasis">No carers yet.</td>
                  </tr>
                </tbody>
              </v-table>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" lg="7">
        <v-card variant="outlined" class="panel-card">
          <v-card-title>Invoice History</v-card-title>
          <v-card-text>
            <div class="table-wrap">
              <v-table density="comfortable">
                <thead>
                  <tr>
                    <th>Invoice</th>
                    <th>Carer</th>
                    <th>Usage</th>
                    <th>Total</th>
                    <th>Email</th>
                    <th class="text-right">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="invoice in invoices" :key="invoice.id">
                    <td>
                      <div class="font-weight-medium">{{ invoice.invoiceNumber }}</div>
                      <div class="text-caption text-medium-emphasis">{{ formatDateTime(invoice.issuedAt) }}</div>
                    </td>
                    <td>
                      <div class="font-weight-medium">{{ invoice.carerName }}</div>
                      <div class="text-caption text-medium-emphasis">{{ invoice.vehicleReg }}</div>
                    </td>
                    <td>
                      <div class="text-caption">Total: {{ invoice.totalHours }}h</div>
                      <div class="text-caption">Free: {{ invoice.freeHours }}h</div>
                      <div class="text-caption">Billable: {{ invoice.billableHours }}h</div>
                    </td>
                    <td>
                      <div class="font-weight-medium">{{ formatCurrency(invoice.totalAmount) }}</div>
                      <div class="text-caption text-medium-emphasis">Sub: {{ formatCurrency(invoice.subtotal) }}</div>
                    </td>
                    <td>
                      <v-chip size="x-small" :color="invoice.emailSent ? 'success' : 'warning'" variant="tonal">
                        {{ invoice.emailSent ? 'Sent' : 'Not sent' }}
                      </v-chip>
                    </td>
                    <td class="text-right">
                      <v-btn
                        size="small"
                        variant="text"
                        color="primary"
                        :loading="resendInvoiceId === invoice.id"
                        @click="resendInvoice(invoice)"
                      >
                        Resend
                      </v-btn>
                    </td>
                  </tr>
                  <tr v-if="!invoices.length">
                    <td colspan="6" class="text-medium-emphasis">No invoices generated yet.</td>
                  </tr>
                </tbody>
              </v-table>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-dialog v-model="carerDialog" max-width="520">
      <v-card>
        <v-card-title>Add / Update Carer</v-card-title>
        <v-card-text>
          <v-text-field v-model="newCarer.fullName" label="Full Name" variant="outlined" class="mb-2"></v-text-field>
          <v-text-field v-model="newCarer.email" label="Email" type="email" variant="outlined" class="mb-2"></v-text-field>
          <v-text-field v-model="newCarer.phone" label="Phone" variant="outlined" class="mb-2"></v-text-field>
          <v-text-field v-model="newCarer.employeeCode" label="Employee Code" variant="outlined"></v-text-field>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="carerDialog = false">Cancel</v-btn>
          <v-btn color="success" :loading="savingCarer" :disabled="!canSaveCarer" @click="saveCarer">Save Carer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="vehicleDialog" max-width="520">
      <v-card>
        <v-card-title>Add / Update Company Car</v-card-title>
        <v-card-text>
          <v-text-field v-model="newVehicle.regNumber" label="Registration" variant="outlined" class="mb-2"></v-text-field>
          <v-text-field v-model="newVehicle.make" label="Make" variant="outlined" class="mb-2"></v-text-field>
          <v-text-field v-model="newVehicle.model" label="Model" variant="outlined" class="mb-2"></v-text-field>
          <v-text-field
            v-model="newVehicle.hourlyRateAfterFree"
            label="Hourly Rate After Free Period"
            type="number"
            min="0"
            step="0.01"
            variant="outlined"
          ></v-text-field>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="vehicleDialog = false">Cancel</v-btn>
          <v-btn color="success" :loading="savingVehicle" :disabled="!canSaveVehicle" @click="saveVehicle">Save Car</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="returnDialog" max-width="560">
      <v-card>
        <v-card-title>Return Car & Generate Invoice</v-card-title>
        <v-card-text v-if="selectedAssignment">
          <div class="mb-2 font-weight-medium">{{ selectedAssignment.carerName }} | {{ selectedAssignment.vehicleReg }}</div>
          <v-text-field
            v-model="returnForm.returnedAt"
            type="datetime-local"
            label="Return Date & Time"
            variant="outlined"
            class="mb-2"
          ></v-text-field>
          <v-text-field
            v-model="returnForm.vatRate"
            type="number"
            min="0"
            max="1"
            step="0.01"
            label="VAT Rate (0 to 1)"
            variant="outlined"
            class="mb-2"
          ></v-text-field>
          <v-switch v-model="returnForm.sendEmail" label="Email invoice to carer" color="primary" hide-details class="mb-2"></v-switch>

          <v-alert type="info" variant="tonal" density="comfortable">
            <div>Total Hours: {{ returnPreview.totalHours }}h</div>
            <div>Free Hours: {{ returnPreview.freeHours }}h</div>
            <div>Billable Hours: {{ returnPreview.billableHours }}h</div>
            <div>Estimated Total: {{ formatCurrency(returnPreview.total) }}</div>
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="returnDialog = false">Cancel</v-btn>
          <v-btn color="success" :loading="processingReturn" :disabled="!canReturnAndInvoice" @click="returnAndInvoice">
            Confirm Return
          </v-btn>
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
const AUTH_STORAGE_KEY = 'facilitateCurrentUser'

export default {
  data: () => ({
    loading: false,
    savingAssignment: false,
    savingCarer: false,
    savingVehicle: false,
    savingConfig: false,
    processingReturn: false,
    resendInvoiceId: null,

    freeHours: 72,
    freeHoursDraft: 72,
    vatRate: 0,

    carers: [],
    vehicles: [],
    activeAssignments: [],
    invoices: [],

    assignForm: {
      carerId: null,
      vehicleId: null,
      assignedBy: '',
      assignedAt: '',
      notes: '',
    },

    carerDialog: false,
    newCarer: {
      fullName: '',
      email: '',
      phone: '',
      employeeCode: '',
    },

    vehicleDialog: false,
    newVehicle: {
      regNumber: '',
      make: '',
      model: '',
      hourlyRateAfterFree: 4.5,
    },

    returnDialog: false,
    selectedAssignment: null,
    returnForm: {
      returnedAt: '',
      sendEmail: true,
      vatRate: 0,
    },

    snackbar: {
      show: false,
      message: '',
      type: 'success',
    },
  }),
  computed: {
    activeVehicleIds () {
      return new Set(this.activeAssignments.map((item) => Number(item.vehicleId)))
    },
    offRoadVehicles () {
      return this.vehicles.filter((vehicle) => vehicle.isActive && Boolean(vehicle.isOffRoad))
    },
    carerOptions () {
      return this.carers
        .filter((carer) => carer.isActive)
        .map((carer) => ({
          value: carer.id,
          label: `${carer.fullName} (${carer.email})`,
        }))
    },
    availableVehicleOptions () {
      return this.vehicles
        .filter((vehicle) => vehicle.isActive && !vehicle.isOffRoad && !this.activeVehicleIds.has(Number(vehicle.id)))
        .map((vehicle) => ({
          value: vehicle.id,
          label: `${vehicle.regNumber} - ${vehicle.make} ${vehicle.model} | GBP ${Number(vehicle.hourlyRateAfterFree).toFixed(2)}/h`,
        }))
    },
    availableVehicleIds () {
      return new Set(this.availableVehicleOptions.map((item) => Number(item.value)))
    },
    canAssign () {
      return Boolean(
        this.assignForm.carerId &&
        this.assignForm.vehicleId &&
        this.availableVehicleIds.has(Number(this.assignForm.vehicleId)) &&
        this.assignForm.assignedBy.trim() !== '' &&
        this.assignForm.assignedAt !== ''
      )
    },
    canSaveFreeHours () {
      const parsed = Number(this.freeHoursDraft)
      return Number.isInteger(parsed) && parsed >= 1 && parsed <= 720 && parsed !== Number(this.freeHours)
    },
    canSaveCarer () {
      return this.newCarer.fullName.trim() !== '' && this.newCarer.email.trim() !== ''
    },
    canSaveVehicle () {
      return this.newVehicle.regNumber.trim() !== '' && Number(this.newVehicle.hourlyRateAfterFree) > 0
    },
    canReturnAndInvoice () {
      return Boolean(this.selectedAssignment && this.returnForm.returnedAt)
    },
    totalInvoicedAmount () {
      return this.invoices.reduce((sum, invoice) => sum + Number(invoice.totalAmount || 0), 0)
    },
    returnPreview () {
      if (!this.selectedAssignment) {
        return {
          totalHours: 0,
          freeHours: this.freeHours,
          billableHours: 0,
          total: 0,
        }
      }

      return this.calculateMetrics(
        this.selectedAssignment.assignedAt,
        this.toApiDateTime(this.returnForm.returnedAt),
        Number(this.selectedAssignment.freeHours || this.freeHours),
        Number(this.selectedAssignment.hourlyRateLocked || 0),
        Number(this.returnForm.vatRate || 0)
      )
    },
  },
  watch: {
    returnDialog (isOpen) {
      if (!isOpen) {
        this.selectedAssignment = null
        this.returnForm.returnedAt = ''
      }
    },
  },
  created () {
    this.assignForm.assignedAt = this.currentLocalInputDateTime()
    this.returnForm.returnedAt = this.currentLocalInputDateTime()
    this.prefillAssignedBy()
    this.loadBootstrap()
  },
  methods: {
    apiUrl (action) {
      return `${API_BASE}/carAllocation.php?action=${action}`
    },
    prefillAssignedBy () {
      try {
        const raw = localStorage.getItem(AUTH_STORAGE_KEY)
        const user = raw ? JSON.parse(raw) : null
        const fallback = user?.name || user?.username || user?.email || ''
        if (fallback && this.assignForm.assignedBy.trim() === '') {
          this.assignForm.assignedBy = fallback
        }
      } catch (error) {
        // Keep field editable even when user storage is unavailable.
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
        return ''
      }
      const normalized = raw.replace('T', ' ')
      return normalized.length === 16 ? `${normalized}:00` : normalized
    },
    formatDateTime (value) {
      const raw = String(value || '').trim()
      if (!raw) {
        return '-'
      }
      const iso = raw.includes('T') ? raw : raw.replace(' ', 'T')
      const parsed = new Date(iso)
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
    formatCurrency (value) {
      return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: 'GBP',
      }).format(Number(value || 0))
    },
    calculateMetrics (startAt, endAt, freeHours, hourlyRate, vatRate) {
      const startTime = new Date(String(startAt || '').replace(' ', 'T')).getTime()
      const endTime = new Date(String(endAt || '').replace(' ', 'T')).getTime()

      if (!Number.isFinite(startTime) || !Number.isFinite(endTime) || endTime <= startTime) {
        return {
          totalHours: 0,
          freeHours,
          billableHours: 0,
          subtotal: 0,
          vatAmount: 0,
          total: 0,
        }
      }

      const totalHours = Math.ceil((endTime - startTime) / (1000 * 60 * 60))
      const billableHours = Math.max(0, totalHours - freeHours)
      const subtotal = Number((billableHours * hourlyRate).toFixed(2))
      const safeVat = Math.max(0, Math.min(1, Number(vatRate || 0)))
      const vatAmount = Number((subtotal * safeVat).toFixed(2))
      const total = Number((subtotal + vatAmount).toFixed(2))

      return {
        totalHours,
        freeHours,
        billableHours,
        subtotal,
        vatAmount,
        total,
      }
    },
    activeAssignmentCount (carerId) {
      return this.activeAssignments.filter((item) => Number(item.carerId) === Number(carerId)).length
    },
    notify (message, type = 'success') {
      this.snackbar.message = message
      this.snackbar.type = type
      this.snackbar.show = true
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
    normalizeBootstrapData (payload) {
      this.freeHours = Number(payload?.config?.freeHours || 72)
      this.freeHoursDraft = this.freeHours
      this.vatRate = Number(payload?.config?.vatRate || 0)
      this.carers = Array.isArray(payload?.carers) ? payload.carers : []
      this.vehicles = Array.isArray(payload?.vehicles) ? payload.vehicles : []
      this.activeAssignments = Array.isArray(payload?.activeAssignments) ? payload.activeAssignments : []
      this.invoices = Array.isArray(payload?.invoices) ? payload.invoices : []

      if (this.assignForm.vehicleId && !this.availableVehicleIds.has(Number(this.assignForm.vehicleId))) {
        this.assignForm.vehicleId = null
      }
    },
    async loadBootstrap () {
      this.loading = true
      try {
        const response = await axios.get(this.apiUrl('getBootstrap'))
        const payload = response?.data || {}
        if (!payload.success) {
          throw new Error(payload.message || 'Failed to load records')
        }

        this.normalizeBootstrapData(payload)
      } catch (error) {
        this.notify('Failed to load car allocation records.', 'error')
      } finally {
        this.loading = false
      }
    },
    async saveCarer () {
      if (!this.canSaveCarer || this.savingCarer) {
        return
      }

      this.savingCarer = true
      try {
        const response = await axios.post(this.apiUrl('addCarer'), {
          fullName: this.newCarer.fullName,
          email: this.newCarer.email,
          phone: this.newCarer.phone,
          employeeCode: this.newCarer.employeeCode,
        })

        const payload = response?.data || {}
        if (!payload.success) {
          throw new Error(payload.message || 'Failed to save carer')
        }

        if (Array.isArray(payload.carers)) {
          this.carers = payload.carers
        } else {
          await this.loadBootstrap()
        }

        this.carerDialog = false
        this.newCarer = {
          fullName: '',
          email: '',
          phone: '',
          employeeCode: '',
        }
        this.notify('Carer saved successfully.')
      } catch (error) {
        this.notify(error?.response?.data?.message || 'Failed to save carer.', 'error')
      } finally {
        this.savingCarer = false
      }
    },
    async saveVehicle () {
      if (!this.canSaveVehicle || this.savingVehicle) {
        return
      }

      this.savingVehicle = true
      try {
        const response = await axios.post(this.apiUrl('addVehicle'), {
          regNumber: this.newVehicle.regNumber,
          make: this.newVehicle.make,
          model: this.newVehicle.model,
          hourlyRateAfterFree: Number(this.newVehicle.hourlyRateAfterFree || 0),
        })

        const payload = response?.data || {}
        if (!payload.success) {
          throw new Error(payload.message || 'Failed to save vehicle')
        }

        if (Array.isArray(payload.vehicles)) {
          this.vehicles = payload.vehicles
        } else {
          await this.loadBootstrap()
        }

        this.vehicleDialog = false
        this.newVehicle = {
          regNumber: '',
          make: '',
          model: '',
          hourlyRateAfterFree: 4.5,
        }
        this.notify('Company car saved successfully.')
      } catch (error) {
        this.notify(error?.response?.data?.message || 'Failed to save company car.', 'error')
      } finally {
        this.savingVehicle = false
      }
    },
    async assignVehicle () {
      if (!this.canAssign || this.savingAssignment) {
        return
      }

      this.savingAssignment = true
      try {
        const response = await axios.post(this.apiUrl('assignCar'), {
          carerId: Number(this.assignForm.carerId),
          vehicleId: Number(this.assignForm.vehicleId),
          assignedBy: this.assignForm.assignedBy,
          assignedAt: this.toApiDateTime(this.assignForm.assignedAt),
          notes: this.assignForm.notes,
          freeHours: this.freeHours,
        })

        const payload = response?.data || {}
        if (!payload.success) {
          throw new Error(payload.message || 'Failed to assign vehicle')
        }

        if (Array.isArray(payload.activeAssignments)) {
          this.activeAssignments = payload.activeAssignments
        }
        await this.loadBootstrap()

        this.assignForm.vehicleId = null
        this.assignForm.notes = ''
        this.assignForm.assignedAt = this.currentLocalInputDateTime()

        this.notify('Company car assigned successfully.')
      } catch (error) {
        this.notify(error?.response?.data?.message || 'Failed to assign company car.', 'error')
      } finally {
        this.savingAssignment = false
      }
    },
    openReturnDialog (assignment) {
      this.selectedAssignment = assignment
      this.returnForm.returnedAt = this.currentLocalInputDateTime()
      this.returnForm.sendEmail = true
      this.returnForm.vatRate = this.vatRate
      this.returnDialog = true
    },
    async returnAndInvoice () {
      if (!this.canReturnAndInvoice || this.processingReturn || !this.selectedAssignment) {
        return
      }

      this.processingReturn = true
      try {
        const response = await axios.post(this.apiUrl('returnAndInvoice'), {
          assignmentId: Number(this.selectedAssignment.id),
          returnedAt: this.toApiDateTime(this.returnForm.returnedAt),
          vatRate: Number(this.returnForm.vatRate || 0),
          sendEmail: Boolean(this.returnForm.sendEmail),
        })

        const payload = response?.data || {}
        if (!payload.success) {
          throw new Error(payload.message || 'Failed to close assignment')
        }

        if (Array.isArray(payload.activeAssignments)) {
          this.activeAssignments = payload.activeAssignments
        }
        if (Array.isArray(payload.invoices)) {
          this.invoices = payload.invoices
        }
        await this.loadBootstrap()

        const invoiceNo = payload?.invoice?.invoiceNumber ? ` (${payload.invoice.invoiceNumber})` : ''
        this.notify(`${payload.message || 'Invoice generated successfully.'}${invoiceNo}`)
        this.returnDialog = false
      } catch (error) {
        this.notify(error?.response?.data?.message || 'Failed to return car and generate invoice.', 'error')
      } finally {
        this.processingReturn = false
      }
    },
    async resendInvoice (invoice) {
      if (!invoice || this.resendInvoiceId) {
        return
      }

      this.resendInvoiceId = invoice.id
      try {
        const response = await axios.post(this.apiUrl('resendInvoiceEmail'), {
          invoiceId: Number(invoice.id),
          toEmail: invoice.emailTo,
        })

        const payload = response?.data || {}
        if (!payload.success) {
          throw new Error(payload.message || 'Failed to resend invoice email')
        }

        if (payload.invoice) {
          const targetIndex = this.invoices.findIndex((item) => Number(item.id) === Number(payload.invoice.id))
          if (targetIndex !== -1) {
            this.invoices.splice(targetIndex, 1, payload.invoice)
          }
        } else {
          await this.loadBootstrap()
        }

        this.notify(payload.message || 'Invoice email sent.')
      } catch (error) {
        this.notify(error?.response?.data?.message || 'Failed to resend invoice email.', 'error')
      } finally {
        this.resendInvoiceId = null
      }
    },
  },
}
</script>

<style scoped>
.allocation-page {
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
  font-size: 1.8rem;
  font-weight: 700;
  margin-top: 6px;
}

.table-wrap {
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 12px;
  overflow: auto;
}

@media (max-width: 960px) {
  .allocation-page {
    padding: 12px;
  }
}
</style>
