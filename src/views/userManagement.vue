<template>
  <v-container fluid class="user-mgmt-page">
    <section class="user-mgmt-shell">
      <header class="shell-header">
        <div>
          <p class="header-kicker">Users</p>
          <h1>User Management</h1>
          <p class="header-copy">
            Manage account profiles, roles, and permission access. These controls drive login access and dashboard restrictions.
          </p>
        </div>
        <div class="header-actions">
          <v-btn variant="outlined" color="primary" prepend-icon="mdi-refresh" :loading="isRefreshing" @click="refreshAll">
            Refresh
          </v-btn>
          <v-btn
            color="primary"
            prepend-icon="mdi-account-plus-outline"
            :disabled="!canManageAccounts"
            @click="openCreateDialog"
          >
            Create User
          </v-btn>
        </div>
      </header>

      <div class="stats-grid">
        <v-card class="stat-card" elevation="0">
          <p>Total Users</p>
          <h3>{{ usersList.length }}</h3>
        </v-card>
        <v-card class="stat-card" elevation="0">
          <p>Active Users</p>
          <h3>{{ activeUsersCount }}</h3>
        </v-card>
        <v-card class="stat-card" elevation="0">
          <p>Inactive Users</p>
          <h3>{{ inactiveUsersCount }}</h3>
        </v-card>
        <v-card class="stat-card" elevation="0">
          <p>Your Role</p>
          <h3>{{ currentRoleLabel }}</h3>
        </v-card>
      </div>

      <v-alert
        v-if="!canViewPage"
        type="warning"
        variant="tonal"
        class="mb-4"
      >
        Your role currently has no access to user management.
      </v-alert>

      <template v-else>
        <v-tabs v-model="activeTab" color="primary" class="mb-3">
          <v-tab value="accounts">Accounts</v-tab>
          <v-tab value="permissions">Role Access</v-tab>
          <v-tab value="security">Security</v-tab>
        </v-tabs>

        <v-window v-model="activeTab">
          <v-window-item value="accounts">
            <v-card class="panel-card" elevation="0">
              <v-card-text>
                <v-alert
                  v-if="!canManageAccounts"
                  type="info"
                  variant="tonal"
                  class="mb-3"
                >
                  You do not have permission to manage user accounts.
                </v-alert>

                <template v-else>
                  <div class="filters-row">
                    <v-text-field
                      v-model="searchQuery"
                      density="comfortable"
                      variant="outlined"
                      hide-details
                      prepend-inner-icon="mdi-magnify"
                      placeholder="Search by name, email, or username"
                    ></v-text-field>
                    <v-select
                      v-model="roleFilter"
                      :items="roleFilterItems"
                      density="comfortable"
                      variant="outlined"
                      hide-details
                      label="Role"
                    ></v-select>
                    <v-select
                      v-model="statusFilter"
                      :items="statusFilterItems"
                      density="comfortable"
                      variant="outlined"
                      hide-details
                      label="Status"
                    ></v-select>
                  </div>

                  <div class="table-shell">
                    <v-table density="comfortable">
                      <thead>
                        <tr>
                          <th>Full Name</th>
                          <th>Email</th>
                          <th>Username</th>
                          <th>Role</th>
                          <th>Status</th>
                          <th>Updated</th>
                          <th class="text-right">Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="item in filteredUsers" :key="item.id">
                          <td>{{ item.fullName || '-' }}</td>
                          <td>{{ item.email || '-' }}</td>
                          <td>{{ item.username || '-' }}</td>
                          <td>
                            <v-chip size="small" variant="tonal" color="primary">
                              {{ item.roleLabel || formatRoleLabel(item.role) }}
                            </v-chip>
                          </td>
                          <td>
                            <v-chip size="small" variant="tonal" :color="item.isActive ? 'success' : 'error'">
                              {{ item.isActive ? 'Active' : 'Disabled' }}
                            </v-chip>
                          </td>
                          <td>{{ formatDate(item.updatedAt || item.createdAt) }}</td>
                          <td class="text-right">
                            <v-btn
                              icon="mdi-pencil-outline"
                              size="small"
                              variant="text"
                              color="primary"
                              :disabled="!canEditUser(item)"
                              :title="canEditUser(item) ? 'Edit user account' : 'You are not allowed to edit this account.'"
                              @click="openEditDialog(item)"
                            ></v-btn>
                          </td>
                        </tr>
                        <tr v-if="!filteredUsers.length">
                          <td colspan="7" class="empty-row">No matching users found.</td>
                        </tr>
                      </tbody>
                    </v-table>
                  </div>
                </template>
              </v-card-text>
            </v-card>
          </v-window-item>

          <v-window-item value="permissions">
            <v-card class="panel-card" elevation="0">
              <v-card-text>
                <v-alert
                  v-if="!canManagePermissions"
                  type="info"
                  variant="tonal"
                  class="mb-3"
                >
                  You do not have permission to update role access matrix.
                </v-alert>

                <v-alert
                  v-else-if="!editableRoleItems.length"
                  type="info"
                  variant="tonal"
                  class="mb-0"
                >
                  No editable roles are available for your current role.
                </v-alert>

                <v-expansion-panels v-else variant="accordion">
                  <v-expansion-panel
                    v-for="roleItem in editableRoleItems"
                    :key="roleItem.value"
                  >
                    <v-expansion-panel-title>
                      {{ roleItem.label }}
                    </v-expansion-panel-title>
                    <v-expansion-panel-text>
                      <div v-for="group in permissionGroups" :key="`${roleItem.value}-${group.group}`" class="permission-group">
                        <h4>{{ group.group }}</h4>
                        <div class="permission-grid">
                          <div
                            v-for="perm in group.items"
                            :key="`${roleItem.value}-${perm.key}`"
                            class="permission-item"
                          >
                            <v-switch
                              v-model="roleAccessDraft[roleItem.value][perm.key]"
                              color="primary"
                              density="compact"
                              hide-details
                              :label="perm.label"
                            ></v-switch>
                            <p>{{ perm.description }}</p>
                          </div>
                        </div>
                      </div>
                      <div class="role-actions">
                        <v-btn
                          color="primary"
                          variant="outlined"
                          :loading="savingRoleKey === roleItem.value"
                          @click="saveRoleAccess(roleItem.value)"
                        >
                          Save {{ roleItem.label }} Access
                        </v-btn>
                      </div>
                    </v-expansion-panel-text>
                  </v-expansion-panel>
                </v-expansion-panels>
              </v-card-text>
            </v-card>
          </v-window-item>

          <v-window-item value="security">
            <v-card class="panel-card" elevation="0">
              <v-card-text>
                <div class="security-grid">
                  <v-card class="security-card" elevation="0">
                    <v-card-title>Change My Password</v-card-title>
                    <v-card-text>
                      <p class="security-copy">
                        Update your login password. Use at least 8 characters with letters and numbers.
                      </p>
                      <v-text-field
                        v-model="myPasswordForm.currentPassword"
                        label="Current Password*"
                        :type="showMyCurrentPassword ? 'text' : 'password'"
                        :append-inner-icon="showMyCurrentPassword ? 'mdi-eye-off' : 'mdi-eye'"
                        @click:append-inner="showMyCurrentPassword = !showMyCurrentPassword"
                        variant="outlined"
                        density="comfortable"
                      ></v-text-field>
                      <v-text-field
                        v-model="myPasswordForm.newPassword"
                        label="New Password*"
                        :type="showMyNewPassword ? 'text' : 'password'"
                        :append-inner-icon="showMyNewPassword ? 'mdi-eye-off' : 'mdi-eye'"
                        @click:append-inner="showMyNewPassword = !showMyNewPassword"
                        variant="outlined"
                        density="comfortable"
                      ></v-text-field>
                      <v-text-field
                        v-model="myPasswordForm.confirmPassword"
                        label="Confirm New Password*"
                        :type="showMyConfirmPassword ? 'text' : 'password'"
                        :append-inner-icon="showMyConfirmPassword ? 'mdi-eye-off' : 'mdi-eye'"
                        @click:append-inner="showMyConfirmPassword = !showMyConfirmPassword"
                        variant="outlined"
                        density="comfortable"
                      ></v-text-field>
                    </v-card-text>
                    <v-card-actions>
                      <v-spacer></v-spacer>
                      <v-btn variant="text" @click="resetMyPasswordForm">Clear</v-btn>
                      <v-btn color="primary" :loading="isChangingMyPassword" @click="submitMyPasswordChange">
                        Update Password
                      </v-btn>
                    </v-card-actions>
                  </v-card>

                  <v-card v-if="canManageAccounts" class="security-card" elevation="0">
                    <v-card-title>Reset User Password</v-card-title>
                    <v-card-text>
                      <p class="security-copy">
                        For staff support: set a temporary password for a user account.
                      </p>
                      <v-select
                        v-model="adminPasswordForm.userId"
                        :items="userSelectItems"
                        label="Select User*"
                        item-title="title"
                        item-value="value"
                        variant="outlined"
                        density="comfortable"
                      ></v-select>
                      <v-text-field
                        v-model="adminPasswordForm.newPassword"
                        label="Temporary Password*"
                        :type="showAdminNewPassword ? 'text' : 'password'"
                        :append-inner-icon="showAdminNewPassword ? 'mdi-eye-off' : 'mdi-eye'"
                        @click:append-inner="showAdminNewPassword = !showAdminNewPassword"
                        variant="outlined"
                        density="comfortable"
                      ></v-text-field>
                      <v-text-field
                        v-model="adminPasswordForm.confirmPassword"
                        label="Confirm Temporary Password*"
                        :type="showAdminConfirmPassword ? 'text' : 'password'"
                        :append-inner-icon="showAdminConfirmPassword ? 'mdi-eye-off' : 'mdi-eye'"
                        @click:append-inner="showAdminConfirmPassword = !showAdminConfirmPassword"
                        variant="outlined"
                        density="comfortable"
                      ></v-text-field>
                    </v-card-text>
                    <v-card-actions>
                      <v-spacer></v-spacer>
                      <v-btn variant="text" @click="resetAdminPasswordForm">Clear</v-btn>
                      <v-btn color="primary" :loading="isSettingUserPassword" @click="submitAdminPasswordReset">
                        Save Temporary Password
                      </v-btn>
                    </v-card-actions>
                  </v-card>
                </div>
              </v-card-text>
            </v-card>
          </v-window-item>
        </v-window>
      </template>
    </section>

    <v-dialog v-model="createDialog" max-width="620">
      <v-card>
        <v-toolbar color="primary" title="Create User Account"></v-toolbar>
        <v-card-text class="pt-4">
          <v-text-field v-model="createForm.fullName" label="Full Name*" variant="outlined" density="comfortable"></v-text-field>
          <v-text-field v-model="createForm.email" label="Email*" variant="outlined" density="comfortable"></v-text-field>
          <v-text-field v-model="createForm.username" label="Username*" variant="outlined" density="comfortable"></v-text-field>
          <v-text-field
            v-model="createForm.password"
            label="Password*"
            :type="showCreatePassword ? 'text' : 'password'"
            :append-inner-icon="showCreatePassword ? 'mdi-eye-off' : 'mdi-eye'"
            @click:append-inner="showCreatePassword = !showCreatePassword"
            variant="outlined"
            density="comfortable"
          ></v-text-field>
          <v-select
            v-model="createForm.roleKey"
            :items="allowedRoleOptions"
            item-title="label"
            item-value="value"
            label="Role*"
            variant="outlined"
            density="comfortable"
          ></v-select>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="createDialog = false">Cancel</v-btn>
          <v-btn color="primary" :loading="isCreating" @click="submitCreate">Create User</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="editDialog" max-width="620">
      <v-card>
        <v-toolbar color="primary" title="Edit User Account"></v-toolbar>
        <v-card-text class="pt-4">
          <v-text-field v-model="editForm.fullName" label="Full Name*" variant="outlined" density="comfortable"></v-text-field>
          <v-text-field v-model="editForm.email" label="Email*" variant="outlined" density="comfortable"></v-text-field>
          <v-text-field v-model="editForm.username" label="Username*" variant="outlined" density="comfortable"></v-text-field>
          <v-select
            v-model="editForm.roleKey"
            :items="editRoleOptions"
            item-title="label"
            item-value="value"
            label="Role*"
            variant="outlined"
            density="comfortable"
            :disabled="!canEditRoleSelection"
          ></v-select>
          <v-switch
            v-model="editForm.isActive"
            color="primary"
            hide-details
            :disabled="isEditingSelf"
            :label="isEditingSelf ? 'Account Status (you cannot disable your own account)' : 'Account Status (active)'"            
          ></v-switch>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="editDialog = false">Cancel</v-btn>
          <v-btn color="primary" :loading="isUpdating" @click="submitEdit">Save Changes</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :timeout="3600" :color="snackbar.type" location="top right">
      {{ snackbar.text }}
      <template #actions>
        <v-btn color="white" variant="text" @click="snackbar.show = false">Close</v-btn>
      </template>
    </v-snackbar>
  </v-container>
