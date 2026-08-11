<template>
  <v-container fluid>
    <v-card variant="outlined" class="settings-card">
      <v-card-title>Mileage Settings</v-card-title>
      <v-card-text>
        <v-alert v-if="message" :type="messageType" variant="tonal" class="mb-4">{{ message }}</v-alert>
        <v-row dense>
          <v-col cols="12" md="4">
            <v-text-field
              v-model.number="form.thresholdMiles"
              label="Review threshold miles"
              type="number"
              min="0"
              step="0.5"
              variant="outlined"
              hint="Entries above expected mileage by more than this value need explanation and admin review."
              persistent-hint
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field
              v-model.number="form.mileageRate"
              label="Mileage rate"
              type="number"
              min="0"
              step="0.01"
              prefix="£"
              variant="outlined"
              hint="Default payment rate per mile."
              persistent-hint
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-select
              v-model="form.weekStartsOn"
              :items="weekDays"
              label="Week starts on"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-select
              v-model="form.submissionDueDay"
              :items="weekDays"
              label="Submission due day"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="form.paymentWindow"
              label="Payment window"
              variant="outlined"
            />
          </v-col>
        </v-row>
        <v-btn color="primary" :loading="saving" @click="save">Save Settings</v-btn>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { currentMileageUser, fetchMileageSettings, updateMileageSettings } from '../../services/mileageService';

const weekDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
const form = reactive({
  thresholdMiles: 10,
  mileageRate: 0.3,
  weekStartsOn: 'wednesday',
  submissionDueDay: 'tuesday',
  paymentWindow: 'thursday-friday',
});
const saving = ref(false);
const message = ref('');
const messageType = ref('success');

const load = async () => {
  const data = await fetchMileageSettings();
  Object.assign(form, data.settings || {});
};

const save = async () => {
  saving.value = true;
  message.value = '';
  try {
    const user = currentMileageUser();
    const data = await updateMileageSettings({ ...form, updatedBy: user.userId });
    Object.assign(form, data.settings || {});
    message.value = 'Mileage settings saved.';
    messageType.value = 'success';
  } catch (error) {
    message.value = error instanceof Error ? error.message : 'Failed to save settings.';
    messageType.value = 'error';
  } finally {
    saving.value = false;
  }
};

onMounted(load);
</script>

<style scoped>
.settings-card{border-radius:8px}
</style>
