<template>
  <v-container fluid>
    <v-card variant="outlined" class="panel-card">
      <v-card-title class="d-flex align-center">
        Run Directory
        <v-spacer />
        <v-btn color="primary" prepend-icon="mdi-plus" @click="openForm(null)">Add Run</v-btn>
      </v-card-title>
      <v-card-text>
        <v-alert type="info" variant="tonal" class="mb-3">
          Runs are the geographic rounds/areas carers work. This list populates the Run dropdown on the driver
          mileage submission form -- a driver may work a different run each day.
        </v-alert>
        <v-alert v-if="error" type="error" variant="tonal" class="mb-3">{{ error }}</v-alert>
        <v-table density="comfortable">
          <thead><tr><th>Run</th><th>Status</th><th style="width:110px"></th></tr></thead>
          <tbody>
            <tr v-for="run in runs" :key="run.id">
              <td>{{ run.name }}</td>
              <td><v-chip size="small" :color="run.isActive ? 'success' : 'grey'" variant="tonal">{{ run.isActive ? 'Active' : 'Inactive' }}</v-chip></td>
              <td class="text-no-wrap">
                <v-btn size="small" variant="text" icon="mdi-pencil" @click="openForm(run)" />
                <v-btn size="small" variant="text" icon="mdi-delete" @click="remove(run)" />
              </td>
            </tr>
            <tr v-if="!loading && !runs.length">
              <td colspan="3" class="text-center text-medium-emphasis">No runs added yet.</td>
            </tr>
          </tbody>
        </v-table>
      </v-card-text>
    </v-card>

    <v-dialog v-model="dialog" max-width="420">
      <v-card>
        <v-card-title>{{ form.id ? 'Edit Run' : 'Add Run' }}</v-card-title>
        <v-card-text>
          <v-text-field v-model="form.name" label="Run name*" variant="outlined" class="mb-2" />
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
import { deleteRun, fetchRuns, saveRun } from '../../services/mileageService';

const runs = ref([]);
const loading = ref(false);
const error = ref('');
const dialog = ref(false);
const saving = ref(false);
const form = reactive({ id: null, name: '', isActive: true });

const load = async () => {
  loading.value = true;
  error.value = '';
  try {
    runs.value = (await fetchRuns()).runs || [];
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load run directory.';
  } finally {
    loading.value = false;
  }
};

const openForm = (run) => {
  form.id = run?.id || null;
  form.name = run?.name || '';
  form.isActive = run ? run.isActive : true;
  dialog.value = true;
};

const save = async () => {
  if (!form.name.trim()) {
    error.value = 'Run name is required.';
    return;
  }
  saving.value = true;
  error.value = '';
  try {
    await saveRun({ ...form });
    dialog.value = false;
    await load();
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to save run.';
  } finally {
    saving.value = false;
  }
};

const remove = async (run) => {
  if (!window.confirm(`Remove "${run.name}" from the run directory?`)) return;
  error.value = '';
  try {
    await deleteRun(run.id);
    await load();
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to delete run.';
  }
};

onMounted(load);
</script>

<style scoped>
.panel-card { border-radius: 8px; }
</style>