</template>

<script>
import {
  changeMyPassword,
  createUserAccount,
  describeAuthApiError,
  fetchSessionUser,
  getRoleAccessMatrix,
  getStoredCurrentUser,
  listUserAccounts,
  persistCurrentUser,
  saveRoleAccessMatrix,
  setUserPassword,
  updateUserAccount,
} from '../services/authApi';
import {
  ROLE_KEYS,
  ROLE_LABELS,
  hasPermission,
  normalizeCurrentUser,
  roleCanEditTargetRole,
} from '../utils/accessControl';

export default {
  data() {
    return {
      activeTab: 'accounts',
      isRefreshing: false,
      isCreating: false,
      isUpdating: false,
      isChangingMyPassword: false,
      isSettingUserPassword: false,
      showCreatePassword: false,
      showMyCurrentPassword: false,
      showMyNewPassword: false,
      showMyConfirmPassword: false,
      showAdminNewPassword: false,
      showAdminConfirmPassword: false,
      createDialog: false,
      editDialog: false,
      savingRoleKey: '',

      currentUser: null,
      usersList: [],
      permissionCatalog: [],
      roleAccessDraft: {},

      searchQuery: '',
      roleFilter: 'all',
      statusFilter: 'all',

      createForm: {
        fullName: '',
        email: '',
        username: '',
        password: '',
        roleKey: 'care_coordinator',
      },
      myPasswordForm: {
        currentPassword: '',
        newPassword: '',
        confirmPassword: '',
      },
      adminPasswordForm: {
        userId: 0,
        newPassword: '',
        confirmPassword: '',
      },
      editForm: {
        userId: 0,
        fullName: '',
        email: '',
        username: '',
        roleKey: 'care_coordinator',
        isActive: true,
      },
      editOriginalRoleKey: '',

      snackbar: {
        show: false,
        text: '',
        type: 'error',
      },
    };
  },
  computed: {
    canManageAccounts() {
      return hasPermission(this.currentUser, 'users.manage_accounts');
    },
    canManagePermissions() {
      return hasPermission(this.currentUser, 'users.manage_permissions');
    },
    canViewPage() {
      return this.canManageAccounts || this.canManagePermissions;
    },
    roleOptions() {
      return ROLE_KEYS.map((roleKey) => ({
        value: roleKey,
        label: ROLE_LABELS[roleKey] || roleKey,
      }));
    },
    currentRoleKey() {
      return String(this.currentUser?.role || '');
    },
    currentRoleLabel() {
      return ROLE_LABELS[this.currentRoleKey] || 'Unknown';
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
    editRoleOptions() {
      const editable = this.roleOptions.filter((item) => roleCanEditTargetRole(this.currentRoleKey, item.value));
      if (!editable.find((item) => item.value === this.editOriginalRoleKey) && this.editOriginalRoleKey) {
        editable.unshift({
          value: this.editOriginalRoleKey,
          label: ROLE_LABELS[this.editOriginalRoleKey] || this.editOriginalRoleKey,
        });
      }
      return editable;
    },
    canEditRoleSelection() {
      if (!this.editDialog) {
        return false;
      }
      if (this.currentRoleKey === 'director') {
        return true;
      }
      return !this.isEditingSelf;
    },
    isEditingSelf() {
      return Number(this.editForm.userId || 0) === Number(this.currentUser?.id || 0);
    },
    editableRoleItems() {
      return this.roleOptions.filter((item) => roleCanEditTargetRole(this.currentRoleKey, item.value));
    },
    roleFilterItems() {
      return [{ title: 'All Roles', value: 'all' }, ...this.roleOptions.map((item) => ({ title: item.label, value: item.value }))];
    },
    statusFilterItems() {
      return [
        { title: 'All Statuses', value: 'all' },
        { title: 'Active', value: 'active' },
        { title: 'Disabled', value: 'disabled' },
      ];
    },
    userSelectItems() {
      return this.usersList.map((item) => ({
        value: Number(item.id || 0),
        title: `${item.fullName || item.username || item.email || 'User'} (${item.roleLabel || this.formatRoleLabel(item.role)})`,
      }));
    },
    filteredUsers() {
      const query = String(this.searchQuery || '').trim().toLowerCase();
      return this.usersList.filter((item) => {
        if (this.roleFilter !== 'all' && item.role !== this.roleFilter) {
          return false;
        }
        if (this.statusFilter === 'active' && !item.isActive) {
          return false;
        }
        if (this.statusFilter === 'disabled' && item.isActive) {
          return false;
        }

        if (!query) {
          return true;
        }

        const search = [
          item.fullName,
          item.email,
          item.username,
          item.roleLabel,
        ]
          .filter(Boolean)
          .join(' ')
          .toLowerCase();

        return search.includes(query);
      });
    },
    permissionGroups() {
      const map = {};
      this.permissionCatalog.forEach((perm) => {
        const group = String(perm.group || 'General').trim() || 'General';
        if (!map[group]) {
          map[group] = [];
        }
        map[group].push(perm);
      });

      return Object.keys(map).map((group) => ({
        group,
        items: map[group],
      }));
    },
    activeUsersCount() {
      return this.usersList.filter((item) => item.isActive).length;
    },
    inactiveUsersCount() {
      return this.usersList.filter((item) => !item.isActive).length;
    },
  },
  async created() {
    await this.initializePage();
  },
  methods: {
    setDefaultTab() {
      if (this.canManageAccounts) {
        this.activeTab = 'accounts';
        return;
      }
      if (this.canManagePermissions) {
        this.activeTab = 'permissions';
      }
    },
    showMessage(text, type = 'error') {
      this.snackbar = {
        show: true,
        text: String(text || '').trim() || 'Request completed.',
        type: type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'error',
      };
    },
    canEditUser(user) {
      if (!this.canManageAccounts) {
        return false;
      }

      const userId = Number(user?.id || 0);
      const currentUserId = Number(this.currentUser?.id || 0);
      if (userId > 0 && userId === currentUserId) {
        return true;
      }

      return roleCanEditTargetRole(this.currentRoleKey, String(user?.role || ''));
    },
    formatRoleLabel(roleKey) {
      return ROLE_LABELS[String(roleKey || '').trim()] || String(roleKey || '');
    },
    formatDate(value) {
      if (!value) {
        return '-';
      }
      const parsed = new Date(value);
      if (Number.isNaN(parsed.getTime())) {
        return '-';
      }
      return parsed.toLocaleString('en-GB', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
      });
    },
    validatePasswordStrength(value) {
      const password = String(value || '');
      if (password.length < 8) {
        return 'Password must be at least 8 characters.';
      }
      if (!/[A-Za-z]/.test(password) || !/\d/.test(password)) {
        return 'Password must include at least one letter and one number.';
      }
      return '';
    },
    resetMyPasswordForm() {
      this.myPasswordForm = {
        currentPassword: '',
        newPassword: '',
        confirmPassword: '',
      };
      this.showMyCurrentPassword = false;
      this.showMyNewPassword = false;
      this.showMyConfirmPassword = false;
    },
    resetAdminPasswordForm() {
      this.adminPasswordForm = {
        userId: 0,
        newPassword: '',
        confirmPassword: '',
      };
      this.showAdminNewPassword = false;
      this.showAdminConfirmPassword = false;
    },
    validateMyPasswordForm() {
      if (!this.myPasswordForm.currentPassword) {
        return 'Current password is required.';
      }
      const passwordRuleMessage = this.validatePasswordStrength(this.myPasswordForm.newPassword);
      if (passwordRuleMessage) {
        return passwordRuleMessage;
      }
      if (this.myPasswordForm.newPassword !== this.myPasswordForm.confirmPassword) {
        return 'New password and confirmation do not match.';
      }
      return '';
    },
    validateAdminPasswordForm() {
      if (!this.canManageAccounts) {
        return 'You do not have permission to reset user passwords.';
      }
      if (Number(this.adminPasswordForm.userId || 0) <= 0) {
        return 'Please select a user account.';
      }
      const targetUser = this.usersList.find((item) => Number(item.id || 0) === Number(this.adminPasswordForm.userId || 0));
      if (!targetUser) {
        return 'Selected user account was not found.';
      }
      if (!this.canEditUser(targetUser)) {
        return 'You are not allowed to reset this user password.';
      }
      const passwordRuleMessage = this.validatePasswordStrength(this.adminPasswordForm.newPassword);
      if (passwordRuleMessage) {
        return passwordRuleMessage;
      }
      if (this.adminPasswordForm.newPassword !== this.adminPasswordForm.confirmPassword) {
        return 'Temporary password and confirmation do not match.';
      }
      return '';
    },
    async submitMyPasswordChange() {
      const validationMessage = this.validateMyPasswordForm();
      if (validationMessage) {
        this.showMessage(validationMessage);
        return;
      }

      this.isChangingMyPassword = true;
      try {
        await changeMyPassword({
          currentPassword: this.myPasswordForm.currentPassword,
          newPassword: this.myPasswordForm.newPassword,
        });
        this.showMessage('Password changed successfully.', 'success');
        this.resetMyPasswordForm();
      } catch (error) {
        this.showMessage(describeAuthApiError(error, 'Failed to change password.'));
      } finally {
        this.isChangingMyPassword = false;
      }
    },
    async submitAdminPasswordReset() {
      const validationMessage = this.validateAdminPasswordForm();
      if (validationMessage) {
        this.showMessage(validationMessage);
        return;
      }

      this.isSettingUserPassword = true;
      try {
        await setUserPassword({
          userId: Number(this.adminPasswordForm.userId || 0),
          newPassword: this.adminPasswordForm.newPassword,
        });
        this.showMessage('User password updated successfully.', 'success');
        this.resetAdminPasswordForm();
      } catch (error) {
        this.showMessage(describeAuthApiError(error, 'Failed to update user password.'));
      } finally {
        this.isSettingUserPassword = false;
      }
    },
    async initializePage() {
      const stored = normalizeCurrentUser(getStoredCurrentUser());
      if (stored) {
        this.currentUser = stored;
      }

      if (!this.currentUser) {
        try {
          const session = await fetchSessionUser();
          this.currentUser = normalizeCurrentUser(session.user);
          if (this.currentUser) {
            persistCurrentUser(this.currentUser);
          }
        } catch (error) {
          this.showMessage(describeAuthApiError(error, 'Please sign in to continue.'), 'warning');
          return;
        }
      }

      this.setDefaultTab();
      await this.refreshAll();
      this.resetCreateForm();
      this.resetMyPasswordForm();
      this.resetAdminPasswordForm();
    },
    async refreshAll() {
      this.isRefreshing = true;
      try {
        await Promise.all([
          this.canManageAccounts ? this.loadUsers() : Promise.resolve(),
          this.canManagePermissions ? this.loadRoleAccess() : Promise.resolve(),
        ]);
      } finally {
        this.isRefreshing = false;
      }
    },
    async loadUsers() {
      try {
        this.usersList = await listUserAccounts();
      } catch (error) {
        this.showMessage(describeAuthApiError(error, 'Failed to load users.'));
      }
    },
    ensureRoleDraftShape() {
      this.editableRoleItems.forEach((roleItem) => {
        if (!this.roleAccessDraft[roleItem.value]) {
          this.roleAccessDraft[roleItem.value] = {};
        }
        this.permissionCatalog.forEach((perm) => {
          if (!Object.prototype.hasOwnProperty.call(this.roleAccessDraft[roleItem.value], perm.key)) {
            this.roleAccessDraft[roleItem.value][perm.key] = false;
          }
        });
      });
    },
    async loadRoleAccess() {
      try {
        const result = await getRoleAccessMatrix();
        this.permissionCatalog = Array.isArray(result.permissionCatalog) ? result.permissionCatalog : [];

        const nextDraft = {};
        (result.roles || []).forEach((roleItem) => {
          nextDraft[roleItem.roleKey] = { ...(roleItem.permissions || {}) };
        });
        this.roleAccessDraft = nextDraft;
        this.ensureRoleDraftShape();
      } catch (error) {
        this.showMessage(describeAuthApiError(error, 'Failed to load role access matrix.'));
      }
    },
    async saveRoleAccess(roleKey) {
      if (!this.canManagePermissions) {
        return;
      }

      if (!this.roleAccessDraft[roleKey]) {
        this.showMessage('No role access data available for this role.');
        return;
      }

      this.savingRoleKey = roleKey;
      try {
        await saveRoleAccessMatrix(roleKey, this.roleAccessDraft[roleKey]);
        this.showMessage(`${this.formatRoleLabel(roleKey)} access updated successfully.`, 'success');
      } catch (error) {
        this.showMessage(describeAuthApiError(error, 'Failed to save role access.'));
      } finally {
        this.savingRoleKey = '';
      }
    },
    openCreateDialog() {
      this.resetCreateForm();
      this.createDialog = true;
    },
    resetCreateForm() {
      this.createForm = {
        fullName: '',
        email: '',
        username: '',
        password: '',
        roleKey: this.allowedRoleOptions[0]?.value || 'care_coordinator',
      };
      this.showCreatePassword = false;
    },
    validateCreateForm() {
      if (!this.createForm.fullName.trim()) {
        return 'Full name is required.';
      }
      if (!this.createForm.email.trim()) {
        return 'Email is required.';
      }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.createForm.email.trim())) {
        return 'Please provide a valid email address.';
      }
      if (!/^[A-Za-z0-9._-]{3,40}$/.test(this.createForm.username.trim())) {
        return 'Username must be 3-40 characters and can only use letters, numbers, dot, underscore, or hyphen.';
      }
      if (String(this.createForm.password || '').length < 8) {
        return 'Password must be at least 8 characters.';
      }
      if (!this.allowedRoleOptions.find((item) => item.value === this.createForm.roleKey)) {
        return 'Selected role is not allowed for your account.';
      }
      return '';
    },
    async submitCreate() {
      if (!this.canManageAccounts) {
        this.showMessage('You do not have permission to create accounts.');
        return;
      }

      const validationMessage = this.validateCreateForm();
      if (validationMessage) {
        this.showMessage(validationMessage);
        return;
      }

      this.isCreating = true;
      try {
        await createUserAccount({
          fullName: this.createForm.fullName.trim(),
          email: this.createForm.email.trim(),
          username: this.createForm.username.trim(),
          password: this.createForm.password,
          roleKey: this.createForm.roleKey,
        });

        this.showMessage('User account created successfully.', 'success');
        this.createDialog = false;
        await this.loadUsers();
      } catch (error) {
        this.showMessage(describeAuthApiError(error, 'Failed to create user account.'));
      } finally {
        this.isCreating = false;
      }
    },
    openEditDialog(user) {
      if (!this.canEditUser(user)) {
        this.showMessage('You are not allowed to edit this account.');
        return;
      }

      this.editForm = {
        userId: Number(user?.id || 0),
        fullName: String(user?.fullName || ''),
        email: String(user?.email || ''),
        username: String(user?.username || ''),
        roleKey: String(user?.role || 'care_coordinator'),
        isActive: Boolean(user?.isActive),
      };
      this.editOriginalRoleKey = String(user?.role || 'care_coordinator');
      this.editDialog = true;
    },
    validateEditForm() {
      if (Number(this.editForm.userId || 0) <= 0) {
        return 'Invalid user account.';
      }
      if (!this.editForm.fullName.trim()) {
        return 'Full name is required.';
      }
      if (!this.editForm.email.trim()) {
        return 'Email is required.';
      }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.editForm.email.trim())) {
        return 'Please provide a valid email address.';
      }
      if (!/^[A-Za-z0-9._-]{3,40}$/.test(this.editForm.username.trim())) {
        return 'Username must be 3-40 characters and can only use letters, numbers, dot, underscore, or hyphen.';
      }
      if (!this.editRoleOptions.find((item) => item.value === this.editForm.roleKey)) {
        return 'Selected role is not allowed for this account.';
      }
      return '';
    },
    async submitEdit() {
      if (!this.canManageAccounts) {
        this.showMessage('You do not have permission to update accounts.');
        return;
      }

      const validationMessage = this.validateEditForm();
      if (validationMessage) {
        this.showMessage(validationMessage);
        return;
      }

      this.isUpdating = true;
      try {
        await updateUserAccount({
          userId: Number(this.editForm.userId || 0),
          fullName: this.editForm.fullName.trim(),
          email: this.editForm.email.trim(),
          username: this.editForm.username.trim(),
          roleKey: this.editForm.roleKey,
          isActive: Boolean(this.editForm.isActive),
        });

        this.showMessage('User account updated successfully.', 'success');
        this.editDialog = false;
        await this.loadUsers();
      } catch (error) {
        this.showMessage(describeAuthApiError(error, 'Failed to update user account.'));
      } finally {
        this.isUpdating = false;
      }
    },
  },
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap');

