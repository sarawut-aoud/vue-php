<template>
  <v-app>
    <v-main>
      <router-view />
    </v-main>
  </v-app>
</template>
<script setup>
import { ref, provide, onMounted } from "vue";
import { useJWT } from "./composables/useJWT";
import { useCookie } from "./composables/useCookie";
import api from "/utils/axios";
const { getCookie } = useCookie();
const { getItem } = useJWT();
const globalitem = ref(getItem(getCookie("jwt")));
const info = ref("");
provide("_info", info);
const getInfo = async () => {
  if (!globalitem.value) return false;
  let { data } = await api({
    method: "get",
    url: "/api/Personal/getInfo",
    params: {
      uid: globalitem.value?.ui,
    },
  });
  if (data?.data) {
    info.value = data.data;
  }
};
const cartCount = ref(0);
const getCountCart = async () => {
  cartCount.value = 0;
  if (globalitem.value?.ui) {
    let { data } = await api.get("/api/orders/getMyCart", {
      params: {
        uid: globalitem.value?.ui,
      },
    });
    if (data.data?.length > 0) {
      cartCount.value = data.data?.length;
    } else {
      cartCount.value = 0;
    }
  }
};

provide("_getInfo", getInfo);
provide("getCountCart", getCountCart);
provide("cartCount", cartCount);

onMounted(() => {
  getInfo();
  getCountCart()
});
</script>

<style>
@import url("https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&display=swap");

* {
  font-family: "Noto Sans Thai", serif;
  font-optical-sizing: auto;
  font-weight: 500;
  font-style: normal;
}

[aria-label="Notifications"] {
  z-index: 3000 !important;
  position: relative;
  /* หรือ absolute/fixed ถ้าจำเป็น */
}
</style>
