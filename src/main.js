import { createApp } from 'vue'
// import './style.css'
import App from './App.vue'
import router from './router'
import store from './store/store.js'
import publicCmsMixin from './mixins/publicCms'

import 'vuetify/dist/vuetify.min.css'; // Import the Vuetify CSS file
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import VueGtag from "vue-gtag";

const vuetify = createVuetify({
  components,
  directives
});

if (typeof window !== 'undefined') {
  window.__facilitateRouter = router;
  router.afterEach(() => {
    window.setTimeout(() => {
      window.dispatchEvent(new Event('facilitate:route-changed'));
    }, 0);
  });
}

const app = createApp(App)

app.mixin(publicCmsMixin)

app
  .use(router)
  .use(store)
  .use(vuetify)
  .use(VueGtag, {
    config: { id: "G-169WM39GZ1" },
  })
  .mount('#app')

if (typeof window !== 'undefined') {
  window.setTimeout(() => {
    window.dispatchEvent(new Event('facilitate:route-changed'));
  }, 0);
}