.user-mgmt-page {
  --bg-shell: #f4f4f6;
  --bg-card: #ffffff;
  --text-main: #1f1f2a;
  --text-muted: #656474;
  --line: #e6e5eb;
  --brand: #ab207d;

  min-height: calc(100vh - 64px);
  padding: 20px;
  background:
    radial-gradient(900px 420px at 85% 110%, rgba(171, 32, 125, 0.16), transparent 68%),
    radial-gradient(680px 360px at -10% -20%, rgba(64, 124, 255, 0.1), transparent 64%),
    var(--bg-shell);
  font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
}

.user-mgmt-shell {
  max-width: 1320px;
  margin: 0 auto;
}

.shell-header {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: flex-start;
  margin-bottom: 14px;
}

.header-kicker {
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  font-size: 0.74rem;
  font-weight: 700;
  color: var(--text-muted);
}

.shell-header h1 {
  margin: 2px 0 4px;
  font-family: 'Space Grotesk', 'Plus Jakarta Sans', sans-serif;
  color: var(--text-main);
  font-size: clamp(1.5rem, 2.3vw, 2rem);
  line-height: 1.08;
}

.header-copy {
  margin: 0;
  color: var(--text-muted);
  max-width: 780px;
}

.header-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.stats-grid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  margin-bottom: 14px;
}

