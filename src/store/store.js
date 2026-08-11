import { createStore } from 'vuex'
import axios from 'axios'
import {
  buildLivePhpApiUrl,
  buildPhpApiUrl,
  isLocalHost,
  isUsingLivePhpApi,
} from '../utils/phpApi';

const INBOX_LOCAL_SOURCE = isLocalHost && !isUsingLivePhpApi ? 'local' : 'auto';

const defaultInboxMeta = {
  inboxStatus: 'new',
  inboxAssignedTo: '',
  inboxIsRead: false,
  inboxPriority: 'normal',
  inboxFollowUpAt: null,
  inboxDeletedAt: null,
  inboxUpdatedAt: null,
};

const normalizeInboxType = (value) => {
  const normalized = String(value || '').trim().toLowerCase();
  const map = {
    complaint: 'complaints',
    complaints: 'complaints',
    contact: 'contact',
    contactform: 'contact',
    contacts: 'contact',
    thanks: 'thanks',
    carethanks: 'thanks',
    carerthanks: 'thanks',
    jobapplication: 'jobapplications',
    jobapplications: 'jobapplications',
    jobs: 'jobapplications',
  };
  return map[normalized] || '';
};

const normalizeRecordSource = (value) => {
  const normalized = String(value || '').trim().toLowerCase();
  return normalized === 'remote' ? 'remote' : 'local';
};

const stringValue = (value) => {
  if (value === undefined || value === null) {
    return '';
  }
  return String(value).trim();
};

const apiUrl = (script, action, queryParams = {}) => buildPhpApiUrl(script, action, queryParams);
const liveApiUrl = (script, action, queryParams = {}) => buildLivePhpApiUrl(script, action, queryParams);
const metaApiUrl = (action, queryParams = {}) => buildPhpApiUrl('inboxMeta', action, queryParams);

const ensureArray = (value) => (Array.isArray(value) ? value : []);

const withSource = (rows, source) =>
  ensureArray(rows).map((row) => ({
    ...row,
    __source: row?.__source || source,
  }));

const rowDateValue = (item) => new Date(item?.Date || item?.date || 0).getTime();

const rowMessageId = (item) => stringValue(item?.ID ?? item?.id ?? item?.messageId ?? '');
const rowSource = (item) => normalizeRecordSource(item?.__source || item?.source || 'local');
const rowMetaKey = (item) => `${rowSource(item)}|${rowMessageId(item)}`;

const inboxRowKey = (item) =>
  [
    rowMetaKey(item),
    item?.Email ?? item?.email ?? '',
    item?.Date ?? item?.date ?? '',
    item?.Message ?? item?.message ?? '',
    item?.PhoneNumber ?? item?.Phonenumber ?? item?.phoneNumber ?? item?.phonenumber ?? '',
    item?.FirstName ?? item?.firstname ?? item?.Name ?? item?.name ?? '',
    item?.SecondName ?? item?.secondname ?? '',
    item?.Carer ?? item?.carer ?? '',
  ]
    .join('|')
    .toLowerCase();

const mergeInboxRows = (primaryRows, secondaryRows) => {
  const seen = new Set();
  const merged = [];

  [...ensureArray(primaryRows), ...ensureArray(secondaryRows)].forEach((item) => {
    const key = inboxRowKey(item);
    if (!key || seen.has(key)) {
      return;
    }
    seen.add(key);
    merged.push(item);
  });

  return merged.sort((a, b) => rowDateValue(b) - rowDateValue(a));
};

const normalizeMetaResponse = (meta = {}) => ({
  inboxStatus: String(meta.status || 'new').toLowerCase(),
  inboxAssignedTo: String(meta.assignedTo || ''),
  inboxIsRead: Boolean(meta.isRead),
  inboxPriority: String(meta.priority || 'normal').toLowerCase(),
  inboxFollowUpAt: meta.followUpAt || null,
  inboxDeletedAt: meta.deletedAt || null,
  inboxUpdatedAt: meta.updatedAt || null,
});

const applyMetaToRows = (rows, metaMap = {}) =>
  ensureArray(rows).map((item) => {
    const key = rowMetaKey(item);
    const mergedMeta = {
      ...defaultInboxMeta,
      ...normalizeMetaResponse(metaMap[key] || {}),
    };
    return {
      ...item,
      ...mergedMeta,
    };
  });

