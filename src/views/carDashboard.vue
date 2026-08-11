<template>
  <v-container fluid class="car-dashboard-page">
    <v-row dense class="mb-4">
      <v-col cols="12" sm="6" md="4" lg="2">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">Fleet (Active)</div>
            <div class="summary-value">{{ activeFleetCount }}</div>
            <div class="summary-sub">{{ totalVehicles }} total vehicles</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" md="4" lg="2">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">Available Now</div>
            <div class="summary-value">{{ availableVehicles.length }}</div>
            <div class="summary-sub">{{ availabilityRate }}% availability</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" md="4" lg="2">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">Assigned</div>
            <div class="summary-value">{{ assignedVehicles.length }}</div>
            <div class="summary-sub">{{ utilizationRate }}% utilization</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" md="4" lg="2">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">Off-Road</div>
            <div class="summary-value">{{ offRoadVehicles.length }}</div>
            <div class="summary-sub">{{ maintenanceSummaryView.openJobs }} open jobs</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" md="4" lg="2">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">This Month Invoiced</div>
            <div class="summary-value money">{{ formatCurrency(thisMonthInvoiced) }}</div>
            <div class="summary-sub" :class="invoicedDelta >= 0 ? 'text-success' : 'text-error'">
              {{ invoicedDelta >= 0 ? '+' : '' }}{{ formatCurrency(invoicedDelta) }} vs last month
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" md="4" lg="2">
        <v-card variant="outlined" class="summary-card">
          <v-card-text>
            <div class="summary-label">Maintenance Cost (Month)</div>
            <div class="summary-value money">{{ formatCurrency(maintenanceSummaryView.thisMonthCost) }}</div>
            <div class="summary-sub">Live exposure: {{ formatCurrency(currentExposure) }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-card variant="outlined" class="mb-4 panel-card">
      <v-card-text>
        <v-row dense>
          <v-col cols="12" sm="6" md="3">
            <v-text-field
              v-model="filters.startDate"
              type="date"
              label="From Date"
              density="compact"
              variant="outlined"
              hide-details
            ></v-text-field>
          </v-col>
          <v-col cols="12" sm="6" md="3">
            <v-text-field
              v-model="filters.endDate"
              type="date"
              label="To Date"
              density="compact"
              variant="outlined"
              hide-details
            ></v-text-field>
          </v-col>
          <v-col cols="12" sm="6" md="3">
            <v-select
              v-model="filters.vehicleId"
              :items="vehicleFilterOptions"
              item-title="label"
              item-value="value"
              label="Vehicle"
              density="compact"
              variant="outlined"
              hide-details
            ></v-select>
          </v-col>
          <v-col cols="12" sm="6" md="3">
            <v-select
              v-model="filters.carerId"
              :items="carerFilterOptions"
              item-title="label"
              item-value="value"
              label="Carer"
              density="compact"
              variant="outlined"
              hide-details
            ></v-select>
          </v-col>
        </v-row>

        <div class="d-flex flex-wrap align-center ga-2 mt-3">
          <v-chip size="small" variant="tonal" color="primary">Filtered assignments: {{ filteredAssignments.length }}</v-chip>
          <v-chip size="small" variant="tonal" color="primary">Filtered invoices: {{ filteredInvoices.length }}</v-chip>
          <v-chip size="small" variant="tonal" color="primary">Filtered maintenance: {{ filteredMaintenanceLogs.length }}</v-chip>
          <v-spacer></v-spacer>
          <v-btn variant="text" color="primary" @click="setThisMonth">This Month</v-btn>
          <v-btn variant="outlined" color="primary" @click="resetFilters">Clear Filters</v-btn>
        </div>
      </v-card-text>
    </v-card>

    <v-card variant="outlined" class="mb-4 panel-card">
      <v-card-text class="d-flex flex-wrap align-center ga-2">
        <v-chip size="small" variant="tonal" color="info">Free period: {{ freeHours }}h</v-chip>
        <v-chip size="small" variant="tonal" color="warning">Over free-period: {{ overdueAssignments.length }}</v-chip>
        <v-chip size="small" variant="tonal" color="primary">Due soon: {{ dueSoonAssignments.length }}</v-chip>
        <v-chip size="small" variant="tonal" color="error">Overdue maintenance: {{ maintenanceSummaryView.overdue }}</v-chip>
        <v-chip v-if="lastSyncedAt" size="small" variant="outlined">Updated {{ formatDateTime(lastSyncedAt) }}</v-chip>
        <v-spacer></v-spacer>
        <v-btn variant="outlined" color="primary" :loading="loading" @click="loadDashboard">Refresh</v-btn>
        <v-btn variant="outlined" color="primary" @click="goTo('vehicledirectory')">Vehicle Directory</v-btn>
        <v-btn variant="outlined" color="primary" @click="goTo('maintenancelog')">Maintenance Log</v-btn>
        <v-btn color="success" @click="goTo('carallocation')">Open Allocation</v-btn>
      </v-card-text>
    </v-card>

    <v-row dense class="mb-4">
      <v-col cols="12" xl="7">
        <v-card variant="outlined" class="panel-card fill-height">
          <v-card-title class="d-flex align-center">
            Fleet Utilization & Revenue Trend
            <v-spacer></v-spacer>
            <v-chip size="small" variant="outlined">{{ filteredInvoices.length }} invoices tracked</v-chip>
          </v-card-title>
          <v-card-text>
            <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-4"></v-progress-linear>

            <div class="kpi-grid mb-4">
              <div class="kpi-item">
                <div class="kpi-title">Availability</div>
                <v-progress-linear :model-value="availabilityRate" color="success" rounded height="12"></v-progress-linear>
                <div class="kpi-value">{{ availabilityRate }}%</div>
              </div>
              <div class="kpi-item">
                <div class="kpi-title">Utilization</div>
                <v-progress-linear :model-value="utilizationRate" color="primary" rounded height="12"></v-progress-linear>
                <div class="kpi-value">{{ utilizationRate }}%</div>
              </div>
              <div class="kpi-item">
                <div class="kpi-title">Off-Road Share</div>
                <v-progress-linear :model-value="offRoadRate" color="error" rounded height="12"></v-progress-linear>
                <div class="kpi-value">{{ offRoadRate }}%</div>
              </div>
            </div>

            <div class="trend-wrap">
              <div class="trend-title mb-2">Invoice trend (last 6 months)</div>
              <div v-for="bucket in invoiceTrend" :key="bucket.key" class="trend-row">
                <div class="trend-label">{{ bucket.label }}</div>
                <div class="trend-track">
                  <div class="trend-fill" :style="{ width: `${bucket.percent}%` }"></div>
                </div>
                <div class="trend-value">{{ formatCurrency(bucket.total) }}</div>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" xl="5">
        <v-card variant="outlined" class="panel-card fill-height">
          <v-card-title class="d-flex align-center">
            Live Alerts
            <v-spacer></v-spacer>
            <v-chip size="small" variant="outlined">{{ liveAlertCount }} alerts</v-chip>
          </v-card-title>
          <v-card-text>
            <v-alert v-if="!liveAlertCount" type="success" variant="tonal" density="comfortable" class="mb-3">
              No urgent transport alerts right now.
            </v-alert>

            <v-alert
              v-if="overdueAssignments.length"
              type="warning"
              variant="tonal"
              density="comfortable"
              class="mb-3"
            >
              {{ overdueAssignments.length }} assignment(s) exceeded free period.
            </v-alert>

            <div v-if="overdueAssignments.length" class="mini-list mb-3">
              <div v-for="item in overdueAssignments.slice(0, 5)" :key="item.id" class="mini-list-item">
                <div>
                  <div class="font-weight-medium">{{ item.carerName }} - {{ item.vehicleReg }}</div>
                  <div class="text-caption text-medium-emphasis">
                    Billable {{ item.currentBillableHours }}h | Running {{ formatCurrency(item.currentSubtotal) }}
                  </div>
                </div>
                <v-btn size="x-small" variant="text" color="primary" @click="goTo('carallocation')">Open</v-btn>
              </div>
            </div>

            <v-alert
              v-if="offRoadVehicles.length"
              type="error"
              variant="tonal"
              density="comfortable"
              class="mb-3"
            >
              {{ offRoadVehicles.length }} vehicle(s) are currently off-road.
            </v-alert>

            <div v-if="offRoadVehicles.length" class="mini-list">
              <div v-for="vehicle in offRoadVehicles.slice(0, 5)" :key="vehicle.id" class="mini-list-item">
                <div>
                  <div class="font-weight-medium">{{ vehicle.regNumber }} ({{ vehicle.make }} {{ vehicle.model }})</div>
                  <div class="text-caption text-medium-emphasis">
                    {{ formatLabel(vehicle.maintenanceStatus || 'off_road') }}
                    <span v-if="vehicle.maintenanceEta"> | ETA {{ formatDateTime(vehicle.maintenanceEta) }}</span>
                  </div>
                </div>
                <v-btn size="x-small" variant="text" color="primary" @click="openMaintenanceForVehicle(vehicle)">Log</v-btn>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-row dense>
      <v-col cols="12" xl="7">
        <v-card variant="outlined" class="panel-card">
          <v-card-title class="d-flex align-center">
            Active Assignment Watchlist
            <v-spacer></v-spacer>
            <v-chip size="small" variant="outlined">{{ filteredAssignments.length }} active</v-chip>
          </v-card-title>
          <v-card-text>
            <div class="table-wrap">
              <v-table density="comfortable">
                <thead>
                  <tr>
                    <th>Carer</th>
                    <th>Vehicle</th>
                    <th>Assigned</th>
                    <th>Usage</th>
                    <th>Risk</th>
                    <th class="text-right">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in sortedAssignments" :key="item.id">
                    <td>
                      <div class="font-weight-medium">{{ item.carerName }}</div>
                      <div class="text-caption text-medium-emphasis">{{ item.carerEmail }}</div>
                    </td>
                    <td>
                      <div class="font-weight-medium">{{ item.vehicleReg }}</div>
                      <div class="text-caption text-medium-emphasis">GBP {{ Number(item.hourlyRateLocked).toFixed(2) }}/h</div>
                    </td>
                    <td class="text-caption">{{ formatDateTime(item.assignedAt) }}</td>
                    <td>
                      <div class="text-caption">Total: {{ item.currentTotalHours }}h</div>
                      <div class="text-caption">Billable: {{ item.currentBillableHours }}h</div>
                      <div class="text-caption text-medium-emphasis">{{ formatCurrency(item.currentSubtotal) }}</div>
                    </td>
                    <td>
                      <v-chip
                        size="x-small"
                        :color="assignmentRiskColor(item)"
                        variant="tonal"
                      >
                        {{ assignmentRiskLabel(item) }}
                      </v-chip>
                    </td>
                    <td class="text-right">
                      <v-btn size="small" variant="text" color="primary" @click="goTo('carallocation')">Manage</v-btn>
                    </td>
                  </tr>
                  <tr v-if="!filteredAssignments.length">
                    <td colspan="6" class="text-medium-emphasis">No active assignments.</td>
                  </tr>
                </tbody>
              </v-table>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" xl="5">
        <v-card variant="outlined" class="panel-card mb-4">
          <v-card-title class="d-flex align-center">
            Recent Invoices
            <v-spacer></v-spacer>
            <v-chip size="small" variant="outlined">{{ recentInvoices.length }} shown</v-chip>
          </v-card-title>
          <v-card-text>
            <div class="table-wrap">
              <v-table density="comfortable">
                <thead>
                  <tr>
                    <th>Invoice</th>
                    <th>Carer</th>
                    <th>Total</th>
                    <th>Email</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="invoice in recentInvoices" :key="invoice.id">
                    <td>
                      <div class="font-weight-medium">{{ invoice.invoiceNumber }}</div>
                      <div class="text-caption text-medium-emphasis">{{ formatDateTime(invoice.issuedAt) }}</div>
                    </td>
                    <td>
                      <div class="font-weight-medium">{{ invoice.carerName }}</div>
                      <div class="text-caption text-medium-emphasis">{{ invoice.vehicleReg }}</div>
                    </td>
                    <td>{{ formatCurrency(invoice.totalAmount) }}</td>
                    <td>
                      <v-chip size="x-small" :color="invoice.emailSent ? 'success' : 'warning'" variant="tonal">
                        {{ invoice.emailSent ? 'Sent' : 'Not sent' }}
                      </v-chip>
                    </td>
                  </tr>
                  <tr v-if="!recentInvoices.length">
                    <td colspan="4" class="text-medium-emphasis">No invoices yet.</td>
                  </tr>
                </tbody>
              </v-table>
            </div>
          </v-card-text>
        </v-card>

        <v-card variant="outlined" class="panel-card">
          <v-card-title>Top Carers by Invoice Value</v-card-title>
          <v-card-text>
            <div v-if="topCarersByInvoice.length" class="mini-list">
              <div v-for="carer in topCarersByInvoice" :key="carer.name" class="mini-list-item">
                <div>
                  <div class="font-weight-medium">{{ carer.name }}</div>
                  <div class="text-caption text-medium-emphasis">{{ carer.count }} invoice(s)</div>
                </div>
                <div class="font-weight-medium">{{ formatCurrency(carer.total) }}</div>
              </div>
            </div>
            <div v-else class="text-medium-emphasis">No invoice data to rank yet.</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

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

const defaultMaintenanceSummary = {
  openJobs: 0,
  offRoadVehicles: 0,
  overdue: 0,
  thisMonthCost: 0,
}

export default {
  data: () => ({
    loading: false,
    freeHours: 72,

    carers: [],
    vehicles: [],
    activeAssignments: [],
    invoices: [],

    maintenanceLogs: [],
    maintenanceSummary: { ...defaultMaintenanceSummary },

    filters: {
      startDate: '',
      endDate: '',
      vehicleId: 'all',
      carerId: 'all',
    },

    lastSyncedAt: null,

    snackbar: {
      show: false,
      message: '',
      type: 'success',
    },
  }),
  computed: {
    selectedVehicleId () {
      const value = Number(this.filters.vehicleId)
      return Number.isFinite(value) && value > 0 ? value : null
    },
    selectedCarerId () {
      const value = Number(this.filters.carerId)
      return Number.isFinite(value) && value > 0 ? value : null
    },
    hasDateFilter () {
      return Boolean(String(this.filters.startDate || '').trim() || String(this.filters.endDate || '').trim())
    },
    vehicleFilterOptions () {
      const options = this.vehicles
        .map((vehicle) => ({
          value: vehicle.id,
          label: `${vehicle.regNumber} - ${vehicle.make || ''} ${vehicle.model || ''}`.trim(),
        }))
        .sort((a, b) => a.label.localeCompare(b.label))

      return [{ value: 'all', label: 'All vehicles' }, ...options]
    },
    carerFilterOptions () {
      const fromCarers = this.carers
        .filter((carer) => carer.isActive)
        .map((carer) => ({
          value: carer.id,
          label: `${carer.fullName} (${carer.email})`,
        }))
        .sort((a, b) => a.label.localeCompare(b.label))

      if (fromCarers.length) {
        return [{ value: 'all', label: 'All carers' }, ...fromCarers]
      }

      const fallbackMap = {}
      this.activeAssignments.forEach((item) => {
        const id = Number(item.carerId || 0)
        if (id > 0 && !fallbackMap[id]) {
          fallbackMap[id] = {
            value: id,
            label: `${item.carerName || 'Unknown carer'} (${item.carerEmail || 'No email'})`,
          }
        }
      })
      this.invoices.forEach((item) => {
        const id = Number(item.carerId || 0)
        if (id > 0 && !fallbackMap[id]) {
          fallbackMap[id] = {
            value: id,
            label: `${item.carerName || 'Unknown carer'}`,
          }
        }
      })

      const fallback = Object.values(fallbackMap).sort((a, b) => a.label.localeCompare(b.label))
      return [{ value: 'all', label: 'All carers' }, ...fallback]
    },
    filteredAssignments () {
      return this.activeAssignments.filter((item) => {
        if (this.selectedVehicleId && Number(item.vehicleId) !== this.selectedVehicleId) {
          return false
        }
        if (this.selectedCarerId && Number(item.carerId) !== this.selectedCarerId) {
          return false
        }
        return this.isWithinSelectedDateRange(item.assignedAt)
      })
    },
    filteredInvoices () {
      return this.invoices.filter((invoice) => {
        if (this.selectedVehicleId && Number(invoice.vehicleId) !== this.selectedVehicleId) {
          return false
        }
        if (this.selectedCarerId && Number(invoice.carerId) !== this.selectedCarerId) {
          return false
        }
        return this.isWithinSelectedDateRange(invoice.issuedAt)
      })
    },
    carerScopedVehicleIds () {
      if (!this.selectedCarerId) {
        return null
      }

      const ids = new Set()
      this.filteredAssignments.forEach((item) => {
        const vehicleId = Number(item.vehicleId || 0)
        if (vehicleId > 0) {
          ids.add(vehicleId)
        }
      })
      this.filteredInvoices.forEach((item) => {
        const vehicleId = Number(item.vehicleId || 0)
        if (vehicleId > 0) {
          ids.add(vehicleId)
        }
      })
      return ids
    },
    filteredMaintenanceLogs () {
      if (this.selectedCarerId && this.carerScopedVehicleIds && !this.carerScopedVehicleIds.size) {
        return []
      }

      return this.maintenanceLogs.filter((log) => {
        const vehicleId = Number(log.vehicleId || 0)
        if (this.selectedVehicleId && vehicleId !== this.selectedVehicleId) {
          return false
        }
        if (this.selectedCarerId && this.carerScopedVehicleIds && !this.carerScopedVehicleIds.has(vehicleId)) {
          return false
        }
        return this.isWithinSelectedDateRange(log.loggedAt)
      })
    },
    maintenanceSummaryView () {
      if (!this.selectedVehicleId && !this.selectedCarerId && !this.hasDateFilter) {
        return this.maintenanceSummary
      }

      const logs = this.filteredMaintenanceLogs
      const now = Date.now()
      const monthStart = new Date(new Date().getFullYear(), new Date().getMonth(), 1).getTime()
      const monthEnd = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0, 23, 59, 59, 999).getTime()
      const openStatuses = ['reported', 'approved', 'in_progress']

      let openJobs = 0
      let overdue = 0
      let thisMonthCost = 0
      const offRoadVehicles = new Set()

      logs.forEach((log) => {
        const status = String(log.status || '')
        const vehicleId = Number(log.vehicleId || 0)
        const effectiveFinalCost = Number(log.effectiveFinalCost || 0)

        if (openStatuses.includes(status)) {
          openJobs += 1
        }

        if (['approved', 'in_progress'].includes(status) && vehicleId > 0) {
          offRoadVehicles.add(vehicleId)
        }

        if (openStatuses.includes(status) && log.estimatedReturnAt) {
          const eta = this.toTimestamp(log.estimatedReturnAt)
          if (Number.isFinite(eta) && eta < now) {
            overdue += 1
          }
        }

        if (status === 'completed') {
          const costTime = this.toTimestamp(log.actualReturnAt || log.updatedAt || log.loggedAt)
          if (Number.isFinite(costTime) && costTime >= monthStart && costTime <= monthEnd) {
            thisMonthCost += effectiveFinalCost
          }
        }
      })

      return {
        openJobs,
        offRoadVehicles: offRoadVehicles.size,
        overdue,
        thisMonthCost: Number(thisMonthCost.toFixed(2)),
      }
    },
    filteredVehicles () {
      let rows = [...this.vehicles]

      if (this.selectedVehicleId) {
        rows = rows.filter((vehicle) => Number(vehicle.id) === this.selectedVehicleId)
      }

      if (this.selectedCarerId || this.hasDateFilter) {
        const activityVehicleIds = new Set([
          ...this.filteredAssignments.map((item) => Number(item.vehicleId || 0)),
          ...this.filteredInvoices.map((item) => Number(item.vehicleId || 0)),
          ...this.filteredMaintenanceLogs.map((item) => Number(item.vehicleId || 0)),
        ].filter((id) => id > 0))

        rows = activityVehicleIds.size
          ? rows.filter((vehicle) => activityVehicleIds.has(Number(vehicle.id)))
          : []
      }

      return rows
    },
    totalVehicles () {
      return this.filteredVehicles.length
    },
    activeFleetCount () {
      return this.filteredVehicles.filter((item) => item.isActive).length
    },
    assignedVehicles () {
      const assignedVehicleIds = new Set(this.filteredAssignments.map((item) => Number(item.vehicleId)))
      return this.filteredVehicles.filter((item) => item.isActive && assignedVehicleIds.has(Number(item.id)))
    },
    offRoadVehicles () {
      return this.filteredVehicles.filter((item) => item.isActive && item.isOffRoad)
    },
    availableVehicles () {
      const assignedVehicleIds = new Set(this.filteredAssignments.map((item) => Number(item.vehicleId)))
      return this.filteredVehicles.filter((item) => item.isActive && !item.isOffRoad && !assignedVehicleIds.has(Number(item.id)))
    },
    availabilityRate () {
      if (!this.activeFleetCount) {
        return 0
      }
      return Number(((this.availableVehicles.length / this.activeFleetCount) * 100).toFixed(1))
    },
    utilizationRate () {
      if (!this.activeFleetCount) {
        return 0
      }
      return Number(((this.assignedVehicles.length / this.activeFleetCount) * 100).toFixed(1))
    },
    offRoadRate () {
      if (!this.activeFleetCount) {
        return 0
      }
      return Number(((this.offRoadVehicles.length / this.activeFleetCount) * 100).toFixed(1))
    },
    thisMonthInvoiced () {
      const now = new Date()
      const month = now.getMonth()
      const year = now.getFullYear()

      return this.filteredInvoices.reduce((sum, invoice) => {
        const issuedAt = new Date(String(invoice.issuedAt || '').replace(' ', 'T'))
        if (Number.isNaN(issuedAt.getTime())) {
          return sum
        }
        if (issuedAt.getMonth() === month && issuedAt.getFullYear() === year) {
          return sum + Number(invoice.totalAmount || 0)
        }
        return sum
      }, 0)
    },
    previousMonthInvoiced () {
      const now = new Date()
      const previous = new Date(now.getFullYear(), now.getMonth() - 1, 1)
      const month = previous.getMonth()
      const year = previous.getFullYear()

      return this.filteredInvoices.reduce((sum, invoice) => {
        const issuedAt = new Date(String(invoice.issuedAt || '').replace(' ', 'T'))
        if (Number.isNaN(issuedAt.getTime())) {
          return sum
        }
        if (issuedAt.getMonth() === month && issuedAt.getFullYear() === year) {
          return sum + Number(invoice.totalAmount || 0)
        }
        return sum
      }, 0)
    },
    invoicedDelta () {
      return this.thisMonthInvoiced - this.previousMonthInvoiced
    },
    currentExposure () {
      return this.filteredAssignments.reduce((sum, item) => sum + Number(item.currentSubtotal || 0), 0)
    },
    overdueAssignments () {
      return this.filteredAssignments.filter((item) => Number(item.currentTotalHours || 0) > Number(item.freeHours || this.freeHours))
    },
    dueSoonAssignments () {
      return this.filteredAssignments.filter((item) => {
        const freeLimit = Number(item.freeHours || this.freeHours)
        const totalHours = Number(item.currentTotalHours || 0)
        return totalHours >= Math.max(0, freeLimit - 12) && totalHours <= freeLimit
      })
    },
    sortedAssignments () {
      return [...this.filteredAssignments].sort((a, b) => {
        const aRatio = Number(a.currentTotalHours || 0) - Number(a.freeHours || this.freeHours)
        const bRatio = Number(b.currentTotalHours || 0) - Number(b.freeHours || this.freeHours)
        return bRatio - aRatio
      })
    },
    recentInvoices () {
      return [...this.filteredInvoices]
        .sort((a, b) => {
          const aTime = new Date(String(a.issuedAt || '').replace(' ', 'T')).getTime()
          const bTime = new Date(String(b.issuedAt || '').replace(' ', 'T')).getTime()
          return (Number.isFinite(bTime) ? bTime : 0) - (Number.isFinite(aTime) ? aTime : 0)
        })
        .slice(0, 8)
    },
    topCarersByInvoice () {
      const grouped = this.filteredInvoices.reduce((acc, invoice) => {
        const name = String(invoice.carerName || 'Unknown carer')
        if (!acc[name]) {
          acc[name] = { name, total: 0, count: 0 }
        }
        acc[name].total += Number(invoice.totalAmount || 0)
        acc[name].count += 1
        return acc
      }, {})

      return Object.values(grouped)
        .sort((a, b) => b.total - a.total)
        .slice(0, 5)
    },
    invoiceTrend () {
      const now = new Date()
      const buckets = []
      for (let offset = 5; offset >= 0; offset -= 1) {
        const bucketDate = new Date(now.getFullYear(), now.getMonth() - offset, 1)
        const year = bucketDate.getFullYear()
        const month = bucketDate.getMonth()
        const key = `${year}-${String(month + 1).padStart(2, '0')}`
        buckets.push({
          key,
          year,
          month,
          label: bucketDate.toLocaleString('en-GB', { month: 'short' }),
          total: 0,
          percent: 0,
        })
      }

      this.filteredInvoices.forEach((invoice) => {
        const issuedAt = new Date(String(invoice.issuedAt || '').replace(' ', 'T'))
        if (Number.isNaN(issuedAt.getTime())) {
          return
        }
        const key = `${issuedAt.getFullYear()}-${String(issuedAt.getMonth() + 1).padStart(2, '0')}`
        const bucket = buckets.find((item) => item.key === key)
        if (bucket) {
          bucket.total += Number(invoice.totalAmount || 0)
        }
      })

      const max = Math.max(...buckets.map((item) => item.total), 1)
      return buckets.map((item) => ({
        ...item,
        percent: Number(((item.total / max) * 100).toFixed(1)),
      }))
    },
    liveAlertCount () {
      return this.overdueAssignments.length + this.offRoadVehicles.length + Number(this.maintenanceSummaryView.overdue || 0)
    },
  },
  created () {
    this.loadDashboard()
  },
  methods: {
    allocationApiUrl (action) {
      return `${API_BASE}/carAllocation.php?action=${action}`
    },
    maintenanceApiUrl (action) {
      return `${API_BASE}/maintenanceLog.php?action=${action}`
    },
    notify (message, type = 'success') {
      this.snackbar.message = message
      this.snackbar.type = type
      this.snackbar.show = true
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
      const parsed = value instanceof Date
        ? value
        : new Date(raw.includes('T') ? raw : raw.replace(' ', 'T'))
      if (Number.isNaN(parsed.getTime())) {
        return raw
      }
      return parsed.toLocaleString()
    },
    formatCurrency (value) {
      return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: 'GBP',
      }).format(Number(value || 0))
    },
    toTimestamp (value, endOfDay = false) {
      const raw = String(value || '').trim()
      if (!raw) {
        return NaN
      }

      if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
        const parsedDateOnly = new Date(`${raw}${endOfDay ? 'T23:59:59' : 'T00:00:00'}`)
        return parsedDateOnly.getTime()
      }

      const parsed = new Date(raw.includes('T') ? raw : raw.replace(' ', 'T'))
      return parsed.getTime()
    },
    isWithinSelectedDateRange (value) {
      if (!this.hasDateFilter) {
        return true
      }

      const ts = this.toTimestamp(value)
      if (!Number.isFinite(ts)) {
        return false
      }

      let start = this.filters.startDate ? this.toTimestamp(this.filters.startDate, false) : null
      let end = this.filters.endDate ? this.toTimestamp(this.filters.endDate, true) : null

      if (Number.isFinite(start) && Number.isFinite(end) && start > end) {
        const swap = start
        start = end
        end = swap
      }

      if (Number.isFinite(start) && ts < start) {
        return false
      }
      if (Number.isFinite(end) && ts > end) {
        return false
      }
      return true
    },
    toLocalDateInput (date) {
      const dt = new Date(date)
      dt.setMinutes(dt.getMinutes() - dt.getTimezoneOffset())
      return dt.toISOString().slice(0, 10)
    },
    setThisMonth () {
      const now = new Date()
      const firstDay = new Date(now.getFullYear(), now.getMonth(), 1)
      this.filters.startDate = this.toLocalDateInput(firstDay)
      this.filters.endDate = this.toLocalDateInput(now)
    },
    resetFilters () {
      this.filters = {
        startDate: '',
        endDate: '',
        vehicleId: 'all',
        carerId: 'all',
      }
    },
    assignmentRiskLabel (item) {
      const freeHours = Number(item.freeHours || this.freeHours)
      const total = Number(item.currentTotalHours || 0)
      if (total > freeHours) {
        return 'Over free period'
      }
      if (total >= Math.max(0, freeHours - 12)) {
        return 'Near billable'
      }
      return 'Within free'
    },
    assignmentRiskColor (item) {
      const label = this.assignmentRiskLabel(item)
      if (label === 'Over free period') {
        return 'error'
      }
      if (label === 'Near billable') {
        return 'warning'
      }
      return 'success'
    },
    normalizeAllocationPayload (payload = {}) {
      this.freeHours = Number(payload?.config?.freeHours || 72)
      this.carers = Array.isArray(payload?.carers) ? payload.carers : []
      this.vehicles = Array.isArray(payload?.vehicles) ? payload.vehicles : []
      this.activeAssignments = Array.isArray(payload?.activeAssignments) ? payload.activeAssignments : []
      this.invoices = Array.isArray(payload?.invoices) ? payload.invoices : []
    },
    normalizeMaintenancePayload (payload = {}) {
      this.maintenanceLogs = Array.isArray(payload?.logs) ? payload.logs : []
      this.maintenanceSummary = { ...defaultMaintenanceSummary, ...(payload?.summary || {}) }
    },
    async loadDashboard () {
      if (this.loading) {
        return
      }

      this.loading = true
      try {
        const [allocationResult, maintenanceResult] = await Promise.allSettled([
          axios.get(this.allocationApiUrl('getBootstrap')),
          axios.get(this.maintenanceApiUrl('getBootstrap')),
        ])

        if (allocationResult.status !== 'fulfilled') {
          throw allocationResult.reason || new Error('Failed to load car allocation data.')
        }

        const allocationPayload = allocationResult.value?.data || {}
        if (!allocationPayload.success) {
          throw new Error(allocationPayload.message || 'Failed to load car allocation data.')
        }
        this.normalizeAllocationPayload(allocationPayload)

        if (maintenanceResult.status === 'fulfilled') {
          const maintenancePayload = maintenanceResult.value?.data || {}
          if (maintenancePayload.success) {
            this.normalizeMaintenancePayload(maintenancePayload)
          } else {
            this.maintenanceLogs = []
            this.maintenanceSummary = { ...defaultMaintenanceSummary }
            this.notify('Maintenance summary could not be loaded for this refresh.', 'warning')
          }
        } else {
          this.maintenanceLogs = []
          this.maintenanceSummary = { ...defaultMaintenanceSummary }
          this.notify('Dashboard loaded without maintenance data.', 'warning')
        }

        this.lastSyncedAt = new Date()
      } catch (error) {
        this.notify(error?.response?.data?.message || 'Failed to load car dashboard.', 'error')
      } finally {
        this.loading = false
      }
    },
    goTo (routeName) {
      if (!routeName || routeName === this.$route?.name) {
        return
      }
      this.$router.push({ name: routeName })
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
.car-dashboard-page {
  padding: 24px;
}

.summary-card,
.panel-card {
  border-radius: 14px;
}

.fill-height {
  height: 100%;
}

.summary-label {
  font-size: 0.82rem;
  color: rgba(0, 0, 0, 0.62);
}

.summary-value {
  font-size: 1.65rem;
  font-weight: 700;
  margin-top: 4px;
}

.summary-value.money {
  font-size: 1.15rem;
}

.summary-sub {
  margin-top: 6px;
  font-size: 0.78rem;
  color: rgba(0, 0, 0, 0.56);
}

.kpi-grid {
  display: grid;
  gap: 14px;
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.kpi-item {
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 12px;
  padding: 12px;
}

.kpi-title {
  font-size: 0.82rem;
  color: rgba(0, 0, 0, 0.64);
  margin-bottom: 8px;
}

.kpi-value {
  margin-top: 8px;
  font-weight: 600;
}

.trend-wrap {
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 12px;
  padding: 12px;
}

.trend-title {
  font-size: 0.9rem;
  font-weight: 600;
}

.trend-row {
  display: grid;
  grid-template-columns: 46px minmax(0, 1fr) 108px;
  gap: 10px;
  align-items: center;
  margin-bottom: 8px;
}

.trend-label {
  font-size: 0.78rem;
  color: rgba(0, 0, 0, 0.64);
}

.trend-track {
  position: relative;
  width: 100%;
  height: 10px;
  border-radius: 10px;
  background: rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.trend-fill {
  position: absolute;
  left: 0;
  top: 0;
  height: 100%;
  border-radius: 10px;
  background: linear-gradient(90deg, #2b88d8 0%, #44b1f2 100%);
}

.trend-value {
  text-align: right;
  font-size: 0.78rem;
  font-weight: 600;
}

.mini-list {
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 12px;
  overflow: hidden;
}

.mini-list-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.mini-list-item:last-child {
  border-bottom: none;
}

.table-wrap {
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 12px;
  overflow: auto;
}

@media (max-width: 1200px) {
  .kpi-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 960px) {
  .car-dashboard-page {
    padding: 12px;
  }

  .trend-row {
    grid-template-columns: 40px minmax(0, 1fr) 90px;
  }
}
</style>
