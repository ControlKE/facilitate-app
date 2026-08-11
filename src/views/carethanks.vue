<template>
  <AdminInboxBoard
    inbox-type="thanks"
    title="Care Thanks"
    brand-color="#AB207D"
    detail-kicker="Care Thanks Detail"
    list-empty-text="No care-thanks messages found"
    empty-detail-title="Select a Message"
    empty-detail-text="Choose a care-thanks message from the left panel to load full details here."
    message-label="Thank You Message"
    reply-title="Reply to Sender"
    search-placeholder="Search care-thanks messages..."
    empty-icon="mdi-heart-outline"
    reply-subject-prefix="Re: Thank You - "
    extra-meta-label="Carer Mentioned"
    extra-meta-field="Carer"
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
