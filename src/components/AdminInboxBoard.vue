<template>
  <v-container fluid class="inbox-board" :style="{ '--brand': brandColor }">
    <v-row no-gutters class="board-layout">
      <v-col cols="12" md="4" lg="3" class="left-col">
        <v-card class="left-card" elevation="0">
          <div class="head-row">
            <div>
              <p class="kicker">Inbox</p>
              <h2>{{ title }}</h2>
            </div>
            <div class="head-actions">
              <v-chip size="small" :color="brandColor" class="count-chip">{{ visibleItems.length }}</v-chip>
              <v-btn size="small" variant="text" :color="showTrash ? 'error' : brandColor" icon="mdi-delete-clock-outline" @click="toggleTrashMode">
                <v-tooltip activator="parent" location="top">{{ showTrash ? 'Back to inbox' : 'Open trash' }}</v-tooltip>
              </v-btn>
              <v-btn size="small" variant="text" :color="brandColor" icon="mdi-refresh" :loading="isRefreshing" @click="refreshItems"></v-btn>
            </div>
          </div>

          <v-text-field
            v-model="search"
            density="comfortable"
            variant="outlined"
            hide-details
            :placeholder="searchPlaceholder"
            prepend-inner-icon="mdi-magnify"
            :color="brandColor"
            class="mb-2"
          ></v-text-field>

          <div v-if="selectedKeys.length" class="bulk-bar">
            <v-checkbox-btn density="compact" :model-value="allVisibleSelected" @update:model-value="toggleSelectAll"></v-checkbox-btn>
            <v-chip size="x-small" variant="tonal">{{ selectedKeys.length }} selected</v-chip>
            <v-btn size="x-small" variant="tonal" :color="brandColor" @click="bulkMeta({ isRead: true }, 'Marked as read.')">Read</v-btn>
            <v-btn size="x-small" variant="tonal" :color="brandColor" @click="bulkMeta({ isRead: false }, 'Marked as unread.')">Unread</v-btn>
            <v-btn size="x-small" variant="tonal" :color="brandColor" @click="bulkMeta({ status: 'in_progress' }, 'Status set to in progress.')">In Progress</v-btn>
            <v-btn size="x-small" variant="tonal" :color="brandColor" @click="bulkMeta({ status: 'resolved' }, 'Status set to resolved.')">Resolved</v-btn>
            <v-btn
              size="x-small"
              variant="tonal"
              :color="showTrash ? 'success' : 'error'"
              @click="showTrash ? bulkMeta({ deletedAt: null }, 'Restored selected.') : bulkMeta({ deletedAt: new Date().toISOString() }, 'Moved selected to trash.')"
            >
              {{ showTrash ? 'Restore' : 'Trash' }}
            </v-btn>
          </div>

          <v-divider class="my-2"></v-divider>

          <div class="list-wrap">
            <v-list v-if="visibleItems.length" density="comfortable" nav>
              <v-list-item
                v-for="item in visibleItems"
                :key="itemKey(item)"
                rounded="lg"
                :active="isActive(item)"
                :class="{ 'row-unread': !item.inboxIsRead }"
                @click="openItem(item)"
              >
                <template #prepend>
                  <v-checkbox-btn
                    density="compact"
                    :model-value="isChecked(item)"
                    @click.stop
                    @update:model-value="setChecked(item, $event)"
                  ></v-checkbox-btn>
                  <v-avatar size="34" class="avatar">{{ initials(item) }}</v-avatar>
                </template>

                <v-list-item-title class="row-name">{{ fullName(item) }}</v-list-item-title>
                <v-list-item-subtitle class="row-msg">{{ itemMessage(item) || 'No message provided' }}</v-list-item-subtitle>

                <div class="row-tags">
                  <v-chip size="x-small" variant="tonal" :color="statusColor(item.inboxStatus)">{{ statusLabel(item.inboxStatus) }}</v-chip>
                  <v-chip size="x-small" variant="tonal" :color="priorityColor(item.inboxPriority)">{{ priorityLabel(item.inboxPriority) }}</v-chip>
                </div>

                <template #append>
                  <div class="append-wrap">
                    <span class="row-date">{{ formatInboxDate(item.Date || item.date) }}</span>
                    <v-tooltip text="Delete" location="top">
                      <template #activator="{ props }">
                        <v-btn
                          v-bind="props"
                          icon="mdi-delete-outline"
                          size="x-small"
                          variant="text"
                          color="error"
                          :loading="isSaving && savingKey === itemKey(item)"
                          :disabled="isSaving"
                          @click.stop.prevent="moveToTrash(item)"
                        ></v-btn>
                      </template>
                    </v-tooltip>
                  </div>
                </template>
              </v-list-item>
            </v-list>
            <div v-else class="empty-list">
              <v-icon size="20">{{ showTrash ? 'mdi-delete-empty-outline' : emptyIcon }}</v-icon>
              <span>{{ showTrash ? 'No trashed messages' : listEmptyText }}</span>
            </div>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" md="8" lg="9" class="right-col">
        <v-card class="right-card" elevation="0">
          <template v-if="activeItem">
            <div class="detail-head">
              <div>
                <p class="kicker">{{ detailKicker }}</p>
                <h3>{{ fullName(activeItem) }}</h3>
              </div>
              <div class="detail-actions">
                <v-btn size="small" variant="text" :color="brandColor" @click="copyValue(itemEmail(activeItem), 'Email')">Copy Email</v-btn>
                <v-btn size="small" variant="text" :color="brandColor" @click="copyValue(itemPhone(activeItem), 'Phone number')">Copy Phone</v-btn>
                <v-btn size="small" variant="text" :color="brandColor" @click="toggleRead(activeItem)">{{ activeItem.inboxIsRead ? 'Mark Unread' : 'Mark Read' }}</v-btn>
                <v-btn v-if="activeItem.inboxDeletedAt" size="small" variant="text" color="success" @click="restoreItem(activeItem)">Restore</v-btn>
                <v-btn v-else size="small" variant="text" color="error" @click="moveToTrash(activeItem)">Move to Trash</v-btn>
                <v-btn v-if="activeItem.inboxDeletedAt" size="small" variant="text" color="error" :loading="isHardDeleting" @click="deletePermanently(activeItem)">Delete Permanently</v-btn>
              </div>
            </div>

            <v-row dense class="mb-2">
              <v-col cols="12" sm="6" lg="4"><v-sheet class="meta-box" rounded="lg"><p>Email</p><span>{{ itemEmail(activeItem) || 'N/A' }}</span></v-sheet></v-col>
              <v-col cols="12" sm="6" lg="4"><v-sheet class="meta-box" rounded="lg"><p>Phone</p><span>{{ itemPhone(activeItem) || 'N/A' }}</span></v-sheet></v-col>
              <v-col cols="12" sm="6" lg="4"><v-sheet class="meta-box" rounded="lg"><p>Submitted</p><span>{{ formatFullDate(activeItem.Date || activeItem.date) }}</span></v-sheet></v-col>
            </v-row>

            <v-sheet v-if="extraMetaLabel && extraMetaValue(activeItem)" class="meta-box mb-2" rounded="lg">
              <p>{{ extraMetaLabel }}</p>
              <span>{{ extraMetaValue(activeItem) }}</span>
            </v-sheet>

            <v-sheet class="meta-box mb-2" rounded="lg">
              <p>Workflow</p>
              <v-row dense>
                <v-col cols="12" sm="6" lg="3">
                  <v-select v-model="workflow.status" :items="statusItems" item-title="label" item-value="value" label="Status" variant="outlined" density="comfortable" hide-details :color="brandColor" @update:model-value="saveStatus"></v-select>
                </v-col>
                <v-col cols="12" sm="6" lg="3">
                  <v-select v-model="workflow.priority" :items="priorityItems" item-title="label" item-value="value" label="Priority" variant="outlined" density="comfortable" hide-details :color="brandColor" @update:model-value="savePriority"></v-select>
                </v-col>
                <v-col cols="12" sm="6" lg="3">
                  <v-text-field v-model="workflow.assignedTo" label="Assigned To" variant="outlined" density="comfortable" hide-details :color="brandColor" @blur="saveAssignedTo" @keyup.enter="saveAssignedTo"></v-text-field>
                </v-col>
                <v-col cols="12" sm="6" lg="3">
                  <v-text-field v-model="workflow.followUpAt" type="datetime-local" label="Follow-up" variant="outlined" density="comfortable" hide-details :color="brandColor" @change="saveFollowUp"></v-text-field>
                </v-col>
              </v-row>
              <div class="follow-up">
                <v-chip size="small" variant="tonal" :color="followUpColor(activeItem)">{{ followUpText(activeItem) }}</v-chip>
              </div>
            </v-sheet>

            <v-sheet class="meta-box mb-2" rounded="lg">
              <div class="label-row">
                <p>{{ messageLabel }}</p>
                <v-chip size="x-small" variant="tonal" :color="brandColor">{{ activeItem.inboxDeletedAt ? 'In Trash' : 'Active' }}</v-chip>
              </div>
              <p class="msg-body">{{ itemMessage(activeItem) || 'No message provided.' }}</p>
            </v-sheet>
            <v-sheet class="meta-box mb-2" rounded="lg">
              <div class="label-row"><p>Internal Notes</p></div>
              <div v-if="notesLoading" class="inline-empty">Loading notes...</div>
              <v-list v-else-if="notes.length" density="compact" class="compact-list">
                <v-list-item v-for="note in notes" :key="note.id">
                  <v-list-item-title class="note-text">{{ note.note }}</v-list-item-title>
                  <v-list-item-subtitle>{{ note.author || 'Admin' }} • {{ formatFullDate(note.createdAt) }}</v-list-item-subtitle>
                </v-list-item>
              </v-list>
              <div v-else class="inline-empty">No internal notes yet.</div>
              <v-textarea v-model="newNote" label="Add internal note..." rows="2" auto-grow hide-details variant="outlined" :color="brandColor" class="mt-2"></v-textarea>
              <div class="notes-actions">
                <v-btn size="small" variant="tonal" :color="brandColor" :loading="isAddingNote" @click="addNote">Save Note</v-btn>
              </div>
            </v-sheet>

            <v-sheet class="meta-box" rounded="lg">
              <div class="label-row">
                <p>{{ replyTitle }}</p>
                <div class="template-row">
                  <v-btn v-for="t in templateButtons" :key="t.key" size="x-small" variant="tonal" :color="brandColor" @click="setTemplate(t.key)">{{ t.label }}</v-btn>
                </div>
              </div>

              <v-text-field label="To" :model-value="itemEmail(activeItem)" readonly variant="outlined" density="comfortable" hide-details :color="brandColor" class="mb-2"></v-text-field>
              <v-text-field v-model="replySubject" label="Subject" variant="outlined" density="comfortable" hide-details :color="brandColor" class="mb-2"></v-text-field>

              <div class="timeline-wrap">
                <p class="small-label">Reply Timeline</p>
                <div v-if="repliesLoading" class="inline-empty">Loading reply history...</div>
                <v-list v-else-if="replies.length" density="compact" class="compact-list timeline-list">
                  <v-list-item v-for="reply in replies" :key="reply.id">
                    <v-list-item-title>{{ reply.subject }}</v-list-item-title>
                    <v-list-item-subtitle>
                      {{ reply.sentBy || 'Admin' }} • {{ formatFullDate(reply.createdAt) }} •
                      <span :class="reply.sentSuccess ? 'sent-ok' : 'sent-fail'">{{ reply.sentSuccess ? 'Sent' : 'Failed' }}</span>
                    </v-list-item-subtitle>
                    <p class="timeline-body">{{ reply.body }}</p>
                    <p v-if="reply.errorMessage" class="timeline-error">{{ reply.errorMessage }}</p>
                  </v-list-item>
                </v-list>
                <div v-else class="inline-empty">No replies logged yet.</div>
              </div>

              <v-textarea v-model="message" label="Type your response..." rows="4" auto-grow hide-details variant="outlined" :color="brandColor" class="mb-2"></v-textarea>

              <div class="reply-actions">
                <v-btn variant="text" color="#6B7280" @click="message = ''">Clear</v-btn>
                <v-btn class="send-btn" :loading="isSendingReply" @click="sendReply">
                  <v-icon size="18" class="mr-1">mdi-send</v-icon>
                  Send Email
                </v-btn>
              </div>
            </v-sheet>
          </template>

          <div v-else class="empty-detail">
            <v-icon size="44" :color="brandColor">{{ emptyIcon }}</v-icon>
            <h3>{{ emptyDetailTitle }}</h3>
            <p>{{ emptyDetailText }}</p>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <v-snackbar v-model="snackbar.show" :timeout="3200" :color="snackbar.color" location="top right">
      {{ snackbar.text }}
      <template #actions>
        <v-btn variant="text" color="white" @click="snackbar.show = false">Close</v-btn>
      </template>
    </v-snackbar>
  </v-container>
