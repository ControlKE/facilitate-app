<template>
  <v-container fluid>
    <v-card variant="outlined" class="mb-4">
      <v-card-text><v-row dense>
        <v-col cols="12" md="6"><v-text-field v-model="driverFilter" label="Driver" density="compact" variant="outlined" hide-details clearable /></v-col>
        <v-col cols="12" md="6" class="text-right"><v-btn color="primary" variant="outlined" @click="load">Refresh</v-btn></v-col>
      </v-row></v-card-text>
    </v-card>

    <v-card variant="outlined">
      <v-card-title>New Mileage Submissions</v-card-title>
      <v-card-text>
        <v-alert type="info" variant="tonal" class="mb-3">
          Entries submitted from the driver mileage portal, not yet verified against Access Care Planning.
          One row per driver per submission period -- open a row to see every entry and verify each against Access.
        </v-alert>
        <v-table density="compact">
          <thead><tr><th>Driver</th><th>Period</th><th>Entries</th><th>Total Claimed</th><th>Latest Submission</th><th>Flagged</th><th></th></tr></thead>
          <tbody>
            <tr v-for="group in groups" :key="group.key">
              <td>{{ group.driverName }}</td>
              <td>{{ group.weekStart }} &rarr; {{ group.weekEnd }}</td>
              <td>{{ group.entries.length }}</td>
              <td>{{ group.totalClaimed }} mi</td>
              <td>{{ formatDate(group.latestSubmittedAt) }}</td>
              <td><v-icon v-if="group.flaggedCount" color="warning" :title="`${group.flaggedCount} flagged`">mdi-alert</v-icon></td>
              <td class="text-no-wrap"><v-btn size="small" color="primary" variant="tonal" @click="openGroup(group)">Open ({{ group.entries.length }})</v-btn></td>
            </tr>
            <tr v-if="!groups.length">
              <td colspan="7" class="text-center text-medium-emphasis">No new driver-submitted mileage waiting on office verification.</td>
            </tr>
          </tbody>
        </v-table>
      </v-card-text>
    </v-card>

    <!-- Per-driver/period breakdown -->
    <v-dialog v-model="groupDialog" max-width="960">
      <v-card>
        <v-card-title>{{ selectedGroup?.driverName }} -- {{ selectedGroup?.weekStart }} &rarr; {{ selectedGroup?.weekEnd }}</v-card-title>
        <v-card-text>
          <v-table density="compact">
            <thead><tr><th>Date</th><th>From</th><th>To</th><th>Odometer</th><th>Mileage</th><th>Notes</th><th>Flag</th><th></th></tr></thead>
            <tbody>
              <tr v-for="entry in groupEntries" :key="entry.id" :class="{ flagged: entry.thresholdFlag }">
                <td>{{ entry.workDate }}</td>
                <td>{{ entry.startingLocation }}</td>
                <td>{{ entry.endingLocation }}</td>
                <td>
                  <span v-if="entry.driverOdometerStart !== null && entry.driverOdometerStart !== undefined">
                    {{ entry.driverOdometerStart }} &rarr; {{ entry.driverOdometerEnd }}
                  </span>
                  <span v-else class="text-medium-emphasis">--</span>
                </td>
                <td>{{ entry.claimedMileage }} mi</td>
                <td class="explanation">{{ entry.notes }}</td>
                <td><v-icon v-if="entry.thresholdFlag" color="warning">mdi-alert</v-icon></td>
                <td class="text-no-wrap">
                  <v-btn
                    v-if="entry.photoPath"
                    size="small" variant="text" icon="mdi-image"
                    :href="photoUrl(entry.photoPath)" target="_blank" rel="noopener noreferrer"
                    title="View uploaded photo"
                  />
                  <v-btn size="small" color="primary" variant="text" @click="openVerify(entry)">Verify</v-btn>
                </td>
              </tr>
              <tr v-if="!groupEntries.length">
                <td colspan="8" class="text-center text-medium-emphasis">All entries in this batch have been verified.</td>
              </tr>
            </tbody>
          </v-table>
        </v-card-text>
        <v-card-actions><v-spacer /><v-btn variant="text" @click="groupDialog = false">Close</v-btn></v-card-actions>
      </v-card>
    </v-dialog>

    <VerifyEntryDialog v-model="verifyDialog" :entry="selectedEntry" :carers="carers" @verified="onVerified" />
  </v-container>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { fetchCarers, fetchMileageEntries } from '../../services/mileageService';
