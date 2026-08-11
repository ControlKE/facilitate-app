<template>
  <AdminInboxBoard
    inbox-type="contact"
    title="General Enquiries"
    brand-color="#AB207D"
    detail-kicker="Enquiry Detail"
    list-empty-text="No enquiries found"
    empty-detail-title="Select an Enquiry"
    empty-detail-text="Choose an enquiry from the left panel to load full details here."
    message-label="Enquiry Message"
    reply-title="Reply to Enquiry"
    search-placeholder="Search enquiries..."
    empty-icon="mdi-email-search-outline"
    reply-subject-prefix="Re: Enquiry - "
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