const attachMetaToRows = async (inboxType, rows) => {
  const normalizedType = normalizeInboxType(inboxType);
  const sourceRows = ensureArray(rows);

  if (!normalizedType || !sourceRows.length) {
    return applyMetaToRows(sourceRows);
  }

  const records = sourceRows
    .map((item) => ({
      messageId: rowMessageId(item),
      source: rowSource(item),
    }))
    .filter((item) => item.messageId !== '');

  if (!records.length) {
    return applyMetaToRows(sourceRows);
  }

  try {
    const response = await axios.post(metaApiUrl('getMetaBatch'), {
      inboxType: normalizedType,
      records,
    });
    const metaMap = response?.data?.meta || {};
    return applyMetaToRows(sourceRows, metaMap);
  } catch (error) {
    return applyMetaToRows(sourceRows);
  }
};

const resolveDeleteTarget = (script, action, source) => {
  if (isLocalHost && source === 'remote' && !isUsingLivePhpApi) {
    return {
      url: liveApiUrl(script, action, { source: 'auto' }),
      source: 'auto',
    };
  }

  return {
    url: apiUrl(script, action, { source }),
    source,
  };
};

const fetchInboxData = async (script, action, inboxType) => {
  let rows = [];

  if (!isLocalHost || isUsingLivePhpApi) {
    const response = await axios.get(apiUrl(script, action, { source: 'auto' }));
    rows = ensureArray(response.data);
    return attachMetaToRows(inboxType, rows);
  }

  const [localResponse, liveResponse] = await Promise.allSettled([
    axios.get(apiUrl(script, action, { source: INBOX_LOCAL_SOURCE })),
    axios.get(liveApiUrl(script, action, { source: 'auto' })),
  ]);

  const localRows = localResponse.status === 'fulfilled'
    ? withSource(localResponse.value?.data, 'local')
    : [];
  const liveRows = liveResponse.status === 'fulfilled'
    ? withSource(liveResponse.value?.data, 'remote')
    : [];

  const combined = mergeInboxRows(localRows, liveRows);
  if (combined.length) {
    return attachMetaToRows(inboxType, combined);
  }

  if (localResponse.status === 'rejected' && liveResponse.status === 'rejected') {
    throw localResponse.reason || liveResponse.reason;
  }

  return attachMetaToRows(inboxType, []);
};

const resolveDeletePayload = (payload) => {
  if (payload && typeof payload === 'object' && !Array.isArray(payload)) {
    return {
      id: Number(payload.id) || 0,
      source: payload.source || 'auto',
    };
  }

  return {
    id: Number(payload) || 0,
    source: 'auto',
  };
};

const resolveRecordPayload = (payload = {}) => {
  const item = payload?.item || {};
  const messageId = stringValue(payload?.messageId ?? payload?.id ?? item?.ID ?? item?.id);
  const source = normalizeRecordSource(payload?.source ?? item?.__source ?? item?.source ?? 'local');
  return {
    messageId,
    source,
  };
};

const refreshActionForInboxType = (inboxType) => {
  const normalized = normalizeInboxType(inboxType);
  if (normalized === 'complaints') {
    return 'getComplaint';
  }
  if (normalized === 'contact') {
    return 'getContactForm';
  }
  if (normalized === 'thanks') {
    return 'getThanks';
  }
  if (normalized === 'jobapplications') {
    return 'getJobApplications';
  }
  return '';
};

