<template>
  <v-container fluid>
    <v-card variant="outlined" class="mb-4 no-print">
      <v-card-text><v-row dense align="center">
        <v-col cols="12" md="3"><v-text-field v-model="filters.weekStart" label="Week start (from)" type="date" density="compact" variant="outlined" hide-details /></v-col>
        <v-col cols="12" md="3"><v-text-field v-model="filters.weekEnd" label="Week end (to)" type="date" density="compact" variant="outlined" hide-details /></v-col>
        <v-col cols="12" md="3"><v-text-field v-model="filters.driver" label="Carer / driver" density="compact" variant="outlined" hide-details clearable /></v-col>
        <v-col cols="12" md="3" class="text-right"><v-btn color="primary" variant="outlined" :loading="loading" @click="load">Run Report</v-btn></v-col>
      </v-row></v-card-text>
    </v-card>
    <v-alert v-if="error" type="error" variant="tonal" class="mb-4 no-print">{{ error }}</v-alert>
    <v-card variant="outlined">
      <v-card-title class="d-flex align-center flex-wrap ga-2">
        Mileage Reports
        <v-spacer />
        <v-chip variant="outlined">{{ report.length }} rows</v-chip>
        <v-btn class="no-print" variant="outlined" prepend-icon="mdi-file-excel-outline" :disabled="!report.length" @click="exportCsv">Export Excel/CSV</v-btn>
        <v-btn class="no-print" variant="outlined" prepend-icon="mdi-file-pdf-box" :disabled="!report.length" @click="exportPdf">Export PDF</v-btn>
      </v-card-title>
      <v-card-text>
        <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-3 no-print" />
        <v-table density="comfortable">
          <thead><tr><th>Carer/Driver</th><th>Week</th><th>Entries</th><th>Claimed</th><th>Adjusted</th><th>System</th><th>Payable</th><th>Amount</th><th>Flags</th></tr></thead>
          <tbody>
            <tr v-for="row in report" :key="`${row.userId}-${row.weekStart}`" :class="{ flagged: row.flaggedCount > 0 }">
              <td>{{ row.driverName || `User ${row.userId}` }}</td><td>{{ row.weekStart }} to {{ row.weekEnd }}</td><td>{{ row.entryCount }}</td>
              <td>{{ row.totalClaimedMileage }} mi</td><td>{{ row.totalAdjustedClaimedMileage }} mi</td><td>{{ row.totalExpectedSystemMileage }} mi</td>
              <td>{{ row.totalFinalPayableMileage }} mi</td><td>{{ gbp(row.totalPayableAmount) }}</td><td>{{ row.flaggedCount }}</td>
            </tr>
            <tr v-if="!loading && !report.length">
              <td colspan="9" class="text-center text-medium-emphasis">No entries match these filters. Try widening the week range.</td>
            </tr>
          </tbody>
        </v-table>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { fetchWeeklyMileageReport, gbp } from '../../services/mileageService';

const report = ref([]);
const loading = ref(false);
const error = ref('');
const filters = reactive({ weekStart: '', weekEnd: '', driver: '' });

const load = async () => {
  loading.value = true;
  error.value = '';
  try {
    report.value = (await fetchWeeklyMileageReport(filters)).report || [];
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load mileage report.';
  } finally {
    loading.value = false;
  }
};

const exportCsv = () => {
  const header = ['Carer/Driver', 'Week Start', 'Week End', 'Entries', 'Claimed Mileage', 'Adjusted Mileage', 'System Mileage', 'Payable Mileage', 'Payable Amount', 'Flagged'];
  const lines = report.value.map((row) => [
    row.driverName || `User ${row.userId}`,
    row.weekStart,
    row.weekEnd,
    row.entryCount,
    row.totalClaimedMileage,
    row.totalAdjustedClaimedMileage,
    row.totalExpectedSystemMileage,
    row.totalFinalPayableMileage,
    row.totalPayableAmount,
    row.flaggedCount,
  ]);
  const csv = [header, ...lines]
    .map((line) => line.map((value) => `"${String(value ?? '').replace(/"/g, '""')}"`).join(','))
    .join('\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `mileage-report-${filters.weekStart || 'all'}.csv`;
  link.click();
  URL.revokeObjectURL(link.href);
};

// Uses the browser's native print dialog ("Save as PDF" is a built-in
// destination in every modern browser/OS) rather than pulling in a PDF
// generation library -- the .no-print rule hides the filter/export
// controls so only the report table prints.
const exportPdf = () => {
  window.print();
};

onMounted(load);
</script>

<style scoped>
.v-card{border-radius:8px}.flagged{background:#fff8e1}
@media print {
  :deep(.no-print) { display: none !important; }
}
</style>
