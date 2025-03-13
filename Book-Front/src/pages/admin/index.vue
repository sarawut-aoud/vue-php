<template>
  <v-responsive class="border rounded">
    <v-app :theme="theme" class="position-relative">
      <App-header @themes="getTheme"> </App-header>
      <v-navigation-drawer theme="dark" permanent floating absolute>
        <v-list v-model="tabmenu" density="compact" nav>
          <v-list-subheader>MENU</v-list-subheader>
          <v-list-item
            @click="updateModel('dashboard')"
            :active="tabmenu == 'dashboard'"
            prepend-icon="mdi-view-dashboard"
            title="Dashboard"
            value="dashboard"
          ></v-list-item>
          <v-list-item
            @click="updateModel('users')"
            :active="tabmenu == 'users'"
            prepend-icon="mdi-account-group"
            title="ผู้ใช้งาน"
            value="users"
          ></v-list-item>
          <v-list-item
            @click="updateModel('orders')"
            :active="tabmenu == 'orders'"
            prepend-icon="mdi-order-bool-descending-variant"
            title="ออเดอร์"
            value="orders"
          ></v-list-item>
          <v-divider class="mt-5"></v-divider>
          <v-list-subheader>Mangement</v-list-subheader>
          <v-list-item
            @click="updateModel('category')"
            :active="tabmenu == 'category'"
            prepend-icon="mdi-archive-outline"
            title="หมวดหมู่หนังสือ"
            value="category"
          ></v-list-item>
          <v-list-item
            @click="updateModel('products')"
            :active="tabmenu == 'products'"
            prepend-icon="mdi-book-open-variant"
            title="หนังสือ"
            value="products"
          ></v-list-item>
          <v-list-item
            @click="updateModel('setting')"
            :active="tabmenu == 'setting'"
            prepend-icon=" mdi-cog"
            title="ตั้งค่าการชำระเงิน"
            value="setting"
          ></v-list-item>
          <v-divider class="mt-5"></v-divider>
          <v-list-subheader>Report</v-list-subheader>
          <v-list-item
            @click="updateModel('report_day')"
            :active="tabmenu == 'report_day'"
            prepend-icon="mdi-file"
            title="สรุปยอด วันนี้"
            value="report_day"
          ></v-list-item>
          <v-list-item
            @click="updateModel('summary')"
            :active="tabmenu == 'summary'"
            prepend-icon="mdi-file"
            title="สรุปยอด วัน/เดือน/ปี"
            value="summary"
          ></v-list-item>
        </v-list>
      </v-navigation-drawer>
      <v-main>
        <v-progress-linear
          class="w-100"
          v-if="loadingPage"
          color="primary"
          indeterminate
        ></v-progress-linear>
        <div class="pa-5">
          <template v-if="tabmenu == 'dashboard'">
            <dashboard />
          </template>
          <template v-if="tabmenu == 'users'">
            <users />
          </template>
          <template v-if="tabmenu == 'orders'">
            <orders></orders>
          </template>
          <template v-if="tabmenu == 'category'">
            <category></category>
          </template>
          <template v-if="tabmenu == 'products'">
            <product></product>
          </template>
          <template v-if="tabmenu == 'setting'">
            <setting></setting>
          </template>
          <template v-if="tabmenu == 'report_day'">
            <report_dayVue></report_dayVue>
          </template>
          <template v-if="tabmenu == 'summary'">
            <report_summaryVue></report_summaryVue>
          </template>
        </div>
      </v-main>
    </v-app>
  </v-responsive>
  <app-footer></app-footer>
  <Notivue v-slot="item">
    <Notification :item="item" />
  </Notivue>
</template>

<script setup>
import { ref, onMounted, inject } from "vue";
import api from "/utils/axios";
import { Notivue, Notification, push } from "notivue";
import { useJWT } from "@/composables/useJWT";
import { useCookie } from "@/composables/useCookie";
import Dashboard from "./dashboard.vue";
import users from "./users.vue";
import category from "./category.vue";
import setting from "./setting.vue";
import product from "./product.vue";
import orders from "./orders.vue";
import report_dayVue from "./report_day.vue";
import report_summaryVue from "./report_summary.vue";

const { getItem } = useJWT();
const { getCookie, setCookie } = useCookie();

const theme = ref("light");
const getTheme = (e) => {
  theme.value = e;
};
const loadingPage = ref(false);
const tabmenu = ref("dashboard");
const updateModel = (value) => {
  loadingPage.value = true;
  tabmenu.value = value;
  setCookie("tabmenu", value);
  setTimeout(() => {
    loadingPage.value = false;
  }, 1000);
};
onMounted(() => {
  let tab = getCookie("tabmenu");
  if (tab) tabmenu.value = tab;
});
</script>
