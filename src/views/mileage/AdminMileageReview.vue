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
      <v-card-title>{{ pageTitle }}</v-card-title>
      <v-card-text>
        <v-alert v-if="isManagerApprovalView && !canFinalApprove" type="warning" variant="tonal" class="mb-3">
          Your role does not have Final Mileage Approval permission -- you can view these entries but cannot approve or reject them.
        </v-alert>
        <v-table density="compact">
          <thead><tr><th>Date</th><th>Driver</th><th>Source</th><th>Claimed</th><th>Midday Add-on</th><th>Adjusted</th><th>System</th><th>Diff</th><th>Flag</th><th>Verified</th><th>Explanation</th><th>Status</th><th>Final</th><th>Amount</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-for="entry in entries" :key="entry.id" :class="{ flagged: entry.thresholdFlag }">
              <td>{{ entry.workDate }}</td><td>{{ entry.driverName }}</td>
              <td>
                <v-chip v-if="entry.source === 'driver_portal'" size="small" color="info" variant="tonal">Driver portal</v-chip>
                <span v-else class="text-caption text-medium-emphasis">Office</span>
              </td>
              <td>{{ entry.claimedMileage }}</td><td>{{ entry.middayPayableMileage }}</td><td>{{ entry.adjustedClaimedMileage }}</td><td>{{ entry.expectedSystemMileage }}</td><td>{{ entry.differenceFromSystem }}</td>
              <td><v-icon :color="entry.thresholdFlag ? 'warning' : 'success'">{{ entry.thresholdFlag ? 'mdi-alert' : 'mdi-check' }}</v-icon></td>
              <td><v-icon v-if="entry.verifiedAt" color="success" title="Verified against Access Care Planning">mdi-check-decagram</v-icon><span v-else class="text-caption text-medium-emphasis">Not yet</span></td>
              <td class="explanation">{{ entry.driverExplanation }}</td>
              <td><v-chip size="small" :color="statusColor(entry.adminStatus)" variant="tonal">{{ statusLabel(entry.adminStatus) }}</v-chip></td>
              <td>{{ entry.finalPayableMileage ?? 'Review' }}</td><td>{{ gbp(entry.finalPayableAmount) }}</td>
              <td class="text-no-wrap">
                <v-btn
                  v-if="entry.photoPath"
                  size="small"
                  variant="text"
                  icon="mdi-image"
                  :href="photoUrl(entry.photoPath)"
                  target="_blank"
                  rel="noopener noreferrer"
                  title="View uploaded photo"
                />
                <v-btn
                  v-if="!entry.verifiedAt"
                  size="small"
                  color="primary"
                  variant="text"
                  @click="openVerify(entry)"
                >
                  Verify
                </v-btn>
                <v-btn
                  v-else
                  size="small"
                  color="primary"
                  variant="text"
                  :disabled="entry.adminStatus === 'pending_manager_approval' && !canFinalApprove"
                  @click="openReview(entry)"
                >
                  Review
                </v-btn>
              </td>
            </tr>
            <tr v-if="!entries.length">
              <td colspan="15" class="text-center text-medium-emphasis">No entries match these filters.</td>
            </tr>
          </tbody>
        </v-table>
      </v-card-text>
    </v-card>

    <VerifyEntryDialog v-model="verifyDialog" :entry="selected" :carers="carers" @verified="load" />

    <!-- Final approve/reject/adjust dialog -->
    <v-dialog v-model="dialog" max-width="560">
      <v-card>
        <v-card-title>Review Mileage</v-card-title>
        <v-card-text>
          <v-alert v-if="selected?.source === 'driver_portal'" type="info" variant="tonal" class="mb-3">
            Submitted via the driver mileage portal.
            <span v-if="selected?.submitterPhone">Phone: {{ selected.submitterPhone }}.</span>
            <span v-if="selected?.submitterEmail">Email: {{ selected.submitterEmail }}.</span>
            <span v-if="selected?.startingLocation">From: {{ selected.startingLocation }}.</span>
            <span v-if="selected?.endingLocation">To: {{ selected.endingLocation }}.</span>
            <span v-if="selected?.driverOdometerStart !== null && selected?.driverOdometerStart !== undefined">
              Odometer: {{ selected.driverOdometerStart }} &rarr; {{ selected.driverOdometerEnd }}.
            </span>
            <a v-if="selected?.photoPath" :href="photoUrl(selected.photoPath)" target="_blank" rel="noopener noreferrer">View attached photo</a>
          </v-alert>
          <v-alert v-if="selected?.verifiedAt" type="success" variant="tonal" class="mb-3" density="compact">
            Verified against Access: {{ selected?.accessRunTotalMileage }} mi route
            + {{ selected?.homeToFirstClientMileage || 0 }} mi out
            + {{ selected?.lastClientToHomeMileage || 0 }} mi back
            <span v-if="selected?.colleagueAddress">(colleague: {{ selected.colleagueAddress }})</span>
            <span v-if="selected?.isHalfDaySwap">
              + {{ selected?.middayColleagueSwapMileage || 0 }} mi midday colleague swap
              <span v-if="selected?.middayDropoffColleagueAddress || selected?.middayPickupColleagueAddress">
                (dropped at {{ selected.middayDropoffColleagueAddress || '?' }}, collected from {{ selected.middayPickupColleagueAddress || '?' }})
              </span>
            </span>
            = {{ selected?.expectedSystemMileage }} mi expected.
          </v-alert>
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
import { computed, onMounted, reactive, ref } from 'vue';
import {
  currentMileageUser,
  fetchCarers,
  fetchMileageEntries,
  gbp,
  reviewMileageEntry,
  statusColor,
  statusLabel,
} from '../../services/mileageService';
import { PHP_API_BASE } from '../../utils/phpApi';
import { getStoredCurrentUser } from '../../services/authApi';
import { hasPermission } from '../../utils/accessControl';
import VerifyEntryDialog from './VerifyEntryDialog.vue';

const props = defineProps({
  defaultStatus: { type: String, default: '' },
});

const photoUrl = (photoPath) => `${PHP_API_BASE}/uploads/${photoPath}`;

const entries = ref([]);
const carers = ref([]);
const dialog = ref(false);
const verifyDialog = ref(false);
const selected = ref(null);
const filters = reactive({ driver: '', status: props.defaultStatus || 'pending_review', flaggedOnly: false });
const user = currentMileageUser();
const review = reactive({ status: 'approved', adminAdjustedPayableMileage: 0, adminNotes: '', reviewerId: user.userId, reviewerName: user.driverName });
const statusOptions = ['draft', 'submitted', 'pending_review', 'pending_manager_approval', 'approved', 'rejected', 'adjusted'];

const isManagerApprovalView = computed(() => props.defaultStatus === 'pending_manager_approval');
const pageTitle = computed(() => (isManagerApprovalView.value ? 'Manager Approval' : 'Verify Mileage'));
const canFinalApprove = computed(() => hasPermission(getStoredCurrentUser(), 'mileage.final_approval'));

const load = async () => {
  entries.value = (await fetchMileageEntries(filters)).entries || [];
};
const loadCarers = async () => {
  try {
    carers.value = (await fetchCarers()).carers || [];
  } catch {
    carers.value = [];
  }
};

const openVerify = (entry) => {
  selected.value = entry;
  verifyDialog.value = true;
};

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

onMounted(() => {
  load();
  loadCarers();
});
</script>

<style scoped>
.v-card{border-radius:8px}.flagged{background:#fff8e1}.explanation{max-width:220px;white-space:normal}
</style>
