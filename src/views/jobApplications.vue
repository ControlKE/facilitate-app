<template>
  <AdminInboxBoard
    inbox-type="jobapplications"
    title="Job Applications"
    brand-color="#AB207D"
    detail-kicker="Application Detail"
    list-empty-text="No job applications found"
    empty-detail-title="Select an Application"
    empty-detail-text="Choose a job application from the left panel to load full details here."
    message-label="Application Summary"
    reply-title="Reply to Applicant"
    search-placeholder="Search job applications..."
    empty-icon="mdi-briefcase-search-outline"
    reply-subject-prefix="Re: Job Application - "
    extra-meta-label="Job Type"
    extra-meta-field="JobType"
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
