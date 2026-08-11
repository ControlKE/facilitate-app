<template>
  <v-container fluid class="signup-page">
    <section class="signup-frame">
      <div class="frame-left">
        <div class="brand-row">
          <div class="brand-icon">
            <v-img src="/frontend/images/logo.png" alt="Facilitate Care Services" width="32" height="32"></v-img>
          </div>
          <div class="brand-copy">
            <p class="brand-name">Facilitate Care Services</p>
            <p class="brand-sub">USER MANAGEMENT</p>
          </div>
        </div>

        <h1>Create Staff<br>Credentials</h1>
        <p class="lead">
          Set up secure login credentials for new admin team members.
        </p>

        <ul class="feature-list">
          <li>
            <v-icon size="16">mdi-check-circle</v-icon>
            <span>Protected account setup</span>
          </li>
          <li>
            <v-icon size="16">mdi-account-group-outline</v-icon>
            <span>Fast onboarding for staff</span>
          </li>
          <li>
            <v-icon size="16">mdi-check-decagram</v-icon>
            <span>Credentials can be updated later.</span>
          </li>
        </ul>
      </div>

      <v-card class="signup-card" elevation="0">
        <v-card-text class="card-body">
          <p class="kicker">Sign Up for a</p>
          <h2>New User Account</h2>

          <v-alert
            v-if="!canManageAccounts"
            type="warning"
            variant="tonal"
            class="mb-4"
          >
            Your role does not currently allow account creation.
          </v-alert>

          <v-form ref="form" v-model="valid" @submit.prevent="submitForm">
            <v-text-field
              v-model="names"
              label="Full Name"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi-card-account-details-outline"
              hide-details="auto"
              color="#ab207d"
              class="field"
              :rules="[requiredRule('Full name')]"
              required
            ></v-text-field>

            <v-text-field
              v-model="email"
              label="Email"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi-email-outline"
              hide-details="auto"
              color="#ab207d"
              class="field"
              :rules="[requiredRule('Email'), emailRule]"
              required
            ></v-text-field>

            <v-text-field
              v-model="username"
              label="Username"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi-account-outline"
              hide-details="auto"
              color="#ab207d"
              class="field"
              :rules="[requiredRule('Username'), usernameRule]"
              required
            ></v-text-field>

            <v-text-field
              v-model="password"
              label="Password"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi-lock-outline"
              :type="showPassword ? 'text' : 'password'"
              :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
              hide-details="auto"
              color="#ab207d"
              class="field"
              :rules="[requiredRule('Password'), passwordRule]"
              @click:append-inner="showPassword = !showPassword"
              required
            ></v-text-field>

            <v-select
              v-model="roleKey"
              label="User Role"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi-shield-account-outline"
              hide-details="auto"
              color="#ab207d"
              class="field"
              :items="allowedRoleOptions"
              item-title="label"
              item-value="value"
              :rules="[requiredRule('Role')]"
              required
            ></v-select>

            <v-checkbox
              v-model="acceptedTerms"
              class="terms-check"
              density="compact"
              hide-details="auto"
              color="#ab207d"
              label="I confirm this user is authorized to access the admin system."
            ></v-checkbox>

            <v-btn
              type="submit"
              block
              size="large"
              class="create-btn"
              :loading="isSubmitting"
              :disabled="!canManageAccounts"
            >
              Create Account
            </v-btn>

            <p class="login-link-copy">
              Already have credentials?
              <router-link to="/login">Go to login</router-link>
            </p>
          </v-form>

          <div class="management-block">
            <h3 class="management-title">Current Users</h3>
            <v-alert
              v-if="!canManageAccounts"
              type="info"
              variant="tonal"
              class="mb-3"
            >
              User list is hidden because your role has no account-management access.
            </v-alert>
            <div v-else class="table-shell">
              <v-table density="compact">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in usersList" :key="item.id">
                    <td>{{ item.fullName || '-' }}</td>
                    <td>{{ item.email }}</td>
                    <td>{{ item.roleLabel }}</td>
                    <td>{{ item.isActive ? 'Active' : 'Disabled' }}</td>
                  </tr>
                  <tr v-if="!usersList.length">
                    <td colspan="4" class="text-medium-emphasis">No users found.</td>
                  </tr>
                </tbody>
              </v-table>
            </div>
          </div>

          <div class="management-block">
            <h3 class="management-title">Role Access Control</h3>
            <v-alert
              v-if="!canManagePermissions"
              type="info"
              variant="tonal"
              class="mb-3"
            >
              Only users with role-access permission can edit this matrix.
            </v-alert>
            <v-expansion-panels v-else multiple variant="accordion">
              <v-expansion-panel
                v-for="roleItem in editableRoleItems"
                :key="roleItem.roleKey"
              >
                <v-expansion-panel-title>
                  {{ roleItem.roleLabel }}
                </v-expansion-panel-title>
                <v-expansion-panel-text>
                  <div class="role-grid">
                    <div v-for="perm in permissionCatalog" :key="`${roleItem.roleKey}-${perm.key}`" class="role-grid-item">
                      <v-switch
                        v-model="roleAccessDraft[roleItem.roleKey][perm.key]"
                        color="#ab207d"
                        hide-details
                        density="compact"
                        :label="perm.label"
                      ></v-switch>
                      <p class="perm-help">{{ perm.description }}</p>
                    </div>
                  </div>
                  <div class="d-flex justify-end mt-3">
                    <v-btn
                      size="small"
                      color="primary"
                      variant="outlined"
                      :loading="savingRoleKey === roleItem.roleKey"
                      @click="saveRoleAccess(roleItem.roleKey)"
                    >
                      Save {{ roleItem.roleLabel }} Access
                    </v-btn>
                  </div>
                </v-expansion-panel-text>
              </v-expansion-panel>
            </v-expansion-panels>
          </div>
        </v-card-text>
      </v-card>
    </section>

    <v-snackbar
      v-model="snackbar.show"
      :timeout="3600"
      :color="snackbar.color"
      location="top right"
      elevation="2"
    >
      {{ snackbar.text }}
      <template #actions>
        <v-btn variant="text" color="white" @click="snackbar.show = false">Close</v-btn>
      </template>
    </v-snackbar>
  </v-container>
