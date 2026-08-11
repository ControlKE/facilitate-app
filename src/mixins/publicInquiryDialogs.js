export default {
  data() {
    return {
      titlecb: '',
      FNametc: '',
      SNametc: '',
      mailtc: '',
      phonetc: '',
      cnametc: '',
      messagetc: '',
      complaintTitle: '',
      complaintFName: '',
      complaintSName: '',
      complaintEmail: '',
      complaintPhone: '',
      complaintMsg: '',
    };
  },
  methods: {
    closethanksDialog() {
      this.caregiverDialog = false;
      this.titlecb = '';
      this.FNametc = '';
      this.SNametc = '';
      this.mailtc = '';
      this.phonetc = '';
      this.cnametc = '';
      this.messagetc = '';
    },
    closecomplaintDialog() {
      this.complaintDialog = false;
      this.complaintTitle = '';
      this.complaintFName = '';
      this.complaintSName = '';
      this.complaintEmail = '';
      this.complaintPhone = '';
      this.complaintMsg = '';
    },
    async thanksSave() {
      const thanksData = {
        Title: this.titlecb,
        FirstName: this.FNametc,
        SecondName: this.SNametc,
        Email: this.mailtc,
        Phonenumber: this.phonetc,
        Carername: this.cnametc,
        Message: this.messagetc,
        Date: new Date(),
      };

      const result = await this.$store.dispatch('saveThanks', thanksData);
      if (result && result.success === false) {
        if (typeof this.showPublicSnackbar === 'function') {
          this.showPublicSnackbar(result?.message || 'Failed to send thank you message.', 'error');
        }
        return;
      }

      this.closethanksDialog();
      if (typeof this.showPublicSnackbar === 'function') {
        this.showPublicSnackbar(result?.message || 'Thank you message sent successfully.', 'success');
      }
    },
    async complaintSave() {
      const complaintData = {
        Title: this.complaintTitle,
        FirstName: this.complaintFName,
        SecondName: this.complaintSName,
        Email: this.complaintEmail,
        Phonenumber: this.complaintPhone,
        Message: this.complaintMsg,
        Date: new Date(),
      };

      const result = await this.$store.dispatch('saveComplaint', complaintData);
      if (result && result.success === false) {
        if (typeof this.showPublicSnackbar === 'function') {
          this.showPublicSnackbar(result?.message || 'Failed to submit concern.', 'error');
        }
        return;
      }

      this.closecomplaintDialog();
      if (typeof this.showPublicSnackbar === 'function') {
        this.showPublicSnackbar(result?.message || 'Concern submitted successfully.', 'success');
      }
    },
  },
};