const store = createStore({
  state () {
    return {
      contents: '',
      complaints: [],
      thanks: [],
      ContactForm: [],
      jobApplications: [],
      selectedContactForm: null,
      selectedThanks: null,
      selectedComplaints: null,
      snackbar: { message: '', show: false, type: 'success' },
    }
  },
  getters: {
    availableContents: ({ contents }) => contents,
    availableComplaints: state => state.complaints,
    availableThanks: state => state.thanks,
    selectedThanks: state => state.selectedThanks,
    selectedContactForm: state => state.selectedContactForm,
    availableJobApplications: state => state.jobApplications,
    snackbar: state => state.snackbar,
  },
  mutations: {
    setComplaints(state, complaints) {
      state.complaints = complaints;
    },
    selectComplaints(state, complaints) {
      state.selectedComplaints = complaints;
    },
    setThanks(state, thanks) {
      state.thanks = thanks;
    },
    selectThanks(state, thanks) {
      state.selectedThanks = thanks;
    },
    setContactForm(state, ContactForm) {
      state.ContactForm = ContactForm;
    },
    setJobApplications(state, jobApplications) {
      state.jobApplications = jobApplications;
    },
    selectContactForm(state, ContactForm) {
      state.selectedContactForm = ContactForm;
    },
    setContent(state, payload) {
      state.contents = payload;
    },
    addComplaint(state, complaint) {
      state.complaints.push(complaint);
    },
    addThanks(state, thanks) {
      state.thanks.push(thanks);
    },
    addContactForm(state, ContactForm) {
      state.ContactForm.push(ContactForm);
    },
    addJobApplication(state, jobApplication) {
      state.jobApplications.push(jobApplication);
    },
    showSnackbar(state, payload) {
      if (typeof payload === 'string') {
        state.snackbar = { message: payload, show: true, type: 'success' };
        return;
      }

      state.snackbar = {
        message: payload?.message || '',
        show: true,
        type: payload?.type || 'success',
      };
    },
    hideSnackbar(state) {
      state.snackbar.show = false;
    },
  },
  actions: {
    async getContactForm({ commit }) {
      try {
        const ContactForm = await fetchInboxData('getContact', 'getContact', 'contact');
        commit('setContactForm', ContactForm);
        return ContactForm;
      } catch (error) {
        console.log(error);
        return [];
      }
    },
    async getJobApplications({ commit }) {
      try {
        const jobApplications = await fetchInboxData('getJobApplication', 'getJobApplication', 'jobapplications');
        commit('setJobApplications', jobApplications);
        return jobApplications;
      } catch (error) {
        console.log(error);
        return [];
      }
    },
    async getThanks({ commit }) {
      try {
        const thanks = await fetchInboxData('getThanks', 'getThanks', 'thanks');
        commit('setThanks', thanks);
        return thanks;
      } catch (error) {
        console.log(error);
        return [];
      }
    },
    async getComplaint({ commit }) {
      try {
        const complaints = await fetchInboxData('getComplaint', 'getComplaint', 'complaints');
        commit('setComplaints', complaints);
        return complaints;
      } catch (error) {
        console.log(error);
        return [];
      }
    },
    async deleteComplaint({ dispatch }, payload) {
      const { id, source } = resolveDeletePayload(payload);
      if (id <= 0) {
        return { success: false, message: 'Invalid complaint ID.' };
      }

      try {
        const deleteTarget = resolveDeleteTarget('getComplaint', 'deleteComplaint', source);
        const response = await axios.post(deleteTarget.url, { id, source: deleteTarget.source });
        const result = response?.data || {};

        if (!result.success) {
          return { success: false, message: result.message || 'Failed to delete complaint.' };
        }

        await dispatch('getComplaint');
        return { success: true };
      } catch (error) {
        console.error('Error deleting complaint:', error);
        return { success: false, message: 'Failed to delete complaint. Please try again.' };
      }
    },
    async deleteContactForm({ dispatch }, payload) {
      const { id, source } = resolveDeletePayload(payload);
      if (id <= 0) {
        return { success: false, message: 'Invalid enquiry ID.' };
      }

      try {
        const deleteTarget = resolveDeleteTarget('getContact', 'deleteContact', source);
        const response = await axios.post(deleteTarget.url, { id, source: deleteTarget.source });
        const result = response?.data || {};

        if (!result.success) {
          return { success: false, message: result.message || 'Failed to delete enquiry.' };
        }

        await dispatch('getContactForm');
        return { success: true };
      } catch (error) {
        console.error('Error deleting enquiry:', error);
        return { success: false, message: 'Failed to delete enquiry. Please try again.' };
      }
    },
    async deleteJobApplication({ dispatch }, payload) {
      const { id, source } = resolveDeletePayload(payload);
      if (id <= 0) {
        return { success: false, message: 'Invalid job application ID.' };
      }

      try {
        const deleteTarget = resolveDeleteTarget('getJobApplication', 'deleteJobApplication', source);
        const response = await axios.post(deleteTarget.url, { id, source: deleteTarget.source });
        const result = response?.data || {};

        if (!result.success) {
          return { success: false, message: result.message || 'Failed to delete job application.' };
        }

        await dispatch('getJobApplications');
        return { success: true };
      } catch (error) {
        console.error('Error deleting job application:', error);
        return { success: false, message: 'Failed to delete job application. Please try again.' };
      }
    },
    async deleteThanks({ dispatch }, payload) {
      const { id, source } = resolveDeletePayload(payload);
      if (id <= 0) {
        return { success: false, message: 'Invalid message ID.' };
      }

      try {
        const deleteTarget = resolveDeleteTarget('getThanks', 'deleteThanks', source);
        const response = await axios.post(deleteTarget.url, { id, source: deleteTarget.source });
        const result = response?.data || {};

        if (!result.success) {
          return { success: false, message: result.message || 'Failed to delete message.' };
        }

        await dispatch('getThanks');
        return { success: true };
      } catch (error) {
        console.error('Error deleting message:', error);
        return { success: false, message: 'Failed to delete message. Please try again.' };
      }
    },
    async updateInboxMeta({ dispatch }, payload) {
      const inboxType = normalizeInboxType(payload?.inboxType);
      const { messageId, source } = resolveRecordPayload(payload);
      const patch = payload?.patch && typeof payload.patch === 'object' ? payload.patch : {};

      if (!inboxType || !messageId) {
        return { success: false, message: 'Invalid inbox metadata target.' };
      }

      try {
        const response = await axios.post(metaApiUrl('upsertMeta'), {
          inboxType,
          messageId,
          source,
          patch,
        });
        const result = response?.data || {};
        if (!result.success) {
          return { success: false, message: result.message || 'Failed to save metadata.' };
        }

        const refreshAction = refreshActionForInboxType(inboxType);
        if (refreshAction) {
          await dispatch(refreshAction);
        }

        return { success: true, meta: result.meta || null };
      } catch (error) {
        console.error('Error saving inbox metadata:', error);
        return { success: false, message: 'Failed to save metadata.' };
      }
    },
    async bulkUpdateInboxMeta({ dispatch }, payload) {
      const inboxType = normalizeInboxType(payload?.inboxType);
      const patch = payload?.patch && typeof payload.patch === 'object' ? payload.patch : {};
      const records = ensureArray(payload?.items || payload?.records)
        .map((item) => resolveRecordPayload(item?.messageId || item?.id || item?.item ? item : { item }))
        .filter((record) => record.messageId !== '')
        .map((record) => ({
          messageId: record.messageId,
          source: record.source,
        }));

      if (!inboxType || !records.length) {
        return { success: false, message: 'No records selected.' };
      }

      try {
        const response = await axios.post(metaApiUrl('bulkUpdate'), {
          inboxType,
          records,
          patch,
        });
        const result = response?.data || {};
        if (!result.success) {
          return { success: false, message: result.message || 'Failed to update selected records.' };
        }

        const refreshAction = refreshActionForInboxType(inboxType);
        if (refreshAction) {
          await dispatch(refreshAction);
        }

        return { success: true, updated: Number(result.updated) || 0 };
      } catch (error) {
        console.error('Error bulk-updating inbox metadata:', error);
        return { success: false, message: 'Failed to update selected records.' };
      }
    },
    async getInboxNotes(_, payload) {
      const inboxType = normalizeInboxType(payload?.inboxType);
      const { messageId, source } = resolveRecordPayload(payload);
      if (!inboxType || !messageId) {
        return [];
      }

      try {
        const response = await axios.get(metaApiUrl('getNotes', {
          inboxType,
          messageId,
          source,
        }));
        return ensureArray(response?.data?.notes);
      } catch (error) {
        console.error('Error loading inbox notes:', error);
        return [];
      }
    },
    async addInboxNote(_, payload) {
      const inboxType = normalizeInboxType(payload?.inboxType);
      const { messageId, source } = resolveRecordPayload(payload);
      const note = stringValue(payload?.note);
      const author = stringValue(payload?.author);

      if (!inboxType || !messageId || !note) {
        return { success: false, message: 'Note cannot be empty.' };
      }

      try {
        const response = await axios.post(metaApiUrl('addNote'), {
          inboxType,
          messageId,
          source,
          note,
          author,
        });
        const result = response?.data || {};
        return {
          success: Boolean(result.success),
          note: result.note || null,
          message: result.message || '',
        };
      } catch (error) {
        console.error('Error saving inbox note:', error);
        return { success: false, message: 'Failed to save note.' };
      }
    },
    async getInboxReplies(_, payload) {
      const inboxType = normalizeInboxType(payload?.inboxType);
      const { messageId, source } = resolveRecordPayload(payload);
      if (!inboxType || !messageId) {
        return [];
      }

      try {
        const response = await axios.get(metaApiUrl('getReplies', {
          inboxType,
          messageId,
          source,
        }));
        return ensureArray(response?.data?.replies);
      } catch (error) {
        console.error('Error loading inbox replies:', error);
        return [];
      }
    },
    async sendInboxReply({ dispatch }, payload) {
      const inboxType = normalizeInboxType(payload?.inboxType);
      const { messageId, source } = resolveRecordPayload(payload);
      const toEmail = stringValue(payload?.toEmail);
      const subject = stringValue(payload?.subject);
      const body = stringValue(payload?.body);
      const sentBy = stringValue(payload?.sentBy);

      if (!inboxType || !messageId || !toEmail || !subject || !body) {
        return { success: false, message: 'Missing email fields.' };
      }

      try {
        const response = await axios.post(metaApiUrl('sendReply'), {
          inboxType,
          messageId,
          source,
          toEmail,
          subject,
          body,
          sentBy,
        });
        const result = response?.data || {};

        const refreshAction = refreshActionForInboxType(inboxType);
        if (refreshAction) {
          await dispatch(refreshAction);
        }

        return {
          success: Boolean(result.success),
          logged: Boolean(result.logged),
          message: result.message || '',
          reply: result.reply || null,
        };
      } catch (error) {
        console.error('Error sending inbox reply:', error);
        return { success: false, logged: false, message: 'Failed to send reply.' };
      }
    },
    async saveComplaint({ commit }, complaintData) {
      try {
        const response = await axios.post(apiUrl('addComplaint', 'addpost'), complaintData);
        const result = response?.data || {};
        if (!result.success) {
          const message = result.message || result.error || 'Failed to save complaint. Please try again.';
          commit('showSnackbar', { message, type: 'error' });
          return { success: false, message };
        }

        commit('addComplaint', result);
        commit('showSnackbar', result.message || 'Complaint sent successfully!');
        return result;
      } catch (error) {
        console.error('Error sending complaint:', error);
        const message = error?.response?.data?.message || 'Failed to save complaint. Please try again.';
        commit('showSnackbar', { message, type: 'error' });
        return { success: false, message };
      }
    },
    async saveContactForm({ commit }, ContactFormData) {
      try {
        const response = await axios.post(apiUrl('addContact', 'addpost'), ContactFormData);
        const result = response?.data || {};
        if (!result.success) {
          const message = result.message || result.error || 'Failed to save message. Please try again.';
          commit('showSnackbar', { message, type: 'error' });
          return { success: false, message };
        }

        commit('addContactForm', result);
        commit('showSnackbar', result.message || 'Message sent successfully!');
        return result;
      } catch (error) {
        console.error('Error sending contact form:', error);
        const message = error?.response?.data?.message || 'Failed to save message. Please try again.';
        commit('showSnackbar', { message, type: 'error' });
        return { success: false, message };
      }
    },
    async saveJobApplication({ commit }, jobApplicationData) {
      try {
        const response = await axios.post(apiUrl('addJobApplication', 'addpost'), jobApplicationData);
        const result = response?.data || {};
        if (!result.success) {
          const message = result.message || result.error || 'Failed to submit job application.';
          commit('showSnackbar', { message, type: 'error' });
          return { success: false, message };
        }

        commit('showSnackbar', { message: result.message || 'Job application submitted successfully!', type: 'success' });
        return result;
      } catch (error) {
        console.error('Error saving job application:', error);
        const message = error?.response?.data?.message || error?.response?.data?.error || 'Failed to submit job application. Please try again.';
        commit('showSnackbar', { message, type: 'error' });
        return { success: false, message };
      }
    },
    async saveThanks({ commit }, thanksData) {
      try {
        const response = await axios.post(apiUrl('addThanks', 'addpost'), thanksData);
        const result = response?.data || {};
        if (!result.success) {
          const message = result.message || result.error || 'Failed to save message. Please try again.';
          commit('showSnackbar', { message, type: 'error' });
          return { success: false, message };
        }

        commit('addThanks', result);
        commit('showSnackbar', result.message || 'Message sent successfully!');
        return result;
      } catch (error) {
        console.error('Error sending thanks:', error);
        const message = error?.response?.data?.message || 'Failed to save message. Please try again.';
        commit('showSnackbar', { message, type: 'error' });
        return { success: false, message };
      }
    },
    selectComplaints({ commit }, complaints) {
      commit('selectComplaints', complaints);
    },
    selectContactForm({ commit }, ContactForm) {
      commit('selectContactForm', ContactForm);
    },
    selectThanks({ commit }, thanks) {
      commit('selectThanks', thanks);
    },
    clearSnackbar({ commit }) {
      commit('hideSnackbar');
    },
  },
  modules: {},
})

export default store