</template>

<script>
import {
  createUserAccount,
  describeAuthApiError,
  fetchSessionUser,
  getRoleAccessMatrix,
  getStoredCurrentUser,
  listUserAccounts,
  persistCurrentUser,
  saveRoleAccessMatrix,
} from '../services/authApi';
import {
  ROLE_LABELS,
  ROLE_KEYS,
  hasPermission,
  normalizeCurrentUser,
  roleCanEditTargetRole,
} from '../utils/accessControl';

export default {
  data() {
    return {
      names: '',
      email: '',
      username: '',
      password: '',
      roleKey: 'care_coordinator',
      showPassword: false,
      acceptedTerms: false,
      valid: false,
      isSubmitting: false,
      currentUser: null,
      usersList: [],
      permissionCatalog: [],
      roleAccessDraft: {},
      savingRoleKey: '',
      snackbar: {
        show: false,
        text: '',
        color: 'error',
      },
    };
  },
  computed: {
    roleOptions() {
      return ROLE_KEYS.map((roleKey) => ({
        value: roleKey,
        label: ROLE_LABELS[roleKey] || roleKey,
      }));
    },
    currentRoleKey() {
      return String(this.currentUser?.role || '');
    },
    canManageAccounts() {
      return hasPermission(this.currentUser, 'users.manage_accounts');
    },
    canManagePermissions() {
      return hasPermission(this.currentUser, 'users.manage_permissions');
    },
    allowedRoleOptions() {
      if (this.currentRoleKey === 'director') {
        return this.roleOptions;
      }
      if (this.currentRoleKey === 'manager') {
        return this.roleOptions.filter((item) => item.value === 'care_coordinator' || item.value === 'carer');
      }
      return this.roleOptions.filter((item) => item.value === 'carer');
    },
    editableRoleItems() {
      return this.roleOptions.filter((item) => roleCanEditTargetRole(this.currentRoleKey, item.value));
    },
  },
  async created() {
    await this.initializePage();
  },
  methods: {
    requiredRule(label) {
      return (value) => !!value || `${label} is required`;
    },
    emailRule(value) {
      if (!value) {
        return true;
      }

      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value).trim())
        || 'Please provide a valid email address';
    },
    usernameRule(value) {
      if (!value) {
        return true;
      }

      return /^[A-Za-z0-9._-]{4,20}$/.test(value)
        || 'Use 4-20 characters (letters, numbers, dot, underscore, hyphen)';
    },
    passwordRule(value) {
      if (!value) {
        return true;
      }

      return value.length >= 8 || 'Password must be at least 8 characters';
    },
    showMessage(text, color = 'error') {
      this.snackbar = {
        show: true,
        text,
        color,
      };
    },
    async initializePage() {
      const stored = normalizeCurrentUser(getStoredCurrentUser());
      this.currentUser = stored;

      if (!this.currentUser) {
        try {
          const session = await fetchSessionUser();
          this.currentUser = normalizeCurrentUser(session.user);
          if (this.currentUser) {
            persistCurrentUser(this.currentUser);
          }
        } catch (error) {
          this.showMessage(describeAuthApiError(error, 'Please sign in to manage users.'), 'warning');
          return;
        }
      }

      if (this.canManageAccounts) {
        await this.loadUsers();
      }

      if (this.canManagePermissions) {
        await this.loadRoleAccess();
      }
    },
    async loadUsers() {
      try {
        this.usersList = await listUserAccounts();
      } catch (error) {
        this.showMessage(describeAuthApiError(error, 'Failed to load users.'));
      }
    },
    async loadRoleAccess() {
      try {
        const result = await getRoleAccessMatrix();
        this.permissionCatalog = result.permissionCatalog;
        const draft = {};
        (result.roles || []).forEach((roleItem) => {
          draft[roleItem.roleKey] = { ...(roleItem.permissions || {}) };
        });
        this.roleAccessDraft = draft;
      } catch (error) {
        this.showMessage(describeAuthApiError(error, 'Failed to load role access matrix.'));
      }
    },
    async saveRoleAccess(roleKey) {
      if (!this.canManagePermissions) {
        return;
      }
      if (!this.roleAccessDraft[roleKey]) {
        this.showMessage('No role access data available to save.');
        return;
      }

      this.savingRoleKey = roleKey;
      try {
        await saveRoleAccessMatrix(roleKey, this.roleAccessDraft[roleKey]);
        this.showMessage(`${ROLE_LABELS[roleKey] || roleKey} access updated.`, 'success');
      } catch (error) {
        this.showMessage(describeAuthApiError(error, 'Failed to save role access.'));
      } finally {
        this.savingRoleKey = '';
      }
    },
    resetForm() {
      this.names = '';
      this.email = '';
      this.username = '';
      this.password = '';
      this.roleKey = this.allowedRoleOptions[0]?.value || 'care_coordinator';
      this.acceptedTerms = false;
      this.showPassword = false;
      this.$refs.form?.resetValidation?.();
    },
    async submitForm() {
      if (!this.canManageAccounts) {
        this.showMessage('You do not have permission to create user accounts.');
        return;
      }

      const formResult = await this.$refs.form.validate();
      if (!formResult.valid) {
        this.showMessage('Please fix the highlighted fields and try again.');
        return;
      }

      if (!this.acceptedTerms) {
        this.showMessage('Please confirm authorization before creating this account.');
        return;
      }

      this.isSubmitting = true;
      try {
        await createUserAccount({
          fullName: this.names,
          email: this.email,
          username: this.username,
          password: this.password,
          roleKey: this.roleKey,
        });
        this.showMessage('User account created successfully.', 'success');
        this.resetForm();
        await this.loadUsers();
      } catch (error) {
        this.showMessage(describeAuthApiError(error, 'Unable to complete registration. Please try again.'));
      } finally {
        this.isSubmitting = false;
      }
    },
  },
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@500;600;700&display=swap');

