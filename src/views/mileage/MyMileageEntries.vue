<template>
  <v-container fluid>
    <v-card variant="outlined" class="panel-card">
      <v-card-title class="d-flex align-center">
        My Mileage Entries
        <v-spacer />
        <v-btn color="primary" prepend-icon="mdi-plus" @click="$router.push({ name: 'mileageNew' })">New Entry</v-btn>
      </v-card-title>
      <v-card-text>
        <v-alert v-if="message" :type="messageType" variant="tonal" class="mb-3">{{ message }}</v-alert>
        <v-table density="comfortable">
          <thead><tr><th>Date</th><th>Route</th><th>Claimed</th><th>Adjusted</th><th>System</th><th>Status</th><th>Amount</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-for="entry in entries" :key="entry.id" :class="{ flagged: entry.thresholdFlag }">
              <td>{{ entry.workDate }}</td>
              <td>{{ entry.startingLocation }} to {{ entry.endingLocation }}</td>
              <td>{{ entry.claimedMileage }} mi</td>
              <td>{{ entry.adjustedClaimedMileage }} mi</td>
              <td>{{ entry.expectedSystemMileage }} mi</td>
              <td><v-chip size="small" :color="statusColor(entry.adminStatus)" variant="tonal">{{ statusLabel(entry.adminStatus) }}</v-chip></td>
              <td>{{ gbp(entry.finalPayableAmount) }}</td>
              <td class="text-no-wrap">
                <v-btn size="small" variant="text" icon="mdi-pencil" :disabled="!canEdit(entry)" @click="$router.push({ name: 'mileageNew', query: { id: entry.id } })" />
                <v-btn size="small" variant="text" icon="mdi-send" :disabled="entry.adminStatus !== 'draft'" @click="submit(entry.id)" />
                <v-btn size="small" variant="text" icon="mdi-delete" :disabled="entry.adminStatus !== 'draft'" @click="remove(entry.id)" />
              </td>
            </tr>
          </tbody>
        </v-table>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { currentMileageUser, deleteMileageEntry, fetchMileageEntries, gbp, statusColor, statusLabel, submitMileageEntry } from '../../services/mileageService';

const entries = ref([]);
const message = ref('');
const messageType = ref('success');
const canEdit = (entry) => ['draft', 'pending_review'].includes(entry.adminStatus);
const user = currentMileageUser();

const load = async () => {
  const data = await fetchMileageEntries({ userId: user.userId });
  entries.value = data.entries || [];
};
const submit = async (id) => {
  await submitMileageEntry(id);
  message.value = 'Mileage entry submitted.';
  messageType.value = 'success';
  await load();
};
const remove = async (id) => {
  await deleteMileageEntry(id);
  message.value = 'Draft mileage entry deleted.';
  messageType.value = 'success';
  await load();
};
onMounted(load);
</script>

<style scoped>
.panel-card{border-radius:8px}.flagged{background:#fff8e1}
</style>
