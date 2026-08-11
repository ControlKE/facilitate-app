<template>
  <v-container fluid class="mileage-page">
    <v-row dense class="mb-4">
      <v-col v-for="card in cards" :key="card.label" cols="12" sm="6" lg="2">
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
        Recent Mileage Entries
        <v-spacer />
        <v-btn color="primary" prepend-icon="mdi-plus" @click="$router.push({ name: 'mileageNew' })">New Entry</v-btn>
      </v-card-title>
      <v-card-text>
        <v-alert v-if="error" type="error" variant="tonal" class="mb-3">{{ error }}</v-alert>
        <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-3" />
        <v-table density="comfortable">
          <thead>
            <tr>
              <th>Date</th><th>Driver</th><th>Claimed</th><th>Adjusted</th><th>System</th><th>Difference</th><th>Status</th><th>Payable</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="entry in entries" :key="entry.id" :class="{ flagged: entry.thresholdFlag }">
              <td>{{ entry.workDate }}</td>
              <td>{{ entry.driverName }}</td>
              <td>{{ entry.claimedMileage }} mi</td>
              <td>{{ entry.adjustedClaimedMileage }} mi</td>
              <td>{{ entry.expectedSystemMileage }} mi</td>
              <td>{{ entry.differenceFromSystem }} mi</td>
              <td><v-chip size="small" :color="statusColor(entry.adminStatus)" variant="tonal">{{ statusLabel(entry.adminStatus) }}</v-chip></td>
              <td>{{ gbp(entry.finalPayableAmount) }}</td>
              <td><v-btn size="small" variant="text" icon="mdi-open-in-new" @click="$router.push({ name: 'mileageReview', query: { id: entry.id } })" /></td>
            </tr>
          </tbody>
        </v-table>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { fetchMileageEntries, gbp, statusColor, statusLabel } from '../../services/mileageService';

const entries = ref([]);
const loading = ref(false);
const error = ref('');

const cards = computed(() => {
  const pending = entries.value.filter((entry) => entry.adminStatus === 'pending_review').length;
  const approved = entries.value.filter((entry) => entry.adminStatus === 'approved' || entry.adminStatus === 'adjusted').length;
  const submitted = entries.value.filter((entry) => entry.adminStatus !== 'draft').length;
  const payable = entries.value.reduce((sum, entry) => sum + Number(entry.finalPayableAmount || 0), 0);
  const flagged = entries.value.filter((entry) => entry.thresholdFlag).length;
  return [
    { label: 'Submitted', value: submitted },
    { label: 'Pending Review', value: pending },
    { label: 'Approved', value: approved },
    { label: 'Payable', value: gbp(payable) },
    { label: 'Flagged', value: flagged },
  ];
});

const load = async () => {
  loading.value = true;
  error.value = '';
  try {
    const data = await fetchMileageEntries();
    entries.value = data.entries || [];
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load mileage dashboard.';
  } finally {
    loading.value = false;
  }
};

onMounted(load);
</script>

<style scoped>
.summary-card,.panel-card{border-radius:8px}.summary-label{font-size:.78rem;color:#667085}.summary-value{font-size:1.6rem;font-weight:700}.flagged{background:#fff8e1}
</style>
