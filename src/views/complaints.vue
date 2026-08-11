<template>
  <AdminInboxBoard
    inbox-type="complaints"
    title="Complaints"
    brand-color="#AB207D"
    detail-kicker="Complaint Detail"
    list-empty-text="No complaints found"
    empty-detail-title="Select a Complaint"
    empty-detail-text="Choose a complaint from the left panel to load full details here."
    message-label="Complaint Message"
    reply-title="Reply to Complaint"
    search-placeholder="Search complaints..."
    empty-icon="mdi-alert-circle-outline"
    reply-subject-prefix="Re: Complaint - "
  />
</template>

<script>
import AdminInboxBoard from '../components/AdminInboxBoard.vue';

export default {
  components: {
    AdminInboxBoard,
  },
};
</script>
