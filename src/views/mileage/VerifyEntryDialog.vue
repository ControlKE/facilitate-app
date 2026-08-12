<template>
  <!-- Verification dialog: office compares the driver's claim against
       Access Care Planning, entering the day's route total plus the
       home<->work commute legs Access doesn't include. Shared between the
       Verify Mileage page and the New Mileage Submissions triage screen. -->
  <v-dialog :model-value="modelValue" max-width="640" @update:model-value="$emit('update:modelValue', $event)">
    <v-card>
      <v-card-title>Verify Against Access Care Planning</v-card-title>
      <v-card-text>
        <v-alert type="info" variant="tonal" class="mb-3">
          Driver claimed <strong>{{ entry?.adjustedClaimedMileage }} mi</strong> on {{ entry?.workDate }}.
          Enter the Access route total and commute legs below -- the expected total is calculated automatically.
        </v-alert>
        <v-alert v-if="carerHomeAddress" type="success" variant="tonal" class="mb-3" density="compact">
          {{ entry?.driverName }}'s home address on file: <strong>{{ carerHomeAddress }}</strong>
        </v-alert>
        <v-alert v-else type="warning" variant="tonal" class="mb-3" density="compact">
          No home address on file for {{ entry?.driverName }} -- add one in the Carer Directory for next time.
        </v-alert>

        <v-text-field
          v-model.number="verify.accessRunTotalMileage"
          label="Access route total (client-to-client, excludes commute)*"
          type="number" min="0" variant="outlined" class="mb-2"
        />
        <v-row dense>
          <v-col cols="12" md="6">
            <v-text-field
              v-model.number="verify.homeToFirstClientMileage"
              label="Home -> colleague -> 1st client (mi)"
              type="number" min="0" variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model.number="verify.lastClientToHomeMileage"
              label="Last client -> colleague -> home (mi)"
              type="number" min="0" variant="outlined"
            />
          </v-col>
        </v-row>
        <v-text-field
          v-model="verify.colleagueAddress"
          label="Colleague pickup address (if applicable today)"
          variant="outlined" class="mb-2"
        />

        <v-checkbox
          v-model="verify.isHalfDaySwap"
          label="Half-day colleague swap (dropped one colleague home at lunch, collected another)"
          hide-details class="mb-2"
        />
        <template v-if="verify.isHalfDaySwap">
          <v-text-field
            v-model.number="verify.middayColleagueSwapMileage"
            label="Midday colleague swap mileage (drop-off + pickup, mi)*"
            type="number" min="0" variant="outlined" class="mb-2"
          />
          <v-row dense>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="verify.middayDropoffColleagueAddress"
                label="Morning colleague -- dropped off at"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="verify.middayPickupColleagueAddress"
                label="Afternoon colleague -- collected from"
                variant="outlined"
              />
            </v-col>
          </v-row>
        </template>

        <v-divider class="my-3" />
        <div class="d-flex justify-space-between text-body-2">
          <span>Expected total (Access + commute{{ verify.isHalfDaySwap ? ' + swap' : '' }})</span><strong>{{ verifyExpectedTotal }} mi</strong>
        </div>
        <div class="d-flex justify-space-between text-body-2">
          <span>+ Passenger pickup allowance</span><strong>{{ entry?.passengerPickupMileage || 0 }} mi</strong>
        </div>
        <div class="d-flex justify-space-between text-body-1 font-weight-bold mt-1">
          <span>Expected total mileage</span><span>{{ verifyExpectedGrandTotal }} mi</span>
        </div>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="close">Cancel</v-btn>
        <v-btn color="primary" :loading="verifying" @click="save">Send for Manager Approval</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { verifyMileageEntry } from '../../services/mileageService';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  entry: { type: Object, default: null },
  carers: { type: Array, default: () => [] },
});
const emit = defineEmits(['update:modelValue', 'verified']);

const verifying = ref(false);
const verify = reactive({
  accessRunTotalMileage: 0,
  homeToFirstClientMileage: 0,
  lastClientToHomeMileage: 0,
  colleagueAddress: '',
  isHalfDaySwap: false,
  middayColleagueSwapMileage: 0,
  middayDropoffColleagueAddress: '',
  middayPickupColleagueAddress: '',
});

watch(
  () => [props.modelValue, props.entry],
  ([open, entry]) => {
    if (!open || !entry) return;
    verify.accessRunTotalMileage = entry.accessRunTotalMileage || 0;
    verify.homeToFirstClientMileage = entry.homeToFirstClientMileage || 0;
    verify.lastClientToHomeMileage = entry.lastClientToHomeMileage || 0;
    verify.colleagueAddress = entry.colleagueAddress || '';
    verify.isHalfDaySwap = Boolean(entry.isHalfDaySwap);
    verify.middayColleagueSwapMileage = entry.middayColleagueSwapMileage || 0;
    verify.middayDropoffColleagueAddress = entry.middayDropoffColleagueAddress || '';
    verify.middayPickupColleagueAddress = entry.middayPickupColleagueAddress || '';
  },
  { immediate: true }
);

const carerHomeAddress = computed(() => {
  const match = props.carers.find((c) => c.driverName.toLowerCase() === (props.entry?.driverName || '').toLowerCase());
  return match?.homeAddress || '';
});
const verifyExpectedTotal = computed(() => {
  const swap = verify.isHalfDaySwap ? Number(verify.middayColleagueSwapMileage || 0) : 0;
  const total = Number(verify.accessRunTotalMileage || 0) + Number(verify.homeToFirstClientMileage || 0) + Number(verify.lastClientToHomeMileage || 0) + swap;
  return Math.round(total * 100) / 100;
});
const verifyExpectedGrandTotal = computed(() => {
  const total = verifyExpectedTotal.value + Number(props.entry?.passengerPickupMileage || 0);
  return Math.round(total * 100) / 100;
});

const close = () => emit('update:modelValue', false);
const save = async () => {
  if (!props.entry) return;
  verifying.value = true;
  try {
    await verifyMileageEntry(props.entry.id, verify);
    emit('verified', props.entry.id);
    close();
  } finally {
    verifying.value = false;
  }
};
</script>