.signup-page {
  --bg-page: #f1e9f3;
  --text-main: #2c1f3d;
  --text-muted: #786b8a;
  --brand: #ab207d;
  --brand-deep: #4d273f;
  --field-line: #e5dbeb;

  min-height: calc(100vh - 64px);
  padding: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  background:
    radial-gradient(860px 460px at 85% 90%, rgba(205, 177, 229, 0.24), transparent 65%),
    radial-gradient(780px 420px at 10% 0%, rgba(240, 205, 233, 0.24), transparent 65%),
    var(--bg-page);
  font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
}

.signup-frame {
  position: relative;
  width: min(1240px, 100%);
  min-height: clamp(620px, 76vh, 720px);
  border-radius: 18px;
  overflow: hidden;
  display: grid;
  grid-template-columns: minmax(0, 1.02fr) minmax(0, 0.98fr);
  align-items: center;
  gap: 32px;
  padding: clamp(30px, 4vw, 52px) clamp(28px, 3.7vw, 44px);
  box-shadow: 0 26px 48px rgba(94, 37, 93, 0.16);
  background:
    radial-gradient(760px 340px at -8% 98%, rgba(255, 214, 241, 0.52) 0%, transparent 58%),
    radial-gradient(540px 250px at 22% 8%, rgba(255, 196, 236, 0.46) 0%, transparent 58%),
    radial-gradient(560px 300px at 90% 92%, rgba(214, 176, 246, 0.46) 0%, transparent 68%),
    linear-gradient(120deg, #7e2a7f 0%, #af4fb7 52%, #ceb0e9 100%);
}

.signup-frame::before {
  content: '';
  position: absolute;
  inset: -30% -10% auto auto;
  width: 52%;
  aspect-ratio: 1;
  border-radius: 999px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0) 68%);
  filter: blur(4px);
  pointer-events: none;
}

