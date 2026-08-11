<template>
  <v-container fluid class="routing-page">
    <section class="routing-shell">
      <header class="shell-header">
        <div>
          <p class="header-kicker">Inbox</p>
          <h1>Email Routing</h1>
          <p class="header-copy">
            Choose which inbox email address receives website submissions for each category.
          </p>
        </div>
        <div class="header-actions">
          <v-btn variant="outlined" color="primary" prepend-icon="mdi-refresh" :loading="loading" @click="loadSettings">
            Refresh
          </v-btn>
          <v-btn color="primary" prepend-icon="mdi-content-save-outline" :loading="saving" :disabled="saving || !canSave" @click="saveSettings">
            Save Routing
          </v-btn>
        </div>
      </header>

      <v-alert v-if="!canViewPage" type="warning" variant="tonal" class="mb-4">
        Your role does not currently have access to inbox email routing.
      </v-alert>

      <template v-else>
        <div class="stats-grid">
          <v-card class="stat-card" elevation="0">
            <p>Categories</p>
            <h3>{{ categoryCards.length }}</h3>
          </v-card>
          <v-card class="stat-card" elevation="0">
            <p>Configured</p>
            <h3>{{ configuredCount }}</h3>
          </v-card>
          <v-card class="stat-card" elevation="0">
            <p>Pending Changes</p>
            <h3>{{ dirtyCount }}</h3>
          </v-card>
        </div>

        <v-alert type="info" variant="tonal" class="mb-4">
          Enter one or more email addresses separated by commas. Leave a category blank only if you intentionally want to disable email notifications for it.
        </v-alert>

        <div class="routing-grid">
          <v-card v-for="item in categoryCards" :key="item.key" class="routing-card" elevation="0">
            <v-card-item>
              <template #prepend>
                <v-avatar size="42" color="primary" variant="tonal">
                  <v-icon :icon="item.icon"></v-icon>
                </v-avatar>
              </template>
              <v-card-title>{{ item.label }}</v-card-title>
              <v-card-subtitle>{{ item.subtitle }}</v-card-subtitle>
            </v-card-item>

            <v-card-text>
              <v-textarea
                v-model="form[item.key]"
                :label="`${item.label} recipient email(s)`"
                auto-grow
                rows="2"
                variant="outlined"
                :error-messages="fieldErrors[item.key] ? [fieldErrors[item.key]] : []"
                placeholder="name@example.com, second@example.com"
              ></v-textarea>

              <div class="meta-row">
                <span>{{ metaLabel(item.key) }}</span>
                <v-chip size="small" :color="recipientStatusColor(item.key)" variant="tonal">
                  {{ recipientStatusText(item.key) }}
                </v-chip>
              </div>
            </v-card-text>
          </v-card>
        </div>
      </template>
    </section>
  </v-container>
</template>

<script>
import {
  describeAuthApiError,
  fetchSessionUser,
  getMessageRoutingSettings,
  getStoredCurrentUser,
  saveMessageRoutingSettings,
} from '../services/authApi';
import {
  hasPermission,
  normalizeCurrentUser,
} from '../utils/accessControl';

const CATEGORY_ORDER = [
  'general_enquiries',
  'job_applications',
  'complaints',
  'care_thanks',
];

const CATEGORY_META = {
  general_enquiries: {
    icon: 'mdi-email-outline',
    subtitle: 'General website contact submissions.',
  },
  job_applications: {
    icon: 'mdi-briefcase-account-outline',
    subtitle: 'Applications submitted from the careers page.',
  },
  complaints: {
    icon: 'mdi-alert-circle-outline',
    subtitle: 'Raise a concern / complaint submissions.',
  },
  care_thanks: {
    icon: 'mdi-thumb-up-outline',
    subtitle: 'Thank a caregiver messages from the public site.',
  },
};

