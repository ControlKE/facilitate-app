<template>
  <v-container fluid>
    <v-row dense class="mb-4">
      <v-col cols="12" md="3"><v-card variant="outlined"><v-card-text><div class="text-caption">Week</div><strong>{{ week.weekStart }} to {{ week.weekEnd }}</strong></v-card-text></v-card></v-col>
      <v-col cols="12" md="3"><v-card variant="outlined"><v-card-text><div class="text-caption">Adjusted Mileage</div><strong>{{ totals.adjusted }} mi</strong></v-card-text></v-card></v-col>
      <v-col cols="12" md="3"><v-card variant="outlined"><v-card-text><div class="text-caption">Payable</div><strong>{{ totals.payable }} mi</strong></v-card-text></v-card></v-col>
      <v-col cols="12" md="3"><v-card variant="outlined"><v-card-text><div class="text-caption">Amount</div><strong>{{ gbp(totals.amount) }}</strong></v-card-text></v-card></v-col>
    </v-row>
    <v-card variant="outlined">
      <v-card-title class="d-flex align-center">
        Weekly Mileage Submission
        <v-spacer />
        <v-btn color="primary" prepend-icon="mdi-send" :disabled="!entries.length || flaggedWithoutExplanation" @click="submitWeek">Submit Week</v-btn>
      </v-card-title>
      <v-card-text>
        <v-alert v-if="flaggedWithoutExplanation" type="warning" variant="tonal" class="mb-3">Flagged entries need explanations before Tuesday submission.</v-alert>
        <v-alert v-if="message" type="success" variant="tonal" class="mb-3">{{ message }}</v-alert>
        <v-table density="comfortable">
          <thead><tr><th>Date</th><th>Claimed</th><th>Midday Add-on</th><th>Adjusted</th><th>System</th><th>Difference</th><th>Status</th></tr></thead>
          <tbody>
            <tr v-for="entry in entries" :key="entry.id" :class="{ flagged: entry.thresholdFlag }">
              <td>{{ entry.workDate }}</td><td>{{ entry.claimedMileage }}</td><td>{{ entry.middayPayableMileage }}</td>
              <td>{{ entry.adjustedClaimedMileage }}</td><td>{{ entry.expectedSystemMileage }}</td><td>{{ entry.differenceFromSystem }}</td>
              <td><v-chip size="small" :color="statusColor(entry.adminStatus)" variant="tonal">{{ statusLabel(entry.adminStatus) }}</v-chip></td>
            </tr>
          </tbody>
        </v-table>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { currentMileageUser, fetchCurrentWeekMileage, gbp, statusColor, statusLabel, submitMileageWeek } from '../../services/mileageService';

const entries = ref([]);
const week = ref({});
const message = ref('');
const user = currentMileageUser();
const totals = computed(() => ({
  adjusted: entries.value.reduce((sum, entry) => sum + Number(entry.adjustedClaimedMileage || 0), 0).toFixed(2),
  payable: entries.value.reduce((sum, entry) => sum + Number(entry.finalPayableMileage || 0), 0).toFixed(2),
  amount: entries.value.reduce((sum, entry) => sum + Number(entry.finalPayableAmount || 0), 0),
}));
const flaggedWithoutExplanation = computed(() => entries.value.some((entry) => entry.thresholdFlag && !String(entry.driverExplanation || '').trim()));
const load = async () => {
  const data = await fetchCurrentWeekMileage({ userId: user.userId });
  entries.value = data.entries || [];
  week.value = data.week || {};
};
const submitWeek = async () => {
  await submitMileageWeek({ userId: user.userId, driverName: user.driverName, weekStart: week.value.weekStart, weekEnd: week.value.weekEnd });
  message.value = 'Weekly mileage submitted for office review.';
  await load();
};
onMounted(load);
</script>

<style scoped>
.v-card{border-radius:8px}.flagged{background:#fff8e1}
</style>
