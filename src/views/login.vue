<template>
  <v-app class="login-app">
    <v-container fluid class="login-shell pa-0">
      <div class="login-layout">
      <section class="brand-panel">
        <div class="brand-chip">Staff Access</div>
        <h1>Secure Workforce Portal</h1>
        <p>
          Sign in to access role-based dashboard tools. Directors and managers can manage permissions, while carers
          and care coordinators only see approved sections.
        </p>
        <div class="brand-meta">
          <div>
            <span>Role-based routing</span>
            <strong>Enabled</strong>
          </div>
          <div>
            <span>Password recovery</span>
            <strong>Enabled</strong>
          </div>
        </div>
      </section>

      <section class="form-panel">
        <v-card class="login-card" elevation="0">
          <div class="form-header">
            <h2>{{ modeTitle }}</h2>
            <p>{{ modeSubtitle }}</p>
          </div>

          <v-form v-if="mode === 'login'" @submit.prevent="submitLogin">
            <v-text-field
              v-model="identifier"
              label="Email or Username*"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi-account-outline"
            ></v-text-field>
            <v-text-field
              v-model="password"
              label="Password*"
              :type="showPassword ? 'text' : 'password'"
              :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
              @click:append-inner="showPassword = !showPassword"
              @keydown.enter.prevent="submitLogin"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi-lock-outline"
            ></v-text-field>

            <div class="form-row">
              <v-btn variant="text" color="primary" size="small" @click="setMode('forgot')">
                Forgot password?
              </v-btn>
            </div>

            <v-btn color="primary" block size="large" type="submit" :loading="isSubmitting">
              Sign In
            </v-btn>
          </v-form>

          <div v-else-if="mode === 'forgot'">
            <v-text-field
              v-model="forgotIdentifier"
              label="Email or Username*"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi-email-outline"
            ></v-text-field>

            <v-btn color="primary" block size="large" :loading="isRequestingReset" @click="requestReset">
              Send Reset Instructions
            </v-btn>

            <div class="form-row">
              <v-btn variant="text" size="small" @click="setMode('login')">Back to sign in</v-btn>
              <v-btn variant="text" color="primary" size="small" @click="setMode('reset')">Have reset token</v-btn>
            </div>
          </div>

          <div v-else>
            <v-text-field
              v-model="resetToken"
              label="Reset Token*"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi-key-outline"
            ></v-text-field>
            <v-text-field
              v-model="resetPassword"
              label="New Password*"
              :type="showResetPassword ? 'text' : 'password'"
              :append-inner-icon="showResetPassword ? 'mdi-eye-off' : 'mdi-eye'"
              @click:append-inner="showResetPassword = !showResetPassword"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi-lock-reset"
            ></v-text-field>
            <v-text-field
              v-model="resetConfirmPassword"
              label="Confirm New Password*"
              :type="showResetConfirmPassword ? 'text' : 'password'"
              :append-inner-icon="showResetConfirmPassword ? 'mdi-eye-off' : 'mdi-eye'"
              @click:append-inner="showResetConfirmPassword = !showResetConfirmPassword"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi-lock-check-outline"
            ></v-text-field>

            <v-btn color="primary" block size="large" :loading="isResettingPassword" @click="submitReset">
              Reset Password
            </v-btn>

            <div class="form-row">
              <v-btn variant="text" size="small" @click="setMode('login')">Back to sign in</v-btn>
            </div>
          </div>
        </v-card>
      </section>
      </div>

      <v-snackbar v-model="snackbar.show" :timeout="4200" location="top right" :color="snackbar.color">
        {{ snackbar.text }}
        <template #actions>
          <v-btn color="white" variant="text" @click="snackbar.show = false">Close</v-btn>
        </template>
      </v-snackbar>
    </v-container>
  </v-app>
</template>

<script>
import {
  describeAuthApiError,
  loginUser,
  requestPasswordReset,
  resetPasswordWithToken,
} from '../services/authApi';
import { firstAllowedRouteName } from '../utils/accessControl';