export default {
  name: 'MessageRouting',
  data() {
    return {
      loading: false,
      saving: false,
      currentUser: normalizeCurrentUser(getStoredCurrentUser()),
      categories: {},
      settings: {},
      form: {},
      fieldErrors: {},
      savedSnapshot: {},
    };
  },
  computed: {
    canViewPage() {
      return hasPermission(this.currentUser, 'inbox.email_routing');
    },
    canSave() {
      return this.canViewPage && this.dirtyCount > 0;
    },
    categoryCards() {
      return CATEGORY_ORDER.map((key) => ({
        key,
        label: this.categories[key]?.label || this.fallbackLabel(key),
        icon: CATEGORY_META[key]?.icon || 'mdi-email-outline',
        subtitle: CATEGORY_META[key]?.subtitle || 'Website inbox notification routing.',
      }));
    },
    dirtyCount() {
      return CATEGORY_ORDER.filter((key) => (this.form[key] || '') !== (this.savedSnapshot[key] || '')).length;
    },
    configuredCount() {
      return CATEGORY_ORDER.filter((key) => String(this.form[key] || '').trim() !== '').length;
    },
  },
  created() {
    this.initializePage();
  },
  methods: {
    async initializePage() {
      if (!this.currentUser) {
        try {
          const result = await fetchSessionUser();
          this.currentUser = normalizeCurrentUser(result.user);
        } catch (error) {
          return;
        }
      }

      if (this.canViewPage) {
        this.loadSettings();
      }
    },
    fallbackLabel(key) {
      return String(key || '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
    },
    setFormFromSettings(settings) {
      const nextForm = {};
      CATEGORY_ORDER.forEach((key) => {
        nextForm[key] = String(settings?.[key]?.recipients || '');
      });
      this.form = nextForm;
      this.savedSnapshot = { ...nextForm };
    },
    async loadSettings() {
      if (!this.canViewPage) {
        return;
      }

      this.loading = true;
      this.fieldErrors = {};
      try {
        const payload = await getMessageRoutingSettings();
        this.categories = payload.categories || {};
        this.settings = payload.settings || {};
        this.setFormFromSettings(this.settings);
      } catch (error) {
        const message = describeAuthApiError(error, 'Failed to load inbox email routing.');
        this.$store.commit('showSnackbar', { message, type: 'error' });
      } finally {
        this.loading = false;
      }
    },
    async saveSettings() {
      if (!this.canViewPage) {
        return;
      }

      this.saving = true;
      this.fieldErrors = {};
      try {
        const payload = {};
        CATEGORY_ORDER.forEach((key) => {
          payload[key] = this.form[key] || '';
        });

        const result = await saveMessageRoutingSettings(payload);
        this.settings = result.settings || {};
        this.setFormFromSettings(this.settings);
        this.$store.commit('showSnackbar', {
          message: result.message || 'Inbox email routing updated successfully.',
          type: 'success',
        });
      } catch (error) {
        const serverErrors = error?.responseData?.errors || error?.response?.data?.errors;
        this.fieldErrors = serverErrors && typeof serverErrors === 'object'
          ? serverErrors
          : {};
        const message = describeAuthApiError(error, 'Failed to save inbox email routing.');
        this.$store.commit('showSnackbar', { message, type: 'error' });
      } finally {
        this.saving = false;
      }
    },
    metaLabel(key) {
      const updatedAt = this.settings?.[key]?.updatedAt || '';
      if (!updatedAt) {
        return 'Not saved in the database yet.';
      }

      const parsed = new Date(updatedAt);
      if (Number.isNaN(parsed.getTime())) {
        return 'Last updated: ' + updatedAt;
      }

      return 'Last updated: ' + parsed.toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      });
    },
    recipientStatusText(key) {
      return String(this.form[key] || '').trim() !== '' ? 'Configured' : 'No recipient';
    },
    recipientStatusColor(key) {
      return String(this.form[key] || '').trim() !== '' ? 'success' : 'warning';
    },
  },
};
</script>

<style scoped>
.routing-page {
  background: #f6f7fb;
  min-height: 100%;
  padding: 24px;
}

.routing-shell {
  display: grid;
  gap: 20px;
}

.shell-header {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: flex-start;
}

.header-kicker {
  margin: 0 0 8px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 0.78rem;
  color: #7a6a7f;
}

.header-copy {
  margin: 8px 0 0;
  max-width: 720px;
  color: #5f6473;
}

.header-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
}

.stat-card {
  border-radius: 20px;
  border: 1px solid rgba(171, 32, 125, 0.12);
  background: #fff;
}

.stat-card :deep(.v-card-text),
.stat-card {
  padding: 18px 20px;
}

.stat-card p {
  margin: 0;
  color: #7a6a7f;
}

.stat-card h3 {
  margin: 6px 0 0;
  font-size: 1.7rem;
  color: #1e2030;
}

.routing-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.routing-card {
  border-radius: 22px;
  border: 1px solid rgba(171, 32, 125, 0.12);
  background: #fff;
}

.meta-row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: center;
  color: #6a6f7f;
  font-size: 0.88rem;
}

@media (max-width: 960px) {
  .shell-header {
    flex-direction: column;
  }

  .header-actions {
    width: 100%;
    justify-content: flex-start;
  }

  .stats-grid,
  .routing-grid {
    grid-template-columns: 1fr;
  }
}
</style>
