<script setup>
import { ref, onMounted } from "vue";
import { Notivue, Notification, push } from "notivue";
import GallaryItems from "@/components/GallaryItems.vue";
import api from "/utils/axios";
import AppHeader from "@/components/AppHeader.vue";
import AppFooter from "@/components/AppFooter.vue";
const theme = ref("light");

const catelists = ref([]);
const getList = async () => {
  let { data } = await api.get("/api/category/getList");
  if (data?.data.length > 0) {
    catelists.value = data.data;
  }
};

const tab = ref("all");
console.log(tab.value);
onMounted(() => {
  getList();
});
const getTheme = (e) => {
  theme.value = e;
};
</script>

<template>
  <v-responsive class="border rounded">
    <v-app :theme="theme" class="position-relative">
      <App-header @themes="getTheme"></App-header>
      <v-main style="padding-top: 100px">
        <v-container>
          <div class="d-flex flex-column ga-2">
            <div class="d-flex justify-center align-center">
              <v-card width="800">
                <v-img :src="'/api/assets/images/logo_1.jpg'" cover></v-img>

              </v-card>
            </div>
            <div>
              <Gallary-items :cateId="null"></Gallary-items>
            </div>
          </div>
        </v-container>
      </v-main>

      <AppFooter />
    </v-app>
  </v-responsive>
</template>