.signup-frame::after {
  content: '';
  position: absolute;
  inset: 0;
  background:
    radial-gradient(circle at 14% 20%, rgba(255, 255, 255, 0.3) 0 1px, transparent 2px),
    radial-gradient(circle at 33% 17%, rgba(255, 255, 255, 0.24) 0 1px, transparent 2px),
    radial-gradient(circle at 46% 33%, rgba(255, 255, 255, 0.22) 0 1px, transparent 2px),
    radial-gradient(circle at 20% 64%, rgba(255, 255, 255, 0.2) 0 1px, transparent 2px),
    radial-gradient(circle at 63% 23%, rgba(255, 255, 255, 0.16) 0 1px, transparent 2px),
    radial-gradient(circle at 76% 72%, rgba(255, 255, 255, 0.16) 0 1px, transparent 2px);
  pointer-events: none;
}

.frame-left {
  position: relative;
  z-index: 1;
  color: #fff9ff;
  max-width: 430px;
  margin-left: clamp(4px, 1.2vw, 18px);
}

.brand-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 24px;
}

.brand-icon {
  width: 40px;
  height: 40px;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.45);
  display: grid;
  place-items: center;
  background: rgba(255, 255, 255, 0.14);
}

.brand-copy p {
  margin: 0;
}

.brand-name {
  font-size: 0.88rem;
  font-weight: 600;
  line-height: 1.2;
}

.brand-sub {
  margin-top: 2px;
  font-size: 0.73rem;
  letter-spacing: 0.11em;
  color: #f7d8ee;
}

.frame-left h1 {
  margin: 0;
  font-family: 'Sora', 'Plus Jakarta Sans', sans-serif;
  font-size: clamp(2.18rem, 3.7vw, 3.05rem);
  line-height: 1.08;
  letter-spacing: 0.01em;
  font-weight: 700;
}

.lead {
  margin: 20px 0 0;
  max-width: 28ch;
  font-size: 1.02rem;
  line-height: 1.5;
  color: #fdeafd;
}