</template>

<script>
const AUTH_STORAGE_KEY = 'facilitateCurrentUser';

export default {
  props: {
    inboxType: { type: String, required: true },
    title: { type: String, required: true },
    brandColor: { type: String, default: '#AB207D' },
    detailKicker: { type: String, default: 'Message Detail' },
    listEmptyText: { type: String, default: 'No messages found' },
    emptyDetailTitle: { type: String, default: 'Select a Message' },
    emptyDetailText: { type: String, default: 'Choose a message from the left panel to view details.' },
    messageLabel: { type: String, default: 'Message' },
    replyTitle: { type: String, default: 'Reply to Sender' },
    searchPlaceholder: { type: String, default: 'Search messages...' },
    emptyIcon: { type: String, default: 'mdi-email-open-outline' },
    replySubjectPrefix: { type: String, default: 'Re: Message - ' },
    extraMetaLabel: { type: String, default: '' },
    extraMetaField: { type: String, default: '' },
  },
  data() {
    return {
      search: '',
      showTrash: false,
      localSelected: null,
      selectedKeys: [],
      isRefreshing: false,
      isSaving: false,
      savingKey: '',
      isHardDeleting: false,
      workflow: { status: 'new', priority: 'normal', assignedTo: '', followUpAt: '' },
      newNote: '',
      notes: [],
      notesLoading: false,
      isAddingNote: false,
      replies: [],
      repliesLoading: false,
      replySubject: '',
      message: '',
      isSendingReply: false,
      currentUser: null,
      nowTick: Date.now(),
      ticker: null,
      snackbar: { show: false, text: '', color: 'info' },
    };
  },
  computed: {
    allItems() {
      if (this.inboxType === 'complaints') return Array.isArray(this.$store.state.complaints) ? this.$store.state.complaints : [];
      if (this.inboxType === 'thanks') return Array.isArray(this.$store.state.thanks) ? this.$store.state.thanks : [];
      if (this.inboxType === 'jobapplications') return Array.isArray(this.$store.state.jobApplications) ? this.$store.state.jobApplications : [];
      return Array.isArray(this.$store.state.ContactForm) ? this.$store.state.ContactForm : [];
    },
    filteredItems() {
      const query = this.search.trim().toLowerCase();
      const sorted = [...this.allItems].sort((a, b) => new Date(b?.Date || b?.date || 0) - new Date(a?.Date || a?.date || 0));
      if (!query) return sorted;
      return sorted.filter((item) => {
        const text = [
          item.Name, item.name, item.FirstName, item.firstname, item.SecondName, item.secondname,
          item.FullName, item.fullname, item.Title, item.JobType, item.jobtype,
          item.HasDomiciliaryExperience, item.ExperienceDuration, item.HasDriverLicense,
          item.LicenseType, item.UkLicenseType, item.City, item.ResidenceArea, item.ResidenceDuration,
          item.Email, item.email, item.PhoneNumber, item.Phonenumber, item.phoneNumber, item.phonenumber,
          item.Message, item.message, item.Carer, item.carer, item.inboxAssignedTo, item.inboxStatus, item.inboxPriority,
        ].filter(Boolean).join(' ').toLowerCase();
        return text.includes(query);
      });
    },
    visibleItems() {
      return this.filteredItems.filter((item) => this.showTrash ? Boolean(item.inboxDeletedAt) : !item.inboxDeletedAt);
    },
    activeItem() {
      return this.localSelected;
    },
    allVisibleSelected() {
      return this.visibleItems.length > 0 && this.visibleItems.every((item) => this.selectedKeys.includes(this.itemKey(item)));
    },
    statusItems() {
      return [
        { label: 'New', value: 'new' },
        { label: 'In Progress', value: 'in_progress' },
        { label: 'Resolved', value: 'resolved' },
        { label: 'Closed', value: 'closed' },
      ];
    },
    priorityItems() {
      return [
        { label: 'Low', value: 'low' },
        { label: 'Normal', value: 'normal' },
        { label: 'High', value: 'high' },
        { label: 'Urgent', value: 'urgent' },
      ];
    },
    templateButtons() {
      if (this.inboxType === 'thanks') {
        return [
          { key: 'appreciate', label: 'Thank Them' },
          { key: 'share', label: 'Share Team' },
          { key: 'followup', label: 'Follow Up' },
        ];
      }
      if (this.inboxType === 'complaints') {
        return [
          { key: 'ack', label: 'Acknowledge' },
          { key: 'details', label: 'Ask Details' },
          { key: 'resolved', label: 'Resolved' },
        ];
      }
      return [
        { key: 'ack', label: 'Acknowledge' },
        { key: 'callback', label: 'Request Call' },
        { key: 'followup', label: 'Follow Up' },
      ];
    },
    currentUserName() {
      return this.currentUser?.name || this.currentUser?.username || 'Admin';
    },
  },
  watch: {
    allItems: {
      deep: true,
      handler() {
        const visibleSet = new Set(this.visibleItems.map((item) => this.itemKey(item)));
        this.selectedKeys = this.selectedKeys.filter((key) => visibleSet.has(key));
        if (!this.activeItem) return;
        const key = this.itemKey(this.activeItem);
        const next = this.allItems.find((item) => this.itemKey(item) === key);
        if (next) {
          this.localSelected = { ...next };
          this.syncWorkflowFromActive();
        } else {
          this.localSelected = null;
          this.notes = [];
          this.replies = [];
        }
      },
    },
  },
  methods: {
    showToast(text, color = 'info') { this.snackbar = { show: true, text, color }; },
    fetchActionName() {
      if (this.inboxType === 'complaints') return 'getComplaint';
      if (this.inboxType === 'thanks') return 'getThanks';
      if (this.inboxType === 'jobapplications') return 'getJobApplications';
      return 'getContactForm';
    },
    hardDeleteActionName() {
      if (this.inboxType === 'complaints') return 'deleteComplaint';
      if (this.inboxType === 'thanks') return 'deleteThanks';
      if (this.inboxType === 'jobapplications') return 'deleteJobApplication';
      return 'deleteContactForm';
    },
    itemKey(item) { return [item?.__source || 'local', item?.ID ?? item?.id ?? '', item?.Date || item?.date || '', item?.Message || item?.message || ''].join('|'); },
    itemId(item) { return Number(item?.ID ?? item?.id ?? 0); },
    itemSource(item) { return String(item?.__source || item?.source || '').toLowerCase() === 'remote' ? 'remote' : 'local'; },
    fullName(item) {
      const split = `${item?.FirstName || item?.firstname || ''} ${item?.SecondName || item?.secondname || ''}`.trim();
      return split || item?.FullName || item?.fullname || item?.Names || item?.names || item?.Name || item?.name || 'Unknown Sender';
    },
    initials(item) { const parts = this.fullName(item).split(' ').filter(Boolean); return `${parts[0]?.[0] || ''}${parts[1]?.[0] || ''}`.toUpperCase() || 'NA'; },
    itemMessage(item) { return item?.Message || item?.message || ''; },
    itemEmail(item) { return item?.Email || item?.email || ''; },
    itemPhone(item) { return item?.PhoneNumber || item?.Phonenumber || item?.phoneNumber || item?.phonenumber || ''; },
    extraMetaValue(item) {
      if (!this.extraMetaField) {
        return '';
      }

      const direct = item?.[this.extraMetaField] || item?.[this.extraMetaField.toLowerCase()];
      if (direct) {
        return direct;
      }

      const target = String(this.extraMetaField).toLowerCase();
      const matchKey = Object.keys(item || {}).find((key) => String(key).toLowerCase() === target);
      return matchKey ? item?.[matchKey] || '' : '';
    },
    isActive(item) { return this.activeItem ? this.itemKey(this.activeItem) === this.itemKey(item) : false; },
    isChecked(item) { return this.selectedKeys.includes(this.itemKey(item)); },
    setChecked(item, value) {
      const key = this.itemKey(item);
      if (value) {
        if (!this.selectedKeys.includes(key)) this.selectedKeys.push(key);
      } else {
        this.selectedKeys = this.selectedKeys.filter((entry) => entry !== key);
      }
    },
    toggleSelectAll(value) { this.selectedKeys = value ? this.visibleItems.map((item) => this.itemKey(item)) : []; },
    statusLabel(value) { const v = String(value || 'new').toLowerCase(); return v === 'in_progress' ? 'In Progress' : v === 'resolved' ? 'Resolved' : v === 'closed' ? 'Closed' : 'New'; },
    statusColor(value) { const v = String(value || 'new').toLowerCase(); return v === 'resolved' ? 'success' : v === 'closed' ? 'blue-grey' : v === 'in_progress' ? this.brandColor : 'warning'; },
    priorityLabel(value) { const v = String(value || 'normal').toLowerCase(); return v === 'urgent' ? 'Urgent' : v === 'high' ? 'High' : v === 'low' ? 'Low' : 'Normal'; },
    priorityColor(value) { const v = String(value || 'normal').toLowerCase(); return v === 'urgent' ? 'error' : v === 'high' ? 'deep-orange' : v === 'low' ? 'teal' : 'grey'; },
    formatInboxDate(date) {
      if (!date) return '';
      const now = new Date();
      const target = new Date(date);
      const dayDiff = Math.floor((now - target) / (1000 * 60 * 60 * 24));
      if (dayDiff < 1) return target.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      if (dayDiff < 7) return target.toLocaleDateString([], { weekday: 'short' });
      return target.toLocaleDateString([], { day: '2-digit', month: 'short' });
    },
    formatFullDate(date) {
      if (!date) return 'N/A';
      const d = new Date(date);
      if (Number.isNaN(d.getTime())) return 'N/A';
      return d.toLocaleDateString([], { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    },
    followUpText(item) {
      if (!item?.inboxFollowUpAt) return 'No reminder set';
      const target = new Date(item.inboxFollowUpAt).getTime();
      if (Number.isNaN(target)) return 'Reminder date invalid';
      const diff = target - this.nowTick;
      const mins = Math.round(Math.abs(diff) / 60000);
      if (diff < 0) return mins < 60 ? `Overdue by ${mins}m` : `Overdue by ${Math.round(mins / 60)}h`;
      return mins < 60 ? `Due in ${mins}m` : `Due in ${Math.round(mins / 60)}h`;
    },
    followUpColor(item) {
      if (!item?.inboxFollowUpAt) return 'grey';
      const target = new Date(item.inboxFollowUpAt).getTime();
      if (Number.isNaN(target)) return 'grey';
      const diff = target - this.nowTick;
      if (diff < 0) return 'error';
      if (diff < 4 * 60 * 60 * 1000) return 'warning';
      return 'success';
    },
    toInputDate(value) {
      if (!value) return '';
      const d = new Date(value);
      if (Number.isNaN(d.getTime())) return '';
      const pad = (n) => String(n).padStart(2, '0');
      return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    },
    syncWorkflowFromActive() {
      if (!this.activeItem) return;
      this.workflow.status = this.activeItem.inboxStatus || 'new';
      this.workflow.priority = this.activeItem.inboxPriority || 'normal';
      this.workflow.assignedTo = this.activeItem.inboxAssignedTo || '';
      this.workflow.followUpAt = this.toInputDate(this.activeItem.inboxFollowUpAt);
      this.replySubject = `${this.replySubjectPrefix}${this.fullName(this.activeItem)}`;
    },
    async refreshItems() {
      this.isRefreshing = true;
      const activeKey = this.activeItem ? this.itemKey(this.activeItem) : '';
      try {
        await this.$store.dispatch(this.fetchActionName());
        if (activeKey) {
          const next = this.allItems.find((item) => this.itemKey(item) === activeKey);
          if (next) this.localSelected = { ...next };
        }
      } finally {
        this.isRefreshing = false;
      }
    },
    async updateMeta(item, patch, successMessage = '') {
      if (!item) return false;
      this.isSaving = true;
      this.savingKey = this.itemKey(item);
      try {
        const result = await this.$store.dispatch('updateInboxMeta', { inboxType: this.inboxType, item, patch });
        if (!result?.success) {
          this.showToast(result?.message || 'Failed to update message.', 'error');
          return false;
        }
        if (successMessage) this.showToast(successMessage, 'success');
        return true;
      } finally {
        this.isSaving = false;
        this.savingKey = '';
      }
    },
    async bulkMeta(patch, successMessage) {
      const items = this.visibleItems.filter((item) => this.selectedKeys.includes(this.itemKey(item)));
      if (!items.length) {
        this.showToast('Select at least one message first.', 'warning');
        return;
      }
      const result = await this.$store.dispatch('bulkUpdateInboxMeta', { inboxType: this.inboxType, items, patch });
      if (!result?.success) {
        this.showToast(result?.message || 'Bulk update failed.', 'error');
        return;
      }
      this.selectedKeys = [];
      this.showToast(successMessage, 'success');
    },
    async openItem(item) {
      this.localSelected = { ...item };
      this.syncWorkflowFromActive();
      this.newNote = '';
      this.message = '';
      await Promise.all([this.loadNotes(), this.loadReplies()]);
      if (!item.inboxIsRead) await this.updateMeta(item, { isRead: true });
    },
    async moveToTrash(item) {
      await this.updateMeta(item, { deletedAt: new Date().toISOString() }, 'Moved to trash.');
      if (!this.showTrash && this.activeItem && this.itemKey(this.activeItem) === this.itemKey(item)) this.localSelected = null;
    },
    async restoreItem(item) {
      await this.updateMeta(item, { deletedAt: null }, 'Message restored.');
      if (this.showTrash && this.activeItem && this.itemKey(this.activeItem) === this.itemKey(item)) this.localSelected = null;
    },
    async toggleRead(item) { await this.updateMeta(item, { isRead: !item.inboxIsRead }, 'Read state updated.'); },
    async saveStatus() { if (this.activeItem) await this.updateMeta(this.activeItem, { status: this.workflow.status }, 'Status updated.'); },
    async savePriority() { if (this.activeItem) await this.updateMeta(this.activeItem, { priority: this.workflow.priority }, 'Priority updated.'); },
    async saveAssignedTo() { if (this.activeItem) await this.updateMeta(this.activeItem, { assignedTo: this.workflow.assignedTo }, 'Assignee updated.'); },
    async saveFollowUp() { if (this.activeItem) await this.updateMeta(this.activeItem, { followUpAt: this.workflow.followUpAt || null }, 'Reminder updated.'); },
    async deletePermanently(item) {
      const id = this.itemId(item);
      if (id <= 0) return this.showToast('Cannot delete permanently: missing ID.', 'warning');
      if (!window.confirm('Delete this message permanently? This cannot be undone.')) return;
      this.isHardDeleting = true;
      try {
        const result = await this.$store.dispatch(this.hardDeleteActionName(), {
          id,
          source: this.itemSource(item),
        });
        if (!result?.success) return this.showToast(result?.message || 'Permanent delete failed.', 'error');
        this.localSelected = null;
        this.selectedKeys = this.selectedKeys.filter((key) => key !== this.itemKey(item));
        this.showToast('Message deleted permanently.', 'success');
      } finally {
        this.isHardDeleting = false;
      }
    },
    toggleTrashMode() {
      this.showTrash = !this.showTrash;
      this.selectedKeys = [];
      if (!this.activeItem) return;
      if (this.showTrash && !this.activeItem.inboxDeletedAt) this.localSelected = null;
      if (!this.showTrash && this.activeItem.inboxDeletedAt) this.localSelected = null;
    },
    async loadNotes() {
      if (!this.activeItem) return;
      this.notesLoading = true;
      try {
        this.notes = await this.$store.dispatch('getInboxNotes', { inboxType: this.inboxType, item: this.activeItem });
      } finally {
        this.notesLoading = false;
      }
    },
    async addNote() {
      if (!this.activeItem) return this.showToast('Select a message first.', 'warning');
      const note = this.newNote.trim();
      if (!note) return this.showToast('Type a note before saving.', 'warning');
      this.isAddingNote = true;
      try {
        const result = await this.$store.dispatch('addInboxNote', { inboxType: this.inboxType, item: this.activeItem, note, author: this.currentUserName });
        if (!result?.success) return this.showToast(result?.message || 'Failed to save note.', 'error');
        this.newNote = '';
        await this.loadNotes();
        this.showToast('Internal note saved.', 'success');
      } finally {
        this.isAddingNote = false;
      }
    },
    async loadReplies() {
      if (!this.activeItem) return;
      this.repliesLoading = true;
      try {
        this.replies = await this.$store.dispatch('getInboxReplies', { inboxType: this.inboxType, item: this.activeItem });
      } finally {
        this.repliesLoading = false;
      }
    },
    setTemplate(type) {
      if (!this.activeItem) return;
      const name = this.fullName(this.activeItem);
      const carer = this.extraMetaValue(this.activeItem) || 'our team member';
      if (this.inboxType === 'thanks') {
        if (type === 'appreciate') return void (this.message = `Hello ${name},\n\nThank you for your kind feedback. We truly appreciate your message and are glad to hear about your positive experience.\n\nKind regards,\nFacilitate Care Services`);
        if (type === 'share') return void (this.message = `Hello ${name},\n\nThank you for recognizing ${carer}. We have shared your message with the team and they were delighted to receive your feedback.\n\nKind regards,\nFacilitate Care Services`);
        return void (this.message = `Hello ${name},\n\nThank you again for your feedback. If there is anything else you would like to share, we would be happy to hear from you.\n\nKind regards,\nFacilitate Care Services`);
      }
      if (this.inboxType === 'complaints') {
        if (type === 'ack') return void (this.message = `Hello ${name},\n\nThank you for your complaint. We have received it and our team is currently reviewing the details.\n\nKind regards,\nFacilitate Care Services`);
        if (type === 'details') return void (this.message = `Hello ${name},\n\nThank you for contacting us. Could you please share any additional details so we can investigate this quickly?\n\nKind regards,\nFacilitate Care Services`);
        return void (this.message = `Hello ${name},\n\nThank you for your patience. Your complaint has now been reviewed and resolved. If you need anything else, please reply to this email.\n\nKind regards,\nFacilitate Care Services`);
      }
      if (type === 'ack') return void (this.message = `Hello ${name},\n\nThank you for contacting us. Your enquiry has been received and a member of our team is reviewing it.\n\nKind regards,\nFacilitate Care Services`);
      if (type === 'callback') return void (this.message = `Hello ${name},\n\nThank you for your enquiry. Please confirm the best contact number and time for a callback, and we will get in touch shortly.\n\nKind regards,\nFacilitate Care Services`);
      this.message = `Hello ${name},\n\nFollowing up on your enquiry. If you need any additional information, please reply to this email and we will assist right away.\n\nKind regards,\nFacilitate Care Services`;
    },
    async sendReply() {
      if (!this.activeItem) return this.showToast('Select a message first.', 'warning');
      const toEmail = this.itemEmail(this.activeItem);
      if (!toEmail) return this.showToast('No email address found for this message.', 'warning');
      if (!this.replySubject.trim()) return this.showToast('Add a subject before sending.', 'warning');
      if (!this.message.trim()) return this.showToast('Type your response before sending.', 'warning');
      this.isSendingReply = true;
      try {
        const result = await this.$store.dispatch('sendInboxReply', {
          inboxType: this.inboxType,
          item: this.activeItem,
          toEmail,
          subject: this.replySubject.trim(),
          body: this.message.trim(),
          sentBy: this.currentUserName,
        });
        if (!result?.success) this.showToast(result?.message || 'Reply failed to send.', 'error');
        else {
          this.showToast('Reply sent and logged.', 'success');
          this.message = '';
        }
        await this.loadReplies();
      } finally {
        this.isSendingReply = false;
      }
    },
    async copyValue(value, label) {
      if (!value) return this.showToast(`${label} is not available.`, 'warning');
      try {
        if (navigator.clipboard?.writeText) await navigator.clipboard.writeText(value);
        else {
          const input = document.createElement('textarea');
          input.value = value;
          document.body.appendChild(input);
          input.select();
          document.execCommand('copy');
          document.body.removeChild(input);
        }
        this.showToast(`${label} copied.`, 'success');
      } catch (error) {
        this.showToast(`Unable to copy ${label.toLowerCase()}.`, 'error');
      }
    },
    loadUser() {
      try {
        const stored = localStorage.getItem(AUTH_STORAGE_KEY);
        this.currentUser = stored ? JSON.parse(stored) : null;
      } catch (error) {
        this.currentUser = null;
      }
    },
  },
  async mounted() {
    this.loadUser();
    this.ticker = window.setInterval(() => { this.nowTick = Date.now(); }, 60000);
    await this.refreshItems();
  },
  beforeUnmount() {
    if (this.ticker) window.clearInterval(this.ticker);
  },
};
</script>

<style scoped>
.inbox-board { min-height: calc(100vh - 74px); padding: 16px; background: linear-gradient(145deg, #f7f2f9 0%, #f3f6fb 100%); }
.board-layout { margin: 0 -8px; }
.left-col, .right-col { padding: 0 8px; }
.left-card, .right-card { border: 1px solid #e8e3eb; border-radius: 16px; background: #fff; box-shadow: 0 10px 24px rgba(41, 22, 47, 0.06); }
.left-card { height: calc(100vh - 110px); padding: 16px; }
.right-card { height: calc(100vh - 110px); padding: 18px; overflow: auto; }
.head-row { display: flex; justify-content: space-between; gap: 10px; align-items: center; }
.kicker { margin: 0; font-size: .74rem; text-transform: uppercase; letter-spacing: .1em; color: #8d8198; font-weight: 700; }
.head-row h2 { margin: 4px 0 0; font-size: 1.26rem; }
.head-actions { display: flex; align-items: center; gap: 4px; }
.count-chip { color: #fff; font-weight: 600; }
.bulk-bar { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-bottom: 6px; }
.list-wrap { height: calc(100% - 162px); overflow: auto; }
.row-unread .row-name { font-weight: 700; }
.avatar { background: linear-gradient(135deg, var(--brand) 0%, #c44da0 100%); color: #fff; font-weight: 700; font-size: .76rem; }
.row-name { font-size: .92rem; color: #25192d; }
.row-msg { font-size: .8rem; color: #76697f; }
.row-tags { display: flex; gap: 4px; margin-top: 4px; }
.append-wrap { display: flex; align-items: center; gap: 2px; }
.row-date { font-size: .72rem; color: #8a7d95; font-weight: 600; }
.empty-list { height: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; color: #9b8ea6; }
.detail-head { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; margin-bottom: 12px; }
.detail-head h3 { margin: 4px 0 0; font-size: 1.24rem; }
.detail-actions { display: flex; flex-wrap: wrap; gap: 4px; }
.meta-box { border: 1px solid #e8e3eb; background: #fcfbfd; padding: 12px 14px; }
.meta-box p { margin: 0 0 4px; font-size: .74rem; text-transform: uppercase; letter-spacing: .08em; color: #8d8198; font-weight: 700; }
.meta-box span { color: #25192d; font-size: .92rem; }
.label-row { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
.template-row { display: flex; gap: 6px; flex-wrap: wrap; }
.msg-body, .timeline-body, .note-text { white-space: pre-line; }
.follow-up, .notes-actions, .reply-actions { display: flex; justify-content: flex-end; margin-top: 8px; gap: 8px; }
.timeline-wrap { margin-bottom: 10px; }
.timeline-list { max-height: 170px; overflow: auto; }
.timeline-error { color: #c62828; margin: 6px 0 0; font-size: .78rem; }
.sent-ok { color: #2e7d32; }
.sent-fail { color: #c62828; }
.inline-empty { font-size: .86rem; color: #8b7ea0; padding: 6px 0; }
.send-btn { color: #fff; background: linear-gradient(135deg, var(--brand) 0%, #7f2f68 100%); text-transform: none; font-weight: 600; }
.empty-detail { height: 100%; display: grid; place-content: center; text-align: center; gap: 8px; color: #7f7390; }
.empty-detail h3, .empty-detail p { margin: 0; }
@media (max-width: 960px) {
  .inbox-board { padding: 12px; }
  .board-layout { margin: 0; }
  .left-col, .right-col { padding: 0; }
  .left-card, .right-card { height: auto; min-height: 320px; }
  .right-col { margin-top: 12px; }
  .list-wrap { height: 320px; }
  .detail-head, .label-row { flex-direction: column; align-items: flex-start; }
}
</style>

