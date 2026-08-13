<template>
  <v-container fluid>
    <v-card variant="outlined" class="panel-card">
      <v-card-title class="d-flex align-center">
        Carer Directory
        <v-spacer />
        <v-btn color="primary" prepend-icon="mdi-plus" @click="openForm(null)">Add Carer</v-btn>
      </v-card-title>
      <v-card-text>
        <v-alert type="info" variant="tonal" class="mb-3">
          Home addresses recorded here are looked up automatically when office staff verify a driver's mileage claim
          against Access Care Planning -- covering the commute leg from home to the first client and back.
        </v-alert>
        <v-alert v-if="error" type="error" variant="tonal" class="mb-3">{{ error }}</v-alert>
        <v-table density="comfortable">
          <thead><tr><th>Carer</th><th>Home Address</th><th>Postcode</th><th>Mobile</th><th>Email</th><th>Status</th><th style="width:110px"></th></tr></thead>
          <tbody>
            <tr v-for="carer in carers" :key="carer.id">
              <td>{{ carer.title ? carer.title + ' ' : '' }}{{ carer.driverName }}</td>
              <td>{{ addressSummary(carer) }}</td>
              <td>{{ carer.postcode || '-' }}</td>
              <td>{{ carer.mobilePhone || '-' }}</td>
              <td>{{ carer.email || '-' }}</td>
              <td><v-chip size="small" :color="carer.isActive ? 'success' : 'grey'" variant="tonal">{{ carer.isActive ? 'Active' : 'Inactive' }}</v-chip></td>
              <td class="text-no-wrap">
                <v-btn size="small" variant="text" icon="mdi-pencil" @click="openForm(carer)" />
                <v-btn size="small" variant="text" icon="mdi-delete" @click="remove(carer)" />
              </td>
            </tr>
            <tr v-if="!loading && !carers.length">
              <td colspan="7" class="text-center text-medium-emphasis">No carers added yet.</td>
            </tr>
          </tbody>
        </v-table>
      </v-card-text>
    </v-card>

    <v-dialog v-model="dialog" max-width="620">
      <v-card>
        <v-card-title>{{ form.id ? 'Edit Carer' : 'Add Carer' }}</v-card-title>
        <v-card-text>
          <div class="text-overline text-medium-emphasis mb-1">Name</div>
          <v-row dense>
            <v-col cols="12" md="3">
              <v-select v-model="form.title" :items="['Mr', 'Mrs', 'Miss', 'Ms', 'Dr']" label="Title" variant="outlined" clearable />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field v-model="form.firstName" label="First name*" variant="outlined" />
            </v-col>
            <v-col cols="12" md="5">
              <v-text-field v-model="form.lastName" label="Last name*" variant="outlined" />
            </v-col>
          </v-row>

          <div class="text-overline text-medium-emphasis mb-1 mt-2">Contact</div>
          <v-row dense>
            <v-col cols="12" md="6">
              <v-text-field v-model="form.mobilePhone" label="Mobile" variant="outlined" />
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field v-model="form.email" label="Email" variant="outlined" />
            </v-col>
          </v-row>

          <div class="text-overline text-medium-emphasis mb-1 mt-2">Home Address</div>
          <v-text-field v-model="form.addressLine1" label="Address line 1" variant="outlined" class="mb-2" />
          <v-text-field v-model="form.addressLine2" label="Address line 2" variant="outlined" class="mb-2" />
          <v-row dense>
            <v-col cols="12" md="5">
              <v-text-field v-model="form.townCity" label="Town / City" variant="outlined" />
            </v-col>
            <v-col cols="12" md="4">
              <v-text-field v-model="form.county" label="County" variant="outlined" />
            </v-col>
            <v-col cols="12" md="3">
              <v-text-field v-model="form.postcode" label="Postcode" variant="outlined" />
            </v-col>
          </v-row>

          <div class="text-overline text-medium-emphasis mb-1 mt-2">Other</div>
          <v-textarea v-model="form.notes" label="Notes" variant="outlined" rows="2" class="mb-2" />
          <v-switch v-model="form.isActive" label="Active" color="primary" hide-details />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
          <v-btn color="primary" :loading="saving" @click="save">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { deleteCarer, fetchCarers, saveCarer } from '../../services/mileageService';

const carers = ref([]);
const loading = ref(false);
const error = ref('');
const dialog = ref(false);
const saving = ref(false);
const form = reactive({
  id: null,
  firstName: '',
  lastName: '',
  title: '',
  addressLine1: '',
  addressLine2: '',
  townCity: '',
  county: '',
  postcode: '',
  mobilePhone: '',
  email: '',
  notes: '',
  isActive: true,
});

const addressSummary = (carer) => {
  const parts = [carer.addressLine1, carer.addressLine2, carer.townCity, carer.county].filter(Boolean);
  return parts.length ? parts.join(', ') : (carer.homeAddress || '-');
};

const load = async () => {
  loading.value = true;
  error.value = '';
  try {
    carers.value = (await fetchCarers()).carers || [];
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load carer directory.';
  } finally {
    loading.value = false;
  }
};

const openForm = (carer) => {
  form.id = carer?.id || null;
  form.firstName = carer?.firstName || '';
  form.lastName = carer?.lastName || '';
  form.title = carer?.title || '';
  form.addressLine1 = carer?.addressLine1 || '';
  form.addressLine2 = carer?.addressLine2 || '';
  form.townCity = carer?.townCity || '';
  form.county = carer?.county || '';
  form.postcode = carer?.postcode || '';
  form.mobilePhone = carer?.mobilePhone || '';
  form.email = carer?.email || '';
  form.notes = carer?.notes || '';
  form.isActive = carer ? carer.isActive : true;
  dialog.value = true;
};

const save = async () => {
  if (!form.firstName.trim() || !form.lastName.trim()) {
    error.value = 'First name and last name are required.';
    return;
  }
  saving.value = true;
  error.value = '';
  try {
    await saveCarer({ ...form });
    dialog.value = false;
    await load();
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to save carer.';
  } finally {
    saving.value = false;
  }
};

const remove = async (carer) => {
  if (!window.confirm(`Remove ${carer.driverName} from the carer directory?`)) return;
  error.value = '';
  try {
    await deleteCarer(carer.id);
    await load();
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to delete carer.';
  }
};

onMounted(load);
</script>

<style scoped>
.panel-card { border-radius: 8px; }
</style>