import { PHP_API_BASE } from '../../utils/phpApi';
import VerifyEntryDialog from './VerifyEntryDialog.vue';

const entries = ref([]);
const carers = ref([]);
const driverFilter = ref('');
const groupDialog = ref(false);
const verifyDialog = ref(false);
const selectedGroupKey = ref('');
const selectedEntry = ref(null);

const photoUrl = (photoPath) => `${PHP_API_BASE}/uploads/${photoPath}`;
const formatDate = (value) => (value ? String(value).replace('T', ' ').slice(0, 16) : '--');

const groupKey = (entry) => `${entry.driverName}||${entry.submissionWeekStart}||${entry.submissionWeekEnd}`;

// Only entries submitted via the driver portal and not yet verified against
// Access belong in this triage queue -- once verified they move on to the
// Verify Mileage / Manager Approval screens.
const unverifiedEntries = computed(() => entries.value.filter((e) => e.source === 'driver_portal' && !e.verifiedAt));

const groups = computed(() => {
  const map = new Map();
  for (const entry of unverifiedEntries.value) {
    if (driverFilter.value && !entry.driverName.toLowerCase().includes(driverFilter.value.toLowerCase())) {
      continue;
    }
    const key = groupKey(entry);
    if (!map.has(key)) {
      map.set(key, {
        key,
        driverName: entry.driverName,
        weekStart: entry.submissionWeekStart,
        weekEnd: entry.submissionWeekEnd,
        entries: [],
        totalClaimed: 0,
        flaggedCount: 0,
        latestSubmittedAt: null,
      });
    }
    const group = map.get(key);
    group.entries.push(entry);
    group.totalClaimed = Math.round((group.totalClaimed + Number(entry.claimedMileage || 0)) * 100) / 100;
    if (entry.thresholdFlag) group.flaggedCount += 1;
    if (entry.submittedAt && (!group.latestSubmittedAt || entry.submittedAt > group.latestSubmittedAt)) {
      group.latestSubmittedAt = entry.submittedAt;
    }
  }
  return Array.from(map.values()).sort((a, b) => (b.latestSubmittedAt || '').localeCompare(a.latestSubmittedAt || ''));
});

const selectedGroup = computed(() => groups.value.find((g) => g.key === selectedGroupKey.value) || null);
const groupEntries = computed(() => unverifiedEntries.value.filter((e) => groupKey(e) === selectedGroupKey.value));

const load = async () => {
  entries.value = (await fetchMileageEntries({ source: 'driver_portal' })).entries || [];
};
const loadCarers = async () => {
  try {
    carers.value = (await fetchCarers()).carers || [];
  } catch {
    carers.value = [];
  }
};

const openGroup = (group) => {
  selectedGroupKey.value = group.key;
  groupDialog.value = true;
};
const openVerify = (entry) => {
  selectedEntry.value = entry;
  verifyDialog.value = true;
};
const onVerified = async () => {
  await load();
  // Auto-close the batch dialog once every entry in it has been verified.
  if (selectedGroupKey.value && !groupEntries.value.length) {
    groupDialog.value = false;
  }
};

onMounted(() => {
  load();
  loadCarers();
});
</script>

<style scoped>
.v-card{border-radius:8px}.flagged{background:#fff8e1}.explanation{max-width:200px;white-space:normal}
</style>