.feature-list {
  margin: 26px 0 0;
  padding: 0;
  list-style: none;
  display: grid;
  gap: 12px;
}

.feature-list li {
  display: flex;
  align-items: center;
  gap: 9px;
  font-size: 0.98rem;
  line-height: 1.35;
  color: #fff2fe;
  font-weight: 500;
}

.signup-card {
  position: relative;
  z-index: 1;
  justify-self: end;
  width: min(560px, 100%);
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.94);
  border: 1px solid rgba(255, 255, 255, 0.88);
  box-shadow:
    0 18px 32px rgba(79, 41, 80, 0.15),
    inset 0 1px 0 rgba(255, 255, 255, 0.78);
  backdrop-filter: blur(8px);
}

.card-body {
  padding: clamp(30px, 3.2vw, 42px);
}

.kicker {
  margin: 0;
  font-size: 1.82rem;
  line-height: 1.08;
  color: #51357a;
  font-family: 'Sora', 'Plus Jakarta Sans', sans-serif;
  font-weight: 600;
  letter-spacing: 0.01em;
}

.signup-card h2 {
  margin: 8px 0 24px;
  font-size: clamp(1.92rem, 2.65vw, 2.45rem);
  line-height: 1.08;
  color: #3f2b62;
  font-family: 'Sora', 'Plus Jakarta Sans', sans-serif;
  font-weight: 700;
}

.field {
  margin-bottom: 14px;
}

.terms-check {
  margin: 6px 0 16px;
}

.create-btn {
  border-radius: 10px;
  text-transform: none;
  font-size: 1.02rem;
  font-weight: 700;
  min-height: 52px;
  color: #fff;
  letter-spacing: 0.01em;
  background: linear-gradient(90deg, #d642a6 0%, #7445cb 100%);
  box-shadow: 0 11px 24px rgba(132, 54, 151, 0.3);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.create-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 14px 26px rgba(132, 54, 151, 0.34);
}

.login-link-copy {
  margin: 16px 0 0;
  font-size: 0.91rem;
  color: #8a789e;
}

.login-link-copy a {
  color: #7a3fb3;
  text-decoration: none;
  font-weight: 600;
}

.login-link-copy a:hover {
  text-decoration: underline;
}

:deep(.v-field--variant-outlined .v-field__outline) {
  color: var(--field-line);
}

:deep(.v-field--focused .v-field__outline) {
  color: #ab207d;
}

:deep(.v-field__input) {
  font-size: 0.93rem;
  color: var(--text-main);
}

:deep(.v-field__prepend-inner .v-icon),
:deep(.v-field__append-inner .v-icon) {
  color: #a390b5;
}

:deep(.v-label) {
  color: #8b7b9f;
  font-size: 0.9rem;
  font-weight: 500;
}

:deep(.terms-check .v-label) {
  font-size: 0.86rem;
  color: #8d7c9f;
  line-height: 1.45;
}

:deep(.v-messages__message) {
  font-size: 0.76rem;
}

@media (max-width: 1200px) {
  .signup-frame {
    min-height: 600px;
    gap: 26px;
    padding: 30px 24px;
  }

  .frame-left h1 {
    font-size: clamp(2rem, 4vw, 2.65rem);
  }

  .kicker {
    font-size: 1.52rem;
  }

  .signup-card h2 {
    font-size: clamp(1.62rem, 3vw, 2.1rem);
  }
}

@media (max-width: 980px) {
  .signup-page {
    padding: 14px;
  }

  .signup-frame {
    grid-template-columns: 1fr;
    gap: 16px;
    min-height: unset;
    padding: 24px 16px 18px;
    border-radius: 14px;
  }

  .frame-left {
    margin-left: 0;
    max-width: 100%;
  }

  .lead {
    max-width: 36ch;
  }

  .feature-list {
    margin-top: 18px;
  }

  .feature-list li {
    font-size: 0.94rem;
  }

  .signup-card {
    justify-self: stretch;
    width: 100%;
    border-radius: 16px;
  }

  .card-body {
    padding: 22px 18px 18px;
  }
}
</style>
