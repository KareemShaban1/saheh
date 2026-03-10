import { createApp } from "vue";
import { createPinia } from "pinia";
import router from "./vue/router";
import App from "./vue/app.vue";
import "./bootstrap";

const app = createApp(App);

app.use(createPinia());
app.use(router);

app.mount("#vue-app");






