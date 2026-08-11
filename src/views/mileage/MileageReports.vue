<template>
  <v-container fluid>
    <v-card variant="outlined" class="mb-4">
      <v-card-text><v-row dense>
        <v-col cols="12" md="3"><v-text-field v-model="filters.weekStart" label="Week start" type="date" density="compact" variant="outlined" hide-details /></v-col>
        <v-col cols="12" md="3"><v-text-field v-model="filters.weekEnd" label="Week end" type="date" density="compact" variant="outlined" hide-details /></v-col>
        <v-col cols="12" md="6" class="text-right"><v-btn color="primary" variant="outlined" @click="load">Run Report</v-btn></v-col>
      </v-row></v-card-text>
    </v-card>
    <v-card variant="outlined">
      <v-card-title class="d-flex align-center">Mileage Reports <v-spacer /><v-chip variant="outlined">Export-ready</v-chip></v-card-title>
      <v-card-text>
        <v-table density="comfortable">
          <thead><tr><th>Carer/Driver</th><th>Week</th><th>Entries</th><th>Claimed</th><th>Adjusted</th><th>System</th><th>Payable</th><th>Amount</th><th>Flags</th></tr></thead>
          <tbody>
            <tr v-for="row in report" :key="`${row.userId}-${row.weekStart}`" :class="{ flagged: row.flaggedCount > 0 }">
              <td>{{ row.driverName || `User ${row.userId}` }}</td><td>{{ row.weekStart }} to {{ row.weekEnd }}</td><td>{{ row.entryCount }}</td>
              <td>{{ row.totalClaimedMileage }} mi</td><td>{{ row.totalAdjustedClaimedMileage }} mi</td><td>{{ row.totalExpectedSystemMileage }} mi</td>
              <td>{{ row.totalFinalPayableMileage }} mi</td><td>{{ gbp(row.totalPayableAmount) }}</td><td>{{ row.flaggedCount }}</td>
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
const filters = reactive({ weekStart: '', weekEnd: '' });
const load = async () => { report.value = (await fetchWeeklyMileageReport(filters)).report || []; };
onMounted(load);
</script>

<style scoped>
.v-card{border-radius:8px}.flagged{background:#fff8e1}
</style>
