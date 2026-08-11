<template>
  <v-container fluid class="weekly-breakdown-page">
    <v-tabs v-model="activeTab" color="primary" class="mb-4">
      <v-tab value="weekly">Weekly Payroll Summary</v-tab>
      <v-tab value="monthly">Monthly Sheet</v-tab>
    </v-tabs>

    <v-window v-model="activeTab">
      <v-window-item value="weekly">
        <v-card variant="outlined" class="panel-card mb-4">
          <v-card-text>
            <v-row dense align="center">
              <v-col cols="12" md="3">
                <v-text-field v-model="filters.weekStart" label="Payroll week" type="date" density="compact" variant="outlined" hide-details />
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="filters.driver" label="Carer / driver" density="compact" variant="outlined" hide-details clearable />
              </v-col>
              <v-col cols="12" md="2">
                <v-select v-model="filters.status" :items="statusOptions" label="Entry status" density="compact" variant="outlined" hide-details clearable />
              </v-col>
              <v-col cols="6" md="2">
                <v-checkbox v-model="filters.flaggedOnly" label="Flagged only" hide-details density="compact" />
              </v-col>
              <v-col cols="6" md="2">
                <v-checkbox v-model="filters.pendingOnly" label="Pending only" hide-details density="compact" />
              </v-col>
              <v-col cols="12" class="d-flex flex-wrap ga-2 justify-end">
                <v-btn color="primary" variant="outlined" :loading="loading" @click="load">Apply Filters</v-btn>
                <v-btn color="primary" prepend-icon="mdi-file-delimited-outline" :disabled="!rows.length" @click="exportCsv">Export CSV</v-btn>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <v-alert v-if="error" type="error" variant="tonal" class="mb-4">{{ error }}</v-alert>
        <v-alert type="info" variant="tonal" class="mb-4">
          Expected Total Mileage = Access Mileage + Passenger Pickup. Entries above the configured threshold require review.
        </v-alert>

        <div class="week-label mb-4">Payroll Week: {{ weekLabel }}</div>

        <v-row dense class="mb-4">
          <v-col v-for="card in summaryCards" :key="card.label" cols="12" sm="6" lg="2">
            <v-card variant="outlined" class="summary-card">
              <v-card-text>
                <div class="summary-label">{{ card.label }}</div>
                <div class="summary-value">{{ card.value }}</div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <v-card variant="outlined" class="panel-card">
          <v-card-title class="d-flex align-center">
            Weekly Mileage Breakdown
            <v-spacer />
            <v-chip variant="outlined">{{ rows.length }} carers</v-chip>
          </v-card-title>
          <v-card-text>
            <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-3" />
            <div class="table-wrap">
              <v-data-table
                :headers="headers"
                :items="rows"
                :items-per-page="25"
                density="comfortable"
                class="breakdown-table"
              >
                <template #item.claimedMileageTotal="{ item }">{{ formatMiles(item.claimedMileageTotal) }}</template>
                <template #item.accessMileageTotal="{ item }">{{ formatMiles(item.accessMileageTotal) }}</template>
                <template #item.passengerPickupMileageTotal="{ item }">{{ formatMiles(item.passengerPickupMileageTotal) }}</template>
                <template #item.expectedTotalMileage="{ item }">{{ formatMiles(item.expectedTotalMileage) }}</template>
                <template #item.mileageDifference="{ item }">
                  <span :class="{ variance: Number(item.mileageDifference) !== 0 }">{{ formatMiles(item.mileageDifference) }}</span>
                </template>
                <template #item.weeklyStatus="{ item }">
                  <v-chip size="small" :color="weeklyStatusColor(item.weeklyStatus)" variant="tonal">{{ weeklyStatusLabel(item.weeklyStatus) }}</v-chip>
                </template>
                <template #item.finalPayableMileageTotal="{ item }">{{ formatMiles(item.finalPayableMileageTotal) }}</template>
                <template #item.finalPayableAmountTotal="{ item }">{{ gbp(item.finalPayableAmountTotal) }}</template>
                <template #item.actions="{ item }">
                  <div class="d-flex ga-1">
                    <v-btn size="small" variant="text" icon="mdi-eye-outline" @click="openDetails(item)" />
                    <v-btn size="small" variant="text" icon="mdi-clipboard-check-outline" @click="$router.push({ name: 'mileageReview', query: { driver: item.driverName } })" />
                  </div>
                </template>
                <template #bottom>
                  <div class="totals-row">
                    <span>Total claimed: <strong>{{ formatMiles(totals.claimedMileageTotal) }}</strong></span>
                    <span>Access: <strong>{{ formatMiles(totals.accessMileageTotal) }}</strong></span>
                    <span>Pickup: <strong>{{ formatMiles(totals.passengerPickupMileageTotal) }}</strong></span>
                    <span>Expected: <strong>{{ formatMiles(totals.expectedTotalMileage) }}</strong></span>
                    <span>Final payable: <strong>{{ formatMiles(totals.finalPayableMileageTotal) }}</strong></span>
                    <span>Amount: <strong>{{ gbp(totals.finalPayableAmountTotal) }}</strong></span>
                  </div>
                </template>
              </v-data-table>
            </div>
          </v-card-text>
        </v-card>
      </v-window-item>

      <v-window-item value="monthly">
        <v-card variant="outlined" class="panel-card">
          <v-card-title>Monthly Sheet</v-card-title>
          <v-card-text>
            <v-alert type="info" variant="tonal">
              Monthly Excel-style day columns are scaffolded for a future pass. The weekly payroll breakdown is the active reconciliation workflow.
            </v-alert>
          </v-card-text>
        </v-card>
      </v-window-item>
    </v-window>

    <v-dialog v-model="detailDialog" max-width="1280">
      <v-card>
        <v-card-title class="d-flex align-center">
          Daily Breakdown - {{ selectedRow?.driverName }}
          <v-spacer />
          <v-chip :color="weeklyStatusColor(selectedRow?.weeklyStatus)" variant="tonal">{{ weeklyStatusLabel(selectedRow?.weeklyStatus) }}</v-chip>
        </v-card-title>
        <v-card-text>
          <v-table density="compact">
            <thead>
              <tr>
                <th>Date</th>
                <th>Vehicle</th>
                <th>Odo Start</th>
                <th>Odo End</th>
                <th>Claimed</th>
                <th>Midday Add-on</th>
                <th>Adjusted</th>
                <th>Access</th>
                <th>Pickup</th>
                <th>Expected Total</th>
                <th>Difference</th>
                <th>Status</th>
                <th>Final Mileage</th>
                <th>Amount</th>
                <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="entry in selectedEntries" :key="entry.id" :class="{ flagged: entry.thresholdFlag }">
                <td>{{ entry.workDate }}</td>
                <td>{{ entry.vehicleLabel || 'Not set' }}</td>
                <td>{{ entry.odometerStart }}</td>
                <td>{{ entry.odometerEnd }}</td>
                <td>{{ formatMiles(entry.claimedMileage) }}</td>
                <td>{{ formatMiles(entry.middayPayableMileage) }}</td>
                <td>{{ formatMiles(entry.adjustedClaimedMileage) }}</td>
                <td>{{ formatMiles(entry.expectedSystemMileage) }}</td>
                <td>{{ formatMiles(entry.passengerPickupMileage) }}</td>
                <td>{{ formatMiles(entry.expectedTotalMileage) }}</td>
                <td>{{ formatMiles(entry.managerMileageDifference) }}</td>
                <td><v-chip size="small" :color="statusColor(entry.adminStatus)" variant="tonal">{{ statusLabel(entry.adminStatus) }}</v-chip></td>
                <td>{{ entry.finalPayableMileage === null ? 'Review' : formatMiles(entry.finalPayableMileage) }}</td>
                <td>{{ gbp(entry.finalPayableAmount) }}</td>
                <td class="notes-cell">{{ entry.middayMileageReason || entry.notes || entry.adminNotes || entry.driverExplanation }}</td>
              </tr>
            </tbody>
          </v-table>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="detailDialog = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import {
  fetchCurrentPayrollWeek,
  fetchWeeklyMileageBreakdown,
  gbp,
  miles,
  statusColor,
  statusLabel,
} from '../../services/mileageService';

