<template>
  <v-container fluid class="ga-page">
    <section class="ga-shell">
      <header class="ga-header">
        <div>
          <p class="ga-kicker">Analytics Menu</p>
          <h1>Google Analytics</h1>
          <p class="ga-copy">All GA4 insights in one menu: Overview, Acquisition, Pages, Audience, Conversions, Realtime, SEO, Alerts.</p>
        </div>
        <div class="ga-actions">
          <v-select v-model="selectedDays" :items="dayOptions" item-title="label" item-value="value" density="comfortable" variant="outlined" hide-details class="range-select"></v-select>
          <v-btn color="primary" variant="outlined" :loading="isLoading" prepend-icon="mdi-refresh" @click="refreshActiveSection">Refresh</v-btn>
        </div>
      </header>

      <v-alert v-if="errorMessage" type="error" variant="tonal" class="mb-3">{{ errorMessage }}</v-alert>
      <v-alert v-if="!isLoading && !isConfigured && !errorMessage" type="warning" variant="tonal" class="mb-3">
        Google Analytics is not configured yet.
        <div v-if="missingConfig.length" class="mt-1">Missing: <strong>{{ missingConfig.join(', ') }}</strong></div>
      </v-alert>

      <template v-if="isConfigured">
        <v-card class="panel-card mb-3" elevation="0">
          <v-card-text class="py-2">
            <v-tabs v-model="activeTab" density="comfortable" color="primary">
              <v-tab v-for="tab in tabs" :key="tab.value" :value="tab.value">{{ tab.label }}</v-tab>
            </v-tabs>
          </v-card-text>
        </v-card>

        <div class="kpi-grid" v-if="activeCards.length">
          <v-card class="kpi-card" elevation="0" v-for="card in activeCards" :key="card.label">
            <p>{{ card.label }}</p>
            <h3>{{ card.value }}</h3>
          </v-card>
        </div>

        <div v-if="activeTab === 'alerts' && activeAlerts.length" class="mb-3">
          <v-alert v-for="item in activeAlerts" :key="item.id" :type="alertType(item.severity)" variant="tonal" class="mb-2">
            <div class="d-flex justify-space-between ga-2 align-start">
              <div>
                <div class="font-weight-bold">{{ item.title }}</div>
                <div>{{ item.message }}</div>
              </div>
              <v-chip size="x-small" :color="alertChipColor(item.severity)" variant="tonal">{{ item.severity }}</v-chip>
            </div>
          </v-alert>
        </div>

        <div class="table-grid">
          <v-card class="panel-card" elevation="0" v-for="table in activeTables" :key="table.title">
            <v-card-title>{{ table.title }}</v-card-title>
            <v-card-text>
              <div class="table-shell">
                <v-table density="comfortable">
                  <thead>
                    <tr>
                      <th v-for="column in table.columns" :key="column.key">{{ column.label }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(row, index) in table.rows" :key="`${table.title}-${index}`">
                      <td v-for="column in table.columns" :key="column.key">{{ formatCell(row[column.key], column.format) }}</td>
                    </tr>
                    <tr v-if="!table.rows.length">
                      <td :colspan="table.columns.length" class="empty-row">No data available.</td>
                    </tr>
                  </tbody>
                </v-table>
              </div>
            </v-card-text>
          </v-card>
        </div>
      </template>

      <v-card v-if="isLoading" class="loading-card mt-3" elevation="0">
        <v-card-text class="d-flex align-center ga-3">
          <v-progress-circular indeterminate color="primary" size="22"></v-progress-circular>
          <span>Loading {{ activeTabLabel }} data...</span>
        </v-card-text>
      </v-card>
    </section>
  </v-container>
</template>

<script>
import { describeAnalyticsError, fetchAnalyticsSection } from '../services/analyticsApi';

const TABS = [
  { value: 'overview', label: 'Overview' },
  { value: 'acquisition', label: 'Acquisition' },
  { value: 'pages', label: 'Pages' },
  { value: 'audience', label: 'Audience' },
  { value: 'conversions', label: 'Conversions' },
  { value: 'realtime', label: 'Realtime' },
  { value: 'seo', label: 'SEO & Marketing' },
  { value: 'alerts', label: 'Alerts' },
];

const EMPTY_SECTIONS = () => ({ overview: null, acquisition: null, pages: null, audience: null, conversions: null, realtime: null, seo: null, alerts: null });

export default {
  name: 'AnalyticsDashboard',
  data() {
    return {
      tabs: TABS,
      activeTab: 'overview',
      selectedDays: 30,
      dayOptions: [{ label: 'Last 7 days', value: 7 }, { label: 'Last 30 days', value: 30 }, { label: 'Last 90 days', value: 90 }, { label: 'Last 180 days', value: 180 }],
      isLoading: false,
      isConfigured: true,
      errorMessage: '',
      missingConfig: [],
      sections: EMPTY_SECTIONS(),
    };
  },
  computed: {
    activeTabLabel() { return this.tabs.find((tab) => tab.value === this.activeTab)?.label || 'analytics'; },
    sectionData() { return this.sections[this.activeTab] || {}; },
    activeCards() {
      const d = this.sectionData;
      if (this.activeTab === 'overview') {
        const s = d.summary || {};
        return [{ label: 'Active Users', value: this.nf(s.activeUsers) }, { label: 'Sessions', value: this.nf(s.sessions) }, { label: 'New Users', value: this.nf(s.newUsers) }, { label: 'Engagement', value: this.pf(s.engagementRate) }, { label: 'Conversions', value: this.nf(s.conversions) }, { label: 'Avg Duration', value: this.df(s.averageSessionDuration) }];
      }
      if (this.activeTab === 'conversions') {
        const s = d.summary || {};
        return [{ label: 'Conversions', value: this.nf(s.conversions) }, { label: 'Sessions', value: this.nf(s.sessions) }, { label: 'Engaged Sessions', value: this.nf(s.engagedSessions) }, { label: 'Conversion Rate', value: this.pf(s.conversionRate) }];
      }
      if (this.activeTab === 'realtime') return [{ label: 'Active Users Now', value: this.nf(d.activeUsers) }];
      if (this.activeTab === 'seo') {
        const s = d.organicSummary || {};
        return [{ label: 'Organic Sessions', value: this.nf(s.sessions) }, { label: 'Organic Users', value: this.nf(s.activeUsers) }, { label: 'Organic Conversions', value: this.nf(s.conversions) }];
      }
      if (this.activeTab === 'alerts') {
        const c = d.comparison || {};
        return [{ label: 'Session Change', value: this.spf(c.sessionChangePercent) }, { label: 'User Change', value: this.spf(c.activeUsersChangePercent) }, { label: 'Conversion Change', value: this.spf(c.conversionsChangePercent) }];
      }
      return [];
    },
    activeAlerts() { return this.activeTab === 'alerts' && Array.isArray(this.sectionData.alerts) ? this.sectionData.alerts : []; },
    activeTables() {
      const d = this.sectionData;
      const t = (title, rows, columns) => ({ title, rows: Array.isArray(rows) ? rows : [], columns });
      if (this.activeTab === 'overview') return [t('Top Source / Medium', d.sources, [{ key: 'sourceMedium', label: 'Source' }, { key: 'sessions', label: 'Sessions', format: 'number' }, { key: 'activeUsers', label: 'Users', format: 'number' }, { key: 'conversions', label: 'Conv.', format: 'number' }]), t('Top Landing Pages', d.pages, [{ key: 'page', label: 'Page' }, { key: 'primaryMetric', label: 'Sessions/Views', format: 'number' }, { key: 'activeUsers', label: 'Users', format: 'number' }, { key: 'conversions', label: 'Conv.', format: 'number' }])];
      if (this.activeTab === 'acquisition') return [t('Channel Performance', d.channels, [{ key: 'channel', label: 'Channel' }, { key: 'sessions', label: 'Sessions', format: 'number' }, { key: 'activeUsers', label: 'Users', format: 'number' }, { key: 'conversions', label: 'Conv.', format: 'number' }, { key: 'engagementRate', label: 'Engagement', format: 'percent' }]), t('Source / Medium', d.sources, [{ key: 'sourceMedium', label: 'Source' }, { key: 'sessions', label: 'Sessions', format: 'number' }, { key: 'activeUsers', label: 'Users', format: 'number' }, { key: 'conversions', label: 'Conv.', format: 'number' }]), t('Campaign Performance', d.campaigns, [{ key: 'campaign', label: 'Campaign' }, { key: 'sessions', label: 'Sessions', format: 'number' }, { key: 'activeUsers', label: 'Users', format: 'number' }, { key: 'conversions', label: 'Conv.', format: 'number' }])];
      if (this.activeTab === 'pages') return [t('Landing Pages', d.landingPages, [{ key: 'page', label: 'Page' }, { key: 'sessions', label: 'Sessions', format: 'number' }, { key: 'activeUsers', label: 'Users', format: 'number' }, { key: 'conversions', label: 'Conv.', format: 'number' }]), t('Top Content', d.topContent, [{ key: 'page', label: 'Page' }, { key: 'views', label: 'Views', format: 'number' }, { key: 'activeUsers', label: 'Users', format: 'number' }, { key: 'averageSessionDuration', label: 'Avg Duration', format: 'duration' }, { key: 'conversions', label: 'Conv.', format: 'number' }])];
      if (this.activeTab === 'audience') return [t('Countries', d.countries, [{ key: 'country', label: 'Country' }, { key: 'activeUsers', label: 'Users', format: 'number' }, { key: 'newUsers', label: 'New', format: 'number' }, { key: 'sessions', label: 'Sessions', format: 'number' }]), t('Cities', d.cities, [{ key: 'city', label: 'City' }, { key: 'country', label: 'Country' }, { key: 'activeUsers', label: 'Users', format: 'number' }, { key: 'sessions', label: 'Sessions', format: 'number' }]), t('Devices', d.devices, [{ key: 'device', label: 'Device' }, { key: 'activeUsers', label: 'Users', format: 'number' }, { key: 'sessions', label: 'Sessions', format: 'number' }, { key: 'conversions', label: 'Conv.', format: 'number' }]), t('New vs Returning', d.newReturning, [{ key: 'segment', label: 'Segment' }, { key: 'activeUsers', label: 'Users', format: 'number' }, { key: 'sessions', label: 'Sessions', format: 'number' }])];
      if (this.activeTab === 'conversions') return [t('Top Events', d.events, [{ key: 'eventName', label: 'Event' }, { key: 'eventCount', label: 'Count', format: 'number' }, { key: 'conversions', label: 'Conv.', format: 'number' }, { key: 'users', label: 'Users', format: 'number' }]), t('Daily Trend', d.series, [{ key: 'date', label: 'Date' }, { key: 'conversions', label: 'Conv.', format: 'number' }, { key: 'sessions', label: 'Sessions', format: 'number' }])];
      if (this.activeTab === 'realtime') return [t('Top Active Pages', d.topPages, [{ key: 'label', label: 'Page' }, { key: 'activeUsers', label: 'Users', format: 'number' }]), t('Top Countries', d.topCountries, [{ key: 'country', label: 'Country' }, { key: 'activeUsers', label: 'Users', format: 'number' }]), t('Realtime Devices', d.topDevices, [{ key: 'device', label: 'Device' }, { key: 'activeUsers', label: 'Users', format: 'number' }])];
      if (this.activeTab === 'seo') return [t('Channel Mix', d.channelMix, [{ key: 'channel', label: 'Channel' }, { key: 'sessions', label: 'Sessions', format: 'number' }, { key: 'activeUsers', label: 'Users', format: 'number' }, { key: 'conversions', label: 'Conv.', format: 'number' }]), t('Organic Landing Pages', d.organicPages, [{ key: 'page', label: 'Page' }, { key: 'sessions', label: 'Sessions', format: 'number' }, { key: 'activeUsers', label: 'Users', format: 'number' }, { key: 'conversions', label: 'Conv.', format: 'number' }])];
      if (this.activeTab === 'alerts') return [t('Top Channels', d.topChannels, [{ key: 'channel', label: 'Channel' }, { key: 'sessions', label: 'Sessions', format: 'number' }])];
      return [];
    },
  },
  watch: {
    selectedDays() { this.sections = EMPTY_SECTIONS(); this.loadSection(this.activeTab, true); },
    activeTab(newValue) { this.loadSection(newValue); },
  },
  async mounted() { await this.loadSection('overview', true); },
  methods: {
    async loadSection(section, force = false) {
      if (!section) return;
      if (!force && this.sections[section]) return;
      this.isLoading = true;
      this.errorMessage = '';
      try {
        const data = await fetchAnalyticsSection(section, this.selectedDays);
        this.isConfigured = data.configured !== false;
        this.missingConfig = Array.isArray(data.missingConfig) ? data.missingConfig : [];
        if (!this.isConfigured) return;
        const payload = section === 'overview'
          ? { summary: data.summary || {}, series: Array.isArray(data.series) ? data.series : [], sources: Array.isArray(data.sources) ? data.sources : [], pages: Array.isArray(data.pages) ? data.pages : [] }
          : (data.data || {});
        this.sections = { ...this.sections, [section]: payload };
      } catch (error) {
        this.errorMessage = describeAnalyticsError(error, `Failed to load ${section} analytics.`);
      } finally {
        this.isLoading = false;
      }
    },
    refreshActiveSection() { this.loadSection(this.activeTab, true); },
    nf(value) { return new Intl.NumberFormat('en-GB', { maximumFractionDigits: 1 }).format(Number(value || 0)); },
    pf(value) { return `${(Number(value || 0) * 100).toFixed(1)}%`; },
    spf(value) { const n = Number(value || 0); return `${n > 0 ? '+' : ''}${n.toFixed(1)}%`; },
    df(value) { const sec = Math.max(0, Math.round(Number(value || 0))); const m = Math.floor(sec / 60); const s = sec % 60; return `${m}m ${s}s`; },
    formatCell(value, format) { if (format === 'number') return this.nf(value); if (format === 'percent') return this.pf(value); if (format === 'duration') return this.df(value); return String(value ?? '-'); },
    alertType(severity) { const s = String(severity || '').toUpperCase(); if (s === 'RED') return 'error'; if (s === 'AMBER') return 'warning'; return 'success'; },
    alertChipColor(severity) { const s = String(severity || '').toUpperCase(); if (s === 'RED') return 'error'; if (s === 'AMBER') return 'warning'; return 'success'; },
  },
};
</script>

<style scoped>
.ga-page { min-height: calc(100vh - 64px); background: #f4f5f9; padding: 20px; }
.ga-shell { max-width: 1360px; margin: 0 auto; }
.ga-header { display: flex; justify-content: space-between; gap: 14px; align-items: flex-start; margin-bottom: 14px; }
.ga-kicker { margin: 0; color: #667085; text-transform: uppercase; letter-spacing: 0.08em; font-size: 0.72rem; font-weight: 700; }
.ga-header h1 { margin: 2px 0 4px; font-size: clamp(1.45rem, 2.2vw, 1.95rem); line-height: 1.1; color: #1d2432; }
.ga-copy { margin: 0; color: #667085; }
.ga-actions { display: flex; gap: 10px; align-items: center; }
.range-select { min-width: 170px; }
.kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 12px; }
.kpi-card { border: 1px solid #e6e8ef; border-radius: 14px; padding: 12px 14px; background: #fff; }
.kpi-card p { margin: 0; font-size: 0.78rem; color: #667085; }
.kpi-card h3 { margin: 8px 0 0; font-size: 1.3rem; line-height: 1.1; color: #1d2432; }
.panel-card, .loading-card { border: 1px solid #e6e8ef; border-radius: 14px; background: #fff; }
.table-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
.table-shell { overflow-x: auto; }
.empty-row { text-align: center; color: #667085; }
@media (max-width: 959px) { .ga-header { flex-direction: column; align-items: stretch; } .table-grid, .kpi-grid { grid-template-columns: minmax(0, 1fr); } }
</style>
