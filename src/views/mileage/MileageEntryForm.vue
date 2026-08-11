<template>
  <v-container fluid class="mileage-page">
    <v-card variant="outlined" class="panel-card">
      <v-card-title>{{ form.id ? 'Edit Mileage Entry' : 'New Mileage Entry' }}</v-card-title>
      <v-card-text>
        <v-alert v-if="error" type="error" variant="tonal" class="mb-3">{{ error }}</v-alert>
        <v-alert v-if="preview.thresholdFlag" type="warning" variant="tonal" class="mb-3">
          Claimed mileage is more than 10 miles above expected system mileage. A driver explanation and admin review are required.
        </v-alert>
        <v-form @submit.prevent="save">
          <v-row dense>
            <v-col cols="12" md="3"><v-text-field v-model="form.workDate" label="Work date" type="date" variant="outlined" density="compact" /></v-col>
            <v-col cols="12" md="3"><v-text-field v-model="form.driverName" label="Driver name" variant="outlined" density="compact" /></v-col>
            <v-col cols="12" md="3"><v-text-field v-model.number="form.odometerStart" label="Odometer start" type="number" min="0" variant="outlined" density="compact" /></v-col>
            <v-col cols="12" md="3"><v-text-field v-model.number="form.odometerEnd" label="Odometer end" type="number" min="0" variant="outlined" density="compact" /></v-col>
            <v-col cols="12" md="6"><v-text-field v-model="form.startingLocation" label="Starting location" variant="outlined" density="compact" /></v-col>
            <v-col cols="12" md="6"><v-text-field v-model="form.endingLocation" label="Ending location" variant="outlined" density="compact" /></v-col>
            <v-col cols="12" md="4"><v-text-field v-model.number="form.expectedSystemMileage" label="Access / expected mileage" type="number" min="0" variant="outlined" density="compact" /></v-col>
            <v-col cols="12" md="4"><v-text-field v-model.number="form.passengerPickupMileage" label="Passenger pickup allowance" type="number" min="0" variant="outlined" density="compact" hint="Office/admin allowance for pickup/drop-off circumstances." persistent-hint /></v-col>
            <v-col cols="12" md="4"><v-text-field v-model.number="form.middayPayableMileage" label="Authorised midday mileage" type="number" min="0" variant="outlined" density="compact" hint="Only add office-approved lunch/office/partner travel not already in the claim." persistent-hint /></v-col>
            <v-col cols="12" md="6"><v-text-field v-model="form.middayMileageReason" label="Midday mileage reason" variant="outlined" density="compact" /></v-col>
            <v-col cols="12"><v-textarea v-model="form.driverExplanation" label="Driver explanation" rows="2" variant="outlined" :required="preview.explanationRequired" /></v-col>
            <v-col cols="12"><v-textarea v-model="form.notes" label="Notes" rows="2" variant="outlined" /></v-col>
          </v-row>

          <v-row dense class="mb-4">
            <v-col v-for="item in previewCards" :key="item.label" cols="12" sm="6" md="2">
              <v-card variant="tonal" class="calc-card"><v-card-text><div class="text-caption">{{ item.label }}</div><strong>{{ item.value }}</strong></v-card-text></v-card>
            </v-col>
          </v-row>
          <div class="d-flex ga-2">
            <v-btn color="primary" type="submit" :loading="saving">Save Entry</v-btn>
            <v-btn variant="outlined" @click="$router.push({ name: 'mileageMine' })">Cancel</v-btn>
          </div>
        </v-form>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { calculateMileagePreview, currentMileageUser, fetchMileageEntry, fetchMileageSettings, gbp, saveMileageEntry } from '../../services/mileageService';

const route = useRoute();
const router = useRouter();
const today = new Date().toISOString().slice(0, 10);
const form = reactive({
  id: null, userId: 1, driverName: 'Current User', workDate: today, startingLocation: '', endingLocation: '',
  odometerStart: 0, odometerEnd: 0, expectedSystemMileage: 0, passengerPickupMileage: 0,
  middayPayableMileage: 0, middayMileageReason: '', wentHomeForLunch: false,
  lunchHomeMileageDeduction: 0, driverExplanation: '', notes: '',
});
const settings = ref({ mileageRate: 0.3, thresholdMiles: 10 });
const saving = ref(false);
const error = ref('');
const preview = computed(() => calculateMileagePreview(form, settings.value));
const previewCards = computed(() => [
  { label: 'Claimed', value: `${preview.value.claimedMileage} mi` },
  { label: 'Midday Add-on', value: `${preview.value.middayPayableMileage} mi` },
  { label: 'Adjusted', value: `${preview.value.adjustedClaimedMileage} mi` },
  { label: 'System', value: `${preview.value.expectedSystemMileage} mi` },
  { label: 'Expected Total', value: `${preview.value.expectedTotalMileage} mi` },
  { label: 'Difference', value: `${preview.value.differenceFromSystem} mi` },
  { label: 'Payable', value: preview.value.finalPayableMileage === null ? 'Review' : `${preview.value.finalPayableMileage} mi` },
  { label: 'Amount', value: preview.value.finalPayableAmount === null ? 'Review' : gbp(preview.value.finalPayableAmount) },
]);

const validate = () => {
  if (Number(form.odometerEnd) < Number(form.odometerStart)) return 'Odometer end must be greater than or equal to odometer start.';
  if (Number(form.lunchHomeMileageDeduction) > preview.value.claimedMileage) return 'Lunch deduction cannot exceed claimed mileage.';
  if (Number(form.expectedSystemMileage) < 0) return 'Expected system mileage cannot be negative.';
  if (Number(form.passengerPickupMileage) < 0) return 'Passenger pickup mileage cannot be negative.';
  if (Number(form.middayPayableMileage) < 0) return 'Authorised midday mileage cannot be negative.';
  if (preview.value.explanationRequired && !String(form.driverExplanation || '').trim()) return 'Explanation is required for threshold exceptions.';
  return '';
};

const save = async () => {
  error.value = validate();
  if (error.value) return;
  saving.value = true;
  try {
    await saveMileageEntry(form);
    router.push({ name: 'mileageMine' });
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to save mileage entry.';
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  const user = currentMileageUser();
  form.userId = user.userId;
  form.driverName = user.driverName;
  try {
    settings.value = (await fetchMileageSettings()).settings || settings.value;
  } catch {
    settings.value = { mileageRate: 0.3, thresholdMiles: 10 };
  }
  if (!route.query.id) return;
  const data = await fetchMileageEntry(route.query.id);
  Object.assign(form, data.entry || {});
  // Lunch-home deductions are legacy only. New edits use authorised midday additions instead.
  form.wentHomeForLunch = false;
  form.lunchHomeMileageDeduction = 0;
});
</script>

<style scoped>
.panel-card,.calc-card{border-radius:8px}
</style>