const activeTab = ref('weekly');
const loading = ref(false);
const error = ref('');
const rows = ref([]);
const totals = ref({});
const week = ref({ weekStart: '', weekEnd: '' });
const detailDialog = ref(false);
const selectedRow = ref(null);
const filters = reactive({
  weekStart: '',
  driver: '',
  status: '',
  flaggedOnly: false,
  pendingOnly: false,
});

const statusOptions = ['draft', 'submitted', 'pending_review', 'approved', 'rejected', 'adjusted'];
const headers = [
  { title: 'Carer', key: 'driverName', sortable: true },
  { title: 'Entries', key: 'entryCount', sortable: true },
  { title: 'Claimed Mileage', key: 'claimedMileageTotal', sortable: true },
  { title: 'Access Mileage', key: 'accessMileageTotal', sortable: true },
  { title: 'Passenger Pickup', key: 'passengerPickupMileageTotal', sortable: true },
  { title: 'Expected Total', key: 'expectedTotalMileage', sortable: true },
  { title: 'Mileage Difference', key: 'mileageDifference', sortable: true },
  { title: 'Status', key: 'weeklyStatus', sortable: true },
  { title: 'Final Payable', key: 'finalPayableMileageTotal', sortable: true },
  { title: 'Amount', key: 'finalPayableAmountTotal', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false },
];

