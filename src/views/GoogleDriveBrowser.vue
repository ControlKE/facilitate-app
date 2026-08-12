<template>
  <div class="drive-browser">
    <v-alert
      v-if="!hasFolderId"
      type="warning"
      variant="tonal"
      class="mb-4"
    >
      No Google Drive folder is configured yet.
    </v-alert>

    <template v-else>
      <div class="drive-toolbar">
        <span class="drive-toolbar-text">
          Showing the company's shared Drive folder. Sign in with your
          <strong>@facilitatecareservices.co.uk</strong> Google account if prompted.
        </span>
        <v-btn
          size="small"
          variant="outlined"
          color="primary"
          prepend-icon="mdi-open-in-new"
          :href="openInDriveUrl"
          target="_blank"
          rel="noopener noreferrer"
        >
          Open in Google Drive
        </v-btn>
      </div>

      <div class="drive-frame-shell">
        <iframe
          :src="embedUrl"
          title="Facilitate Care Google Drive"
          class="drive-frame"
          allow="clipboard-write"
        ></iframe>
      </div>
    </template>
  </div>
</template>

<script>
// Company-wide shared Drive folder ("Facilitate Care"). Shared as
// "Anyone at facilitatecareservices.co.uk with the link" so every staff
// member sees the same company folder regardless of which computer or
// personal Google account is otherwise signed into their browser.
const COMPANY_DRIVE_FOLDER_ID = '1YkMFmpkLrpGfrd8TIWBQoKeQjBSH4_8J';

export default {
  name: 'GoogleDriveBrowser',
  computed: {
    hasFolderId() {
      return Boolean(COMPANY_DRIVE_FOLDER_ID);
    },
    embedUrl() {
      return `https://drive.google.com/embeddedfolderview?id=${COMPANY_DRIVE_FOLDER_ID}#list`;
    },
    openInDriveUrl() {
      return `https://drive.google.com/drive/folders/${COMPANY_DRIVE_FOLDER_ID}`;
    },
  },
};
</script>

<style scoped>
.drive-browser {
  padding: 20px;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.drive-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
  flex-wrap: wrap;
}

.drive-toolbar-text {
  font-size: 0.85rem;
  color: rgba(0, 0, 0, 0.64);
}

.drive-frame-shell {
  flex: 1;
  min-height: 640px;
  border: 1px solid #e6e8ef;
  border-radius: 12px;
  overflow: hidden;
  background: #fff;
}

.drive-frame {
  width: 100%;
  height: 100%;
  min-height: 640px;
  border: 0;
}
</style>