export default {
  name: 'LoginPage',
  data() {
    return {
      mode: 'login',
      identifier: '',
      password: '',
      showPassword: false,

      forgotIdentifier: '',
      resetToken: '',
      resetPassword: '',
      resetConfirmPassword: '',
      showResetPassword: false,
      showResetConfirmPassword: false,

      isSubmitting: false,
      isRequestingReset: false,
      isResettingPassword: false,

      snackbar: {
        show: false,
        text: '',
        color: 'error',
      },
    };
  },
  computed: {
    modeTitle() {
      if (this.mode === 'forgot') return 'Forgot Password';
      if (this.mode === 'reset') return 'Reset Password';
      return 'Staff Sign In';
    },
    modeSubtitle() {
      if (this.mode === 'forgot') {
        return 'Enter your account identifier to receive password reset instructions.';
      }
      if (this.mode === 'reset') {
        return 'Use the reset token and set a new password for your account.';
      }
      return 'Use your assigned username/email and password to continue.';
    },
  },
  created() {
    this.applyRouteQuery();
  },
  methods: {
    applyRouteQuery() {
      const mode = String(this.$route?.query?.mode || '').trim().toLowerCase();
      const token = String(this.$route?.query?.token || '').trim();

      if (token) {
        this.resetToken = token;
        this.mode = 'reset';
        return;
      }

      if (mode === 'forgot' || mode === 'reset') {
        this.mode = mode;
      }
    },
    showMessage(text, color = 'error') {
      this.snackbar = {
        show: true,
        text: String(text || '').trim() || 'Request completed.',
        color: ['success', 'info', 'warning'].includes(color) ? color : 'error',
      };
    },
    setMode(mode) {
      const nextMode = String(mode || '').trim().toLowerCase();
      if (!['login', 'forgot', 'reset'].includes(nextMode)) {
        return;
      }
      this.mode = nextMode;
    },
    validatePasswordStrength(password) {
      const value = String(password || '');
      if (value.length < 8) {
        return 'Password must be at least 8 characters.';
      }
      if (!/[A-Za-z]/.test(value) || !/\d/.test(value)) {
        return 'Password must include at least one letter and one number.';
      }
      return '';
    },
    async submitLogin() {
      if (!String(this.identifier || '').trim() || !String(this.password || '')) {
        this.showMessage('Email/username and password are required.');
        return;
      }

      this.isSubmitting = true;
      try {
        const result = await loginUser({
          identifier: this.identifier.trim(),
          password: this.password,
        });

        const landingRoute = firstAllowedRouteName(result.user);
        this.showMessage(result.message || 'Login successful.', 'success');
        if (landingRoute) {
          this.$router.push({ name: landingRoute });
        } else {
          this.$router.push('/dashboard');
        }
      } catch (error) {
        this.showMessage(describeAuthApiError(error, 'Login failed.'));
      } finally {
        this.isSubmitting = false;
      }
    },
    async requestReset() {
      const identifier = String(this.forgotIdentifier || '').trim();
      if (!identifier) {
        this.showMessage('Please provide your email or username.');
        return;
      }

      this.isRequestingReset = true;
      try {
        const result = await requestPasswordReset(identifier);
        if (result.debugResetToken) {
          this.resetToken = String(result.debugResetToken);
          this.mode = 'reset';
          this.showMessage('Reset token generated for local testing. Use it to set a new password.', 'info');
        } else {
          this.showMessage(result.message || 'Reset instructions sent.', 'success');
          this.mode = 'reset';
        }
      } catch (error) {
        this.showMessage(describeAuthApiError(error, 'Failed to request password reset.'));
      } finally {
        this.isRequestingReset = false;
      }
    },
    async submitReset() {
      const token = String(this.resetToken || '').trim();
      if (!token) {
        this.showMessage('Reset token is required.');
        return;
      }

      const ruleMessage = this.validatePasswordStrength(this.resetPassword);
      if (ruleMessage) {
        this.showMessage(ruleMessage);
        return;
      }

      if (this.resetPassword !== this.resetConfirmPassword) {
        this.showMessage('Password confirmation does not match.');
        return;
      }

      this.isResettingPassword = true;
      try {
        const result = await resetPasswordWithToken({
          token,
          newPassword: this.resetPassword,
        });

        this.showMessage(result.message || 'Password reset successful.', 'success');
        this.resetPassword = '';
        this.resetConfirmPassword = '';
        this.mode = 'login';
      } catch (error) {
        this.showMessage(describeAuthApiError(error, 'Failed to reset password.'));
      } finally {
        this.isResettingPassword = false;
      }
    },
  },
};
</script>

<style scoped>
.login-app {
  min-height: 100vh;
}

.login-shell {
  min-height: 100vh;
  background:
    radial-gradient(1200px 450px at 8% 12%, rgba(171, 32, 125, 0.18), transparent 55%),
    radial-gradient(900px 380px at 92% 90%, rgba(21, 120, 196, 0.14), transparent 58%),
    linear-gradient(120deg, #f5f6fa 0%, #eceff5 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 22px;
}

.login-layout {
  width: min(1120px, 100%);
  min-height: 640px;
  border: 1px solid #e6e8ef;
  border-radius: 24px;
  overflow: hidden;
  background: #fff;
  display: grid;
  grid-template-columns: 1.05fr 1fr;
  box-shadow: 0 18px 38px rgba(16, 24, 40, 0.12);
}

.brand-panel {
  padding: 52px 42px;
  background:
    radial-gradient(420px 220px at 74% 12%, rgba(255, 255, 255, 0.16), transparent 70%),
    linear-gradient(145deg, #ab207d 0%, #6f1d73 52%, #253465 100%);
  color: #fff;
}

.brand-chip {
  display: inline-flex;
  align-items: center;
  padding: 5px 12px;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.24);
  font-size: 0.75rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-bottom: 24px;
}

.brand-panel h1 {
  margin: 0 0 12px;
  font-size: clamp(1.9rem, 3vw, 2.6rem);
  line-height: 1.08;
}

.brand-panel p {
  margin: 0;
  max-width: 430px;
  font-size: 0.96rem;
  color: rgba(255, 255, 255, 0.86);
  line-height: 1.6;
}

.brand-meta {
  margin-top: 34px;
  display: grid;
  gap: 12px;
}

.brand-meta div {
  border: 1px solid rgba(255, 255, 255, 0.24);
  border-radius: 12px;
  padding: 11px 14px;
  display: flex;
  justify-content: space-between;
}

.brand-meta span {
  font-size: 0.82rem;
  color: rgba(255, 255, 255, 0.8);
}

.brand-meta strong {
  font-size: 0.84rem;
}

.form-panel {
  padding: 38px;
  display: flex;
  align-items: center;
}

.login-card {
  width: 100%;
  border: 1px solid #e8ebf2;
  border-radius: 18px;
  padding: 22px;
}

.form-header {
  margin-bottom: 14px;
}

.form-header h2 {
  margin: 0;
  font-size: 1.45rem;
  color: #1e2432;
}

.form-header p {
  margin: 6px 0 0;
  font-size: 0.9rem;
  color: #667085;
}

.form-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: -2px 0 14px;
}

@media (max-width: 980px) {
  .login-layout {
    grid-template-columns: 1fr;
  }

  .brand-panel {
    padding: 34px 24px;
  }

  .form-panel {
    padding: 18px;
  }
}
</style>