.stat-card {
  border: 1px solid var(--line);
  border-radius: 14px;
  padding: 14px 16px;
  background: var(--bg-card);
}

.stat-card p {
  margin: 0;
  font-size: 0.78rem;
  color: var(--text-muted);
  letter-spacing: 0.02em;
}

.stat-card h3 {
  margin: 8px 0 0;
  font-size: 1.5rem;
  color: var(--text-main);
  line-height: 1.15;
}

.panel-card {
  border: 1px solid var(--line);
  border-radius: 16px;
  background: var(--bg-card);
}

.filters-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 220px 220px;
  gap: 10px;
  margin-bottom: 12px;
}

.table-shell {
  border: 1px solid var(--line);
  border-radius: 12px;
  overflow: hidden;
}

.empty-row {
  text-align: center;
  color: var(--text-muted);
  padding: 16px 8px;
}

.permission-group {
  margin-bottom: 16px;
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 12px;
  background: #fcfbfd;
}

.permission-group h4 {
  margin: 0 0 10px;
  font-size: 0.92rem;
  font-weight: 700;
  color: var(--text-main);
}

.permission-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.permission-item {
  border: 1px solid var(--line);
  border-radius: 10px;
  padding: 8px 10px;
  background: #fff;
}

.permission-item p {
  margin: 4px 0 0;
  color: var(--text-muted);
  font-size: 0.78rem;
  line-height: 1.35;
}

.role-actions {
  margin-top: 10px;
  display: flex;
  justify-content: flex-end;
}

.security-grid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.security-card {
  border: 1px solid var(--line);
  border-radius: 12px;
  background: #fff;
}

.security-copy {
  margin: 0 0 10px;
  color: var(--text-muted);
  font-size: 0.84rem;
  line-height: 1.4;
}

@media (max-width: 1180px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .filters-row {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 760px) {
  .user-mgmt-page {
    padding: 12px;
  }

  .shell-header {
    flex-direction: column;
  }

  .header-actions {
    width: 100%;
  }

  .header-actions .v-btn {
    flex: 1;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .permission-grid {
    grid-template-columns: 1fr;
  }

  .security-grid {
    grid-template-columns: 1fr;
  }
}
</style>