const selectedEntries = computed(() => selectedRow.value?.entries || []);
const weekLabel = computed(() => {
  if (!week.value.weekStart || !week.value.weekEnd) return '';
  const format = (value) => new Date(`${value}T12:00:00`).toLocaleDateString('en-GB', {
    weekday: 'short',
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  });
  return `${format(week.value.weekStart)} - ${format(week.value.weekEnd)}`;
});
const summaryCards = computed(() => [
  { label: 'Total Carers', value: totals.value.totalCarers || 0 },
  { label: 'Claimed', value: formatMiles(totals.value.claimedMileageTotal) },
  { label: 'Expected', value: formatMiles(totals.value.expectedTotalMileage) },
  { label: 'Final Payable', value: formatMiles(totals.value.finalPayableMileageTotal) },
  { label: 'Payable Amount', value: gbp(totals.value.finalPayableAmountTotal) },
  { label: 'Needs Review', value: totals.value.pendingReviewCount || 0 },
]);

const formatMiles = (value) => `${miles(value).toFixed(2)} mi`;
const weeklyStatusLabel = (status) => ({
  needs_review: 'Needs Review',
  ready: 'Ready',
  paid: 'Paid',
  mixed: 'Mixed',
}[status] || 'Mixed');
const weeklyStatusColor = (status) => ({
  needs_review: 'warning',
  ready: 'success',
  paid: 'primary',
  mixed: 'info',
}[status] || 'grey');

const load = async () => {
  loading.value = true;
  error.value = '';
  try {
    const data = await fetchWeeklyMileageBreakdown({
      weekStart: filters.weekStart,
      driver: filters.driver,
      status: filters.status,
      flaggedOnly: filters.flaggedOnly,
      pendingOnly: filters.pendingOnly,
    });
    rows.value = data.breakdown?.rows || [];
    totals.value = data.breakdown?.totals || {};
    week.value = data.breakdown?.week || week.value;
    filters.weekStart = week.value.weekStart || filters.weekStart;
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load weekly mileage breakdown.';
  } finally {
    loading.value = false;
  }
};

const openDetails = (row) => {
  selectedRow.value = row;
  detailDialog.value = true;
};

const exportCsv = () => {
  const header = ['Carer', 'Entries', 'Claimed Mileage', 'Access Mileage', 'Passenger Pickup', 'Expected Total Mileage', 'Mileage Difference', 'Weekly Status', 'Final Payable Mileage', 'Rate', 'Final Payable Amount'];
  const lines = rows.value.map((row) => [
    row.driverName,
    row.entryCount,
    row.claimedMileageTotal,
    row.accessMileageTotal,
    row.passengerPickupMileageTotal,
    row.expectedTotalMileage,
    row.mileageDifference,
    weeklyStatusLabel(row.weeklyStatus),
    row.finalPayableMileageTotal,
    row.rate,
    row.finalPayableAmountTotal,
  ]);
  const csv = [header, ...lines]
    .map((line) => line.map((value) => `"${String(value ?? '').replace(/"/g, '""')}"`).join(','))
    .join('\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `weekly-mileage-${week.value.weekStart || 'summary'}.csv`;
  link.click();
  URL.revokeObjectURL(link.href);
};

onMounted(async () => {
  const current = await fetchCurrentPayrollWeek();
  filters.weekStart = current.week?.weekStart || new Date().toISOString().slice(0, 10);
  await load();
});
</script>

<style scoped>
.panel-card,.summary-card{border-radius:8px}.week-label{font-weight:700;color:#344054}.summary-label{font-size:.78rem;color:#667085}.summary-value{font-size:1.4rem;font-weight:700}.table-wrap{overflow-x:auto}.breakdown-table{min-width:1180px}.totals-row{display:flex;flex-wrap:wrap;gap:16px;padding:14px 16px;border-top:1px solid #eaecf0;background:#f9fafb}.variance{font-weight:700;color:#b54708}.flagged{background:#fff8e1}.notes-cell{min-width:220px;white-space:normal}
</style>
