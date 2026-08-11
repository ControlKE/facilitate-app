<template>
  <v-container fluid>
    <v-card variant="outlined" class="mb-4">
      <v-card-text><v-row dense>
        <v-col cols="12" md="3"><v-text-field v-model="filters.driver" label="Driver" density="compact" variant="outlined" hide-details /></v-col>
        <v-col cols="12" md="3"><v-select v-model="filters.status" :items="statusOptions" label="Status" density="compact" variant="outlined" hide-details clearable /></v-col>
        <v-col cols="12" md="3"><v-checkbox v-model="filters.flaggedOnly" label="Flagged only" hide-details /></v-col>
        <v-col cols="12" md="3" class="text-right"><v-btn color="primary" variant="outlined" @click="load">Apply</v-btn></v-col>
      </v-row></v-card-text>
    </v-card>
    <v-card variant="outlined">
      <v-card-title>Admin Mileage Review</v-card-title>
      <v-card-text>
        <v-table density="compact">
          <thead><tr><th>Date</th><th>Driver</th><th>Claimed</th><th>Midday Add-on</th><th>Adjusted</th><th>System</th><th>Diff</th><th>Flag</th><th>Explanation</th><th>Status</th><th>Final</th><th>Amount</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-for="entry in entries" :key="entry.id" :class="{ flagged: entry.thresholdFlag }">
              <td>{{ entry.workDate }}</td><td>{{ entry.driverName }}</td><td>{{ entry.claimedMileage }}</td><td>{{ entry.middayPayableMileage }}</td><td>{{ entry.adjustedClaimedMileage }}</td><td>{{ entry.expectedSystemMileage }}</td><td>{{ entry.differenceFromSystem }}</td>
              <td><v-icon :color="entry.thresholdFlag ? 'warning' : 'success'">{{ entry.thresholdFlag ? 'mdi-alert' : 'mdi-check' }}</v-icon></td>
              <td class="explanation">{{ entry.driverExplanation }}</td>
              <td><v-chip size="small" :color="statusColor(entry.adminStatus)" variant="tonal">{{ statusLabel(entry.adminStatus) }}</v-chip></td>
              <td>{{ entry.finalPayableMileage ?? 'Review' }}</td><td>{{ gbp(entry.finalPayableAmount) }}</td>
              <td><v-btn size="small" color="primary" variant="text" @click="openReview(entry)">Review</v-btn></td>
            </tr>
          </tbody>
        </v-table>
      </v-card-text>
    </v-card>
    <v-dialog v-model="dialog" max-width="520">
      <v-card>
        <v-card-title>Review Mileage</v-card-title>
        <v-card-text>
          <v-select v-model="review.status" :items="['approved','rejected','adjusted']" label="Decision" variant="outlined" />
          <v-text-field v-if="review.status === 'adjusted'" v-model.number="review.adminAdjustedPayableMileage" label="Adjusted payable mileage" type="number" min="0" variant="outlined" />
          <v-textarea v-model="review.adminNotes" label="Admin notes" variant="outlined" rows="3" />
        </v-card-text>
        <v-card-actions><v-spacer /><v-btn variant="text" @click="dialog=false">Cancel</v-btn><v-btn color="primary" @click="saveReview">Save Review</v-btn></v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { currentMileageUser, fetchMileageEntries, gbp, reviewMileageEntry, statusColor, statusLabel } from '../../services/mileageService';

const entries = ref([]);
const dialog = ref(false);
const selected = ref(null);
const filters = reactive({ driver: '', status: 'pending_review', flaggedOnly: false });
const user = currentMileageUser();
const review = reactive({ status: 'approved', adminAdjustedPayableMileage: 0, adminNotes: '', reviewerId: user.userId, reviewerName: user.driverName });
const statusOptions = ['draft', 'submitted', 'pending_review', 'approved', 'rejected', 'adjusted'];
const load = async () => { entries.value = (await fetchMileageEntries(filters)).entries || []; };
const openReview = (entry) => {
  selected.value = entry;
  review.status = entry.thresholdFlag ? 'adjusted' : 'approved';
  review.adminAdjustedPayableMileage = entry.finalPayableMileage || entry.expectedSystemMileage || 0;
  review.adminNotes = entry.adminNotes || '';
  dialog.value = true;
};
const saveReview = async () => {
  await reviewMileageEntry(selected.value.id, review);
  dialog.value = false;
  await load();
};
onMounted(load);
</script>

<style scoped>
.v-card{border-radius:8px}.flagged{background:#fff8e1}.explanation{max-width:260px;white-space:normal}
</style>
